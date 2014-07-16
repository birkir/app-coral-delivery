<?php
/**
 * TNT Express Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_Express_TNT extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered = '/shipment received/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched = '/(in transit|shipment received at tnt location)/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms = '/customs/s';

	/**
	 * @var string Regex for out for delivery check
	 */
	public $matchOutForDelivery = '/out for delivery/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered = '/shipment delivered/s';

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		// Create HTTP Request
		$request = Request::factory('http://www.tnt.com/webtracker/tracking.do')
		->method(Request::POST)
		->post(array(
			'respCountry'   => 'us',
			'respLang'      => 'en',
			'navigation'    => '1',
			'page'          => '1',
			'sourceID'      => '1',
			'sourceCountry' => 'ww',
			'plazaKey'      => NULL,
			'refs'          => NULL,
			'requesttype'   => 'GEN',
			'searchType'    => 'CON',
			'cons'          => $this->package->tracking_number
		));

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_USERAGENT,  Carrier::$user_agent);
		$client->options(CURLOPT_REFERER,    'http://www.tnt.com/express/en_us/site/home.html');
		$client->options(CURLOPT_COOKIEJAR,  APPPATH.'cache/cookies/'.sha1('www.tnt.com').'.cookie');
		$client->options(CURLOPT_COOKIEFILE, APPPATH.'cache/cookies/'.sha1('www.tnt.com').'.cookie');

		// Execute request and get body
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

		// Get main table
		$table = $response('table', 3);

		// Get details table
		$details = $table('table', 0);

		// Get scan status table
		$list = $table('table', 1);

		foreach ($details('tr') as $row)
		{
			if (empty($this->package->destination_country_id) AND preg_match('#Destination#s', $row->html()))
			{
				// Get location data
				$location = Carrier::getLocation($this->getText($row('b', 0)));

				// Find country row in database
				$country = ORM::factory('Country', array('name' => Arr::get($location, 'country')));

				if ($country->loaded())
				{
					// Set country id
					$this->package->destination_country_id = $country->id;
				}
			}

			if (preg_match('#Signatory#s', $row->html()))
			{
				// Set signature to extra
				$this->package->extras('signature', $this->getText($row('b', 0)));
			}
		}

		foreach ($list('tr[valign=top]') as $row)
		{
			$cells = $row('td');

			// Get cells
			$datetime = date('Y-m-d', strtotime($this->getText($cells[0]))).' '.$this->getText($cells[1]);
			$location = $this->getText($cells[2]);
			$message = $this->getText($cells[3]);

			// Setup item
			$item = array();
			$item['location'] = Carrier::getLocation($location);
			$item['message'] = $message;
			$item['datetime'] = new DateTime();
			$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
			$item['datetime']->setTimestamp(strtotime($datetime));

			if (empty($this->package->origin_country_id))
			{
				// Find country row in database
				$country = ORM::factory('Country', array('name' => Arr::get($location, 'country')));

				if ($country->loaded())
				{
					// Set country id
					$this->package->origin_country_id = $country->id;
				}
			}

			// Append to result array
			$items[$this->direction][] = $item;
		}

		return $items;
	}

	/**
	 * Some unexpected character is at the end of some strings.
	 *
	 * @param  string $str
	 * @return string
	 */
	public function getText($str)
	{
		// Get string plain text
		$str = is_array($str) ? $str[0]->getPlainText() : $str->getPlainText();

		// Skew last character off
		return UTF8::substr($str, 0, UTF8::strlen($str) - 1);
	}

} // End TNT Express Carrier