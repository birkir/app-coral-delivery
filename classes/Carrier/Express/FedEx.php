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
	 * Get statuses for tracking number
	 *
	 * @return int
	 */
	public function track()
	{
		// Set counter
		$count = 0;

		// Get package
		$data = $this->getRequest();

		// Find first package in data
		$response = $data->TrackPackagesResponse->packageList[0];

		if ($response->errorList[0]->code == '1041')
		{
			// Did not found package
			$status = Model_Package::NOT_FOUND;
			return;
		}

		if (empty($this->package->origin_location))
		{
			// Set package origin location
			$loc = Carrier::location_to_coords($response->shipperCity.', '.$response->shipperCntryCD);
			$this->package->origin_location = isset($loc['country']) ? $loc['country'] : $response->shipperCntryCD;
		}

		if (empty($this->package->destination_location))
		{
			// Set package destination location
			$loc = Carrier::location_to_coords($response->recipientCity.', '.$response->recipientCntryCD);
			$this->package->destination_location = isset($loc['country']) ? $loc['country'] : $response->recipientCntryCD;
		}

		$extras = array(
			'signature' => 'receivedByNm',
			'service'   => 'serviceDesc',
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

		foreach (array_reverse($response->scanEventList) as $event)
		{
			// Create status item
			$item = array();
			$item['location_raw'] = $event->scanLocation;
			$item['location'] = Carrier::location_to_coords($item['location_raw']);
			$item['message'] = $event->status.' '.$event->scanDetails;
			$item['datetime'] = new DateTime();
			$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
			$item['datetime']->setTimestamp(strtotime($event->date.' '.$event->time));

			// Insert status to database
			if ($this->append_status($item))
			{
				// Process package state
				$this->checkState($item);

				// Increment count status
				$count++;
			}
		}

		return $count;
	}

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
		$client->options(CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36');
		$client->options(CURLOPT_HEADER,     'X-Requested-With: XMLHttpRequest'.PHP_EOL.'Origin: https://www.fedex.com');
		$client->options(CURLOPT_REFERER,    'https://www.fedex.com/fedextrack/index.html?tracknumbers='.$this->tracking_number.'&cntry_code=us');
		$client->options(CURLOPT_COOKIEJAR,  APPPATH.'cache/www.fedex.com.cookie');
		$client->options(CURLOPT_COOKIEFILE, APPPATH.'cache/www.fedex.com.cookie');

		// Execute Curl Request
		$response = $request->execute();

		// Parse JSON Response
		return json_decode($response->body());
	}

	/**
	 * Check if status matches when shipping info was received.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkRegistered($message, $location)
	{
		return preg_match('#shipment information sent to fedex#s', $message);
	}

	/**
	 * Check if status matches when package was registered.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkInTransit($message, $timestamp)
	{
		return TRUE;
	}

	/**
	 * Check if status matches when package was dispatched.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkDispatched($message, $timestamp)
	{
		return preg_match('#left fedex origin facility#s', $message);
	}

	/**
	 * Check if status matches when package in customs.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkInCustoms($message, $timestamp)
	{
		return preg_match('#international shipment release#s', $message);
	}

	/**
	 * Check if status matches when package is out for delivery.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkOutForDelivery($message, $timestamp)
	{
		return preg_match('#on fedex vehicle for delivery#s', $message);
	}

	/**
	 * Check if status matches when package is out for delivery.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkFailedAttempt($message, $timestamp)
	{
		return preg_match('#delivery exception future delivery requested#s', $message);
	}

	/**
	 * Check if status matches when package is delivered.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkDelivered($message, $timestamp)
	{
		return preg_match('#delivered#s', $message);
	}

	/**
	 * Process current status for package datetimes and current state.
	 *
	 * @param  array $item
	 * @return void
	 */
	public function checkState($item)
	{
		// Get package
		$package = $this->package;

		// Set datetime timezone to application's timezone 
		$item['datetime']->setTimezone(new DateTimeZone(date_default_timezone_get()));

		// Get status timestamp
		$timestamp = $item['datetime']->getTimestamp();

		// Get status datetime
		$datetime = $item['datetime']->format('Y-m-d H:i:s');

		// Get message as lowercase
		$message = UTF8::trim(UTF8::strtolower($item['message']));

		// Get current state
		$state = intval($this->package->state);

		// Check if registered timestamp is newer than current, or empty
		if ((empty($package->registered_at) OR strtotime($package->registered_at) > $timestamp) AND $this->checkRegistered($message, $timestamp))
		{
			// Set package registered timestamp
			$this->package->registered_at = $datetime;

			// Set package state
			$state = Model_Package::REGISTERED;
		}

		// Check if dispatched at timestamp is newer than current, or empty
		else if ($this->checkInTransit($message, $timestamp))
		{
			// Set package dispatched timestamp
			if ((empty($package->dispatched_at) OR strtotime($package->dispatched_at) > $timestamp))
			{
				$this->package->dispatched_at = $datetime;
			}

			if ($state < Model_Package::DELIVERED)
			{
				// Set package state to "In transit".
				$state = Model_Package::IN_TRANSIT;
			}
		}

		// List state callbacks
		$checkCallbacks = array(
			Model_Package::IN_CUSTOMS       => 'checkInCustoms',
			Model_Package::OUT_FOR_DELIVERY => 'checkOutForDelivery',
			Model_Package::FAILED_ATTEMPT   => 'checkFailedAttempt'
		);

		foreach ($checkCallbacks as $checkState => $callback)
		{
			if ($state < $checkState AND call_user_func_array(array($this, $callback), array($message, $timestamp)))
			{
				// Set current state
				$state = $checkState;
			}
		}

		if (empty($package->completed_at) AND $this->checkDelivered($message, $timestamp))
		{
			// Set package complete timestamp
			$this->package->completed_at = $datetime;

			if ($state < Model_Package::DELIVERED)
			{
				// Set package state to "Delivered".
				$state = Model_Package::DELIVERED;
			}
		}

		// Set package model state
		$this->package->state = $state;
	}

} // End FedEx Express Carrier