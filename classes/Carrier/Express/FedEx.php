<?php
/**
 * FedEx Express Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_Express_FedEx extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered = '/shipment information sent to fedex/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched = '/left fedex origin facility/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms = '/international shipment release/s';

	/**
	 * @var string Regex for out for delivery check
	 */
	public $matchOutForDelivery = '/on fedex vehicle for delivery/s';

	/**
	 * @var string Regex for failed attempt check
	 */
	public $matchFailedAttempt = '/delivery exception future delivery requested/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered = '/delivered/s';

	/**
	 * Get fedex information to process
	 *
	 * @return object
	 */
	public function getRequest()
	{
		// Setup HTTP Request
		$request = Request::factory('https://www.fedex.com/trackingCal/track')
		->method(Request::POST)
		->post(array(
			'data'    => '{"TrackPackagesRequest":{"appType":"wtrk","uniqueKey":"","processingParameters":{"anonymousTransaction":true,"clientId":"WTRK","returnDetailedErrors":true,"returnLocalizedDateTime":false},"trackingInfoList":[{"trackNumberInfo":{"trackingNumber":"'.$this->tracking_number.'","trackingQualifier":"","trackingCarrier":""}}]}}',
			'action'  => 'trackpackages',
			'locale'  => 'en_US',
			'format'  => 'json',
			'version' => '99'
		));

		// Get Request cURL Client
		$client = $request->client();

		// Set some options
		$client->options(CURLOPT_USERAGENT,  Carrier::$user_agent);
		$client->options(CURLOPT_HEADER,     'X-Requested-With: XMLHttpRequest'.PHP_EOL.'Origin: https://www.fedex.com');
		$client->options(CURLOPT_REFERER,    'https://www.fedex.com/fedextrack/index.html?tracknumbers='.$this->tracking_number.'&cntry_code=us');
		$client->options(CURLOPT_COOKIEJAR,  APPPATH.'cache/cookies/'.sha1('www.fedex.com').'.cookie');
		$client->options(CURLOPT_COOKIEFILE, APPPATH.'cache/cookies/'.sha1('www.fedex.com').'.cookie');

		// Execute Curl Request
		$response = $request->execute();

		// Parse JSON Response
		return json_decode($response->body());
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

		// Find first package in data
		$response = $response->TrackPackagesResponse->packageList[0];

		if ($response->errorList[0]->code == '1041')
		{
			// Did not found package
			$this->package->state = Model_Package::NOT_FOUND;
			return;
		}

		if (empty($this->package->origin_country_id))
		{
			// Get location data
			$location = Carrier::getLocation($response->shipperCity.', '.$response->shipperCntryCD);

			// Find country row in database
			$country = ORM::factory('Country', array('name' => Arr::get($location, 'country')));

			if ($country->loaded())
			{
				// Set country id
				$this->package->origin_country_id = $country->id;
			}
		}

		if (empty($this->package->destination_country_id))
		{
		// Get location data
			$location = Carrier::getLocation($response->recipientCity.', '.$response->recipientCntryCD);

			// Find country row in database
			$country = ORM::factory('Country', array('name' => Arr::get($location, 'country')));

			if ($country->loaded())
			{
				// Set country id
				$this->package->destination_country_id = $country->id;
			}
		}

		// Setup extras array
		$extras = array(
			'signature' => 'receivedByNm',
			'services'  => 'serviceDesc',
			'packaging' => 'packaging',
			'pieces'    => 'totalPieces'
		);

		foreach ($extras as $key => $value)
		{
			if ( ! empty($response->{$value}) AND isset($response->{$value}))
			{
				// Add extra information
				$this->package->extras($key, $response->{$value});
			}
		}

		if (empty($this->package->weight))
		{
			// Set package weight
			$this->package->weight = floatval(isset($response->totalKgsWgt) ? $response->totalKgsWgt : $response->pkgKgsWgt) * 1000;
		}

		foreach ($response->scanEventList as $event)
		{
			// Create status item
			$item = array();
			$item['location'] = Carrier::getLocation($event->scanLocation);
			$item['message'] = $event->status.' '.$event->scanDetails;
			$item['datetime'] = new DateTime();
			$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
			$item['datetime']->setTimestamp(strtotime($event->date.' '.$event->time));

			// Append to result array
			$items[$this->direction][] = $item;
		}

		return $items;
	}

} // End FedEx Express Carrier