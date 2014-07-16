<?php defined('SYSPATH') or die('No direct script access.');
/**
 * USPS Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_US_USPS extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered     = '/electronic shipping info received/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched     = '/(accepted|processed|depart)/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms      = '/customs clearance/s';

	/**
	 * @var string Regex for failed attempt check
	 */
	public $matchFailedAttempt  = '/(delivery attempt|addressee cannot be located)/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered      = '/delivered/s';

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		// Create HTTP Request
		$request = Request::factory('https://tools.usps.com/go/TrackConfirmAction!input.action')
		->query(array(
			'tRef'    => 'qt',
			'tLc'     => 0,
			'tLabels' => 'CJ223231688US' //$this->package->tracking_number
		));

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_FOLLOWLOCATION, TRUE);
		$client->options(CURLOPT_SSL_VERIFYPEER, FALSE);
		$client->options(CURLOPT_USERAGENT, Carrier::$user_agent);

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

		if ($error = Arr::get($response('.progress-indicator'), 0) AND preg_match('/error/s', (string)$error->getPlainText()))
		{
			// Set package state
			return $this->package->state = Model_Package::NOT_FOUND;
		}

		// Find services and features
		foreach (array('product' => 'services', 'feature' => 'features') as $key => $value)
		{
			if ($item = Arr::get($response('.'.$key), 0))
			{
				// Setup list
				$list = array();

				foreach ($item('li') as $list_item)
				{
					// Append value to list
					$list[] = UTF8::trim($list_item->getPlainText());
				}

				// Add services to extras
				$this->package->extras($value, implode(', ', $list));
			}
		}

		if ($table = $response('#tc-hits', 0))
		{
			$rows = $table('tr');

			foreach ($rows as $row)
			{
				if ($location = $row('td.location', 0) AND $message = $row('td.status', 0) AND $datetime = $row('td.date-time', 0))
				{
					$item = array();
					$item['location'] = Carrier::getLocation(UTF8::trim(html_entity_decode($location->getPlainText())));
					$item['message'] = UTF8::trim(strip_tags($message->getPlainText()));
					$item['datetime'] = $this->parseDatetime($datetime->getPlainText(), Arr::get($item['location'], 'timezone'));
					$item['state'] = $this->getState($item);

					// Append to items array
					$items[$this->direction][] = $item;
				}
			}
		}

		return $items;
	}


	/**
	 * Parse datetime string from usps.com
	 *
	 * @param  string   Text to parse
	 * @return DateTime
	 */
	private function parseDatetime($str = NULL, $timezone = NULL)
	{
		// Create DateTime
		$dt = new DateTime();

		// Set timezone
		if ( ! empty($timezone))
		{
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		// Strip tags
		$str = strip_tags($str);

		// Explode by comma
		$parts = explode(',', $str);

		// Extract 
		$monthWithDate = UTF8::trim($parts[0]);

		// Extract year
		$year = UTF8::trim($parts[1]);

		// Set date
		$dt->setTimestamp(strtotime($monthWithDate.' '.$year));

		if (isset($parts[2]))
		{
			// Extract time
			$time = explode(':', UTF8::trim($parts[2]));
			$minutes = (intval($time[0]) * 60) + intval(UTF8::trim(substr($time[1], 0, 2))) + ((substr($time[1], -2) === 'pm') ? 720 : 0);
			$hours = floor($minutes / 60);
			$minutes -= ($hours * 60);
			
			// Set time
			$dt->setTime($hours, $minutes);
		}

		return $dt;
	}

} // End USPS Carrier