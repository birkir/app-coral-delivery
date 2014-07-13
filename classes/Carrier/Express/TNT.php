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

	public function getRequest()
	{
		// Setup request
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
			'cons'          => $this->tracking_number
		));

		// Get Request cURL Client
		$client = Scraper::factory(array(), 'Scraper');
		$request->client($client);

		// Set some options
		$client->options(CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36');
		$client->options(CURLOPT_REFERER,    'http://www.tnt.com/express/en_us/site/home.html');
		$client->options(CURLOPT_COOKIEJAR,  APPPATH.'cache/www.tnt.com.cookie');
		$client->options(CURLOPT_COOKIEFILE, APPPATH.'cache/www.tnt.com.cookie');

		// Execute the request
		$response = $request->execute();

		return $response->html;
	}

	public function track()
	{
		$html = $this->getRequest();

		// Get status table
		$table = $html('table')[3];
		$table = $table('table');

		// Find all rows
		$details = $table[0];
		$list = $table[1];

		foreach ($details('tr') as $row)
		{
			if (empty($this->package->destination_location) AND preg_match('#Destination#s', $row->html()))
			{
				$loc = Carrier::location_to_coords(UTF8::substr($row('b')[0]->getPlainText(), 0, UTF8::strlen($row('b')[0]->getPlainText())));
				$this->package->destination_location = $loc['country'];
			}
		}

		foreach ($list('tr[valign=top]') as $row)
		{
			$cells = $row('td');

			// Get cells
			$date = $cells[0]->getPlainText();
			$time = $cells[1]->getPlainText();
			$location = $cells[2]->getPlainText();
			$message = $cells[3]->getPlainText();

			// The DOM is a mess, cleanup needed
			$datetime = date('Y-m-d', strtotime(UTF8::substr($date, 0, UTF8::strlen($date) - 1)));
			$datetime .= ' '.UTF8::substr($time, 0, UTF8::strlen($time) - 1);
			$location = UTF8::substr($location, 0, UTF8::strlen($location) - 1);
			$message = UTF8::substr($message, 0, UTF8::strlen($message) - 1);

			// Setup item
			$item = array();
			$item['location_raw'] = $location;
			$item['location'] = Carrier::location_to_coords($item['location_raw']);
			$item['message'] = $message;
			$item['datetime'] = new DateTime();
			$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
			$item['datetime']->setTimestamp(strtotime($datetime));

			if (empty($this->package->origin_location))
			{
				$this->package->origin_location = Arr::get($item['location'], 'country', 'Unknown');
			}

			$this->checkState($item);

			echo Debug::vars($item);
			echo Debug::vars($this->package->state);
		}

		echo Debug::vars($this->package->as_array());

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

	/**
	 * Check if status matches when shipping info was received.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkRegistered($message, $location)
	{
		return empty($this->package->registered_at);
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

} // End TNT Express Carrier