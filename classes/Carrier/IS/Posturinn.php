<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Pósturinn Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_IS_Posturinn extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered = '/póstlagt/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched = '/fór frá erlendri/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms  = '/tollmiðlun/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered  = '/afhent|skannað til afhendingar/s';

	/**
	 * @var array Month names
	 */
	protected $_months = array(
		'JAN', 'FEB', 'MAR', 'APR',
		'MAÍ', 'JÚN', 'JÚL', 'AGÚ',
		'SEP', 'OKT', 'NÓV', 'DES'
	);

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		// Create HTTP Request
		$request = Request::factory('http://www.posturinn.is');

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_FOLLOWLOCATION, TRUE);
		$client->options(CURLOPT_SSL_VERIFYPEER, FALSE);
		$client->options(CURLOPT_USERAGENT, Carrier::$user_agent);

		// Execute request and get body
		$body = $request->execute()->body();

		// Setup post data array
		$post = array(
			'ctl01$ctl10$txtLabel' => $this->package->tracking_number,
			'ctl01$ctl10$Button1'  => 'Finna'
		);

		// List parameters wanted
		$params = array(
			'RadScriptManager1_TSM',
			'__EVENTTARGET',
			'__EVENTARGUMENT',
			'__VIEWSTATE',
			'pathinfo',
			'ctl01$ctl02$ctl00$query_string'
		);

		foreach ($params as $param)
		{
			if (preg_match('/'.$param.'\" value=\"(.*)\"/i', $body, $value))
			{
				// Extract value and push to post array
				$post[$param] = Arr::get($value, 1, NULL);
			}
		}

		// Create next HTTP Request in series
		$request = Request::factory('http://www.postur.is/desktopdefault.aspx')
		->method(Request::POST)
		->post($post);

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_FOLLOWLOCATION, TRUE);
		$client->options(CURLOPT_SSL_VERIFYPEER, FALSE);
		$client->options(CURLOPT_USERAGENT, Carrier::$user_agent);
		$client->options(CURLOPT_COOKIEJAR, APPPATH.'cache/cookies/'.sha1('www.posturinn.is').'.cookie');

		// Execute request and get response body
		$response = $request->execute()->body();

		// Parse response html dom
		$dom = new HTML_Parser_HTML5($response);

		return $dom->root;
	}

	/**
	 * Get status items categorized by direction
	 *
	 * @return array
	 */
	function getStatusItems($response)
	{
		// Setup returned items array
		$items = array(
			Carrier::ORIGIN      => array(),
			Carrier::DESTINATION => array()
		);

		// Check if package was found or not
		if ($not_found = Arr::get($response('#ctl01_ctl08_EyjolfsVilla1'), 0))
		{
			if ($not_found->getAttribute('style') === 'display:block;')
			{
				// Set package state
				return $this->package->state = Model_Package::NOT_FOUND;
			}
		}

		// Get package country
		$country = ($this->direction === Carrier::DESTINATION) ? $this->package->destination_country_id : $this->package->origin_country_id;

		if (empty($country))
		{
			// Set location to Iceland
			$this->package->{($this->type === Carrier::DESTINATION) ? 'destination_country_id' : 'origin_country_id'} = ORM::factory('Country', array('name' => 'Iceland'))->id;
		}

		// Get months and flip the array for indexes
		$months = array_flip($this->_months);

		if ($table = Arr::get($response('#ctl01_ctl08_divOuput'), 0))
		{
			// Find Rows
			foreach ($table('tr') as $i => $row)
			{
				if ($heading = Arr::get($row('th'), 0))
				{
					// Trim and clean text
					$heading = UTF8::trim($heading->getPlainText());

					if (preg_match('/Toll\-sendingarnúmer: (.*)/s', $heading, $customs_number))
					{
						// Set customs number
						$this->package->extras('customs_number', $customs_number[1]);
					}

					if (preg_match('/(.*innflutningsskýrsla.*)/s', $heading, $customs_type))
					{
						// Set customs type
						$this->package->extras('customs_type', $customs_type[1]);
					}

					if (preg_match('/Gjöld kr\. (.*)/s', $heading, $customs_payment))
					{
						// Set customs payment
						$this->package->extras('customs_payment', preg_replace('/\./s', NULL, $customs_payment[1]));
					}

					if (preg_match('/Þyngd (.*) KG/s', $heading, $weight))
					{
						// Set package weight
						$this->package->weight = floatval(preg_replace('/\,/s', '.', $weight[1])) * 1000;
					}
				}
				else
				{
					// Get cells inside row
					$cells = $row('td');

					// Get day of month
					$dayOfMonth = UTF8::trim($cells[0]->getPlainText());

					// Explode date by brake
					$monthAndTime = explode("<br />", UTF8::trim($cells[1]->getInnerText()));

					// Get month as number
					$monthOfYear = Arr::get($months, UTF8::trim(Arr::get($monthAndTime, 0)), 0) + 1;

					// Extract time
					$timeOfDay = UTF8::trim(Arr::get($monthAndTime, 1));

					// Get current year
					$year = date('Y') - (((intval(date('n')) < $monthOfYear)) ? 1 : 0);

					// Create the timestamp
					$timestamp = strtotime($year.'-'.$monthOfYear.'-'.$dayOfMonth.' '.(empty($timeOfDay) ? '22:00': $timeOfDay).':00');

					// Get location and description
					$locationAndDesc = explode("<br />", UTF8::trim($cells[2]->getInnerText()));

					// Extract location
					$location = preg_replace('/\s+/', ' ', UTF8::trim(strip_tags(Arr::get($locationAndDesc, 0))));

					// Extract message
					$message = UTF8::trim(strip_tags(Arr::get($locationAndDesc, 1)));

					// Setup item
					$item = array();
					$item['location'] = $this->parseLocation(UTF8::trim(html_entity_decode(strip_tags($location))));
					$item['message'] = UTF8::trim(strip_tags($message));
					$item['datetime'] = new DateTime();
					$item['datetime']->setTimeZone(new DateTimeZone('Atlantic/Reykjavik'));
					$item['datetime']->setTimestamp($timestamp);

					// Append to result array
					$items[$this->direction][] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * Parse location string to corresponding location info
	 *
	 * @param  string $str
	 * @return array
	 */
	public function parseLocation($str)
	{
		if (preg_match('#(ISREKA|Reykjavík|Tollmiðlun|Heimkeyrsla|Bréfaflokkunarvél)#s', $str))
		{
			// Return Pósturinn head quarters location
			return array(
				'raw'         => $str,
				'name'        => $str,
				'coordinates' => '64.1291751,-21.7966282',
				'timezone'    => 'Atlantic/Reykjavik',
				'country'     => 'Iceland'
			);
		}

		return Carrier::getLocation($str);
	}

	/**
	 * Check if status matches when package is available for pickup
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkPickUp($message, $timestamp, $location)
	{
		return (preg_match('/bréfaflokkunarvél/s', Arr::get($location, 'raw')) AND preg_match('/tilkynning/s', $message));
	}

} // End Posturinn IS Carrier