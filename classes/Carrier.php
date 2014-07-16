<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Carrier base class
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier {

	/**
	 * @var integer Origin carrier
	 */
	const ORIGIN = 0;

	/**
	 * @var integer Destination carrier
	 */
	const DESTINATION = 1;

	/**
	 * @var array   Coordinates buffer array
	 */
	public static $coords = array();

	/**
	 * @var array   Timezones buffer array
	 */
	public static $timezones = array();

	/**
	 * @var string  User agent string
	 */
	public static $user_agent = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';

	/**
	 * @var bool    Debug flag
	 */
	public $debug = FALSE;

	/**
	 * @var string  Check delivery regex
	 */
	public $matchRegistered     = FALSE;
	public $matchDispatched     = FALSE;
	public $matchInCustoms      = FALSE;
	public $matchOutForDelivery = FALSE;
	public $matchFailedAttempt  = FALSE;
	public $matchException      = FALSE;
	public $matchPickUp         = FALSE;
	public $matchDelivered      = FALSE;

	/**
	 * Construct class
	 */
	public function __construct($package, $carrier)
	{
		// Set package
		$this->package = $package;

		// Set carrier
		$this->carrier = $carrier;

		// Should carrier handle both directions?
		$this->multiple = ($package->origin_carrier_id === $package->destination_carrier_id);

		// Which direction is it attached to?
		$this->direction = ($package->destination_carrier_id === $carrier->id) ? Carrier::DESTINATION : Carrier::ORIGIN;
	}

	/**
	 * Process status items
	 */
	public function process()
	{
		$response = $this->getRequest();
		$statuses = $this->getStatusItems($response);
		$multiple = $this->multiple;
		$direction = $this->direction;

		foreach (array(Carrier::ORIGIN, Carrier::DESTINATION) as $d)
		{
			if ($direction === $d OR $multiple)
			{
				// Sort states by datetime
				uasort($statuses[$d], function ($a, $b) {
					return ($a['datetime']->getTimestamp() < $b['datetime']->getTimestamp()) ? -1 : 1;
				});

				foreach ($statuses[$d] as $item)
				{
					$this->processStatusItem($item, $d);
				}
			}
		}
	}

	/**
	 * Get request needed for further processing
	 *
	 * @return mixed
	 */
	public function getRequest()
	{
		return NULL;
	}

	/**
	 * Get status items categorized by direction
	 *
	 * @return array
	 */
	public function getStatusItems($response)
	{
		$items = array(
			Carrier::ORIGIN      => array(),
			Carrier::DESTINATION => array()
		);

		return $items;
	}

	/**
	 * Add scan status to package and process its state
	 *
	 * @param  array $item
	 * @return void
	 */
	public function processStatusItem($item, $direction)
	{
		// Create identifier for status
		$identifier = sha1($item['location']['raw'].$item['datetime']->getTimestamp().$item['message']);

		if ( ! $this->package->status->where('identifier', '=', $identifier)->find()->loaded())
		{
			try
			{
				// Create package status object
				$status = ORM::factory('Package_Status');
				$status->package_id = $this->package->id;
				$status->carrier_id = $this->carrier->id;
				$status->state = $this->getState($item);
				$status->direction = $direction;
				$status->identifier = $identifier;
				$status->raw = json_encode($item);
				$status->timestamp = $item['datetime']->format('Y-m-d H:i:s');
				$status->location = $item['location']['name'];
				$status->coordinates = $item['location']['coordinates'];
				$status->message = $item['message'];

				if ($this->debug)
				{
					echo Debug::vars($status->as_array());
				}
				else
				{
					$status->save();
				}

				return TRUE;
			}
			catch (ORM_Validation_Exception $e) { }
		}

		return FALSE;
	}

	/**
	 * Process current status for package datetimes and current state.
	 *
	 * @param  array $item
	 * @return void
	 */
	public function getState($item)
	{
		// Get package
		$package = $this->package;

		// Get status timestamp
		$timestamp = $item['datetime']->getTimestamp();

		// Get status datetime
		$datetime = $item['datetime']->format('Y-m-d H:i:s');

		// Get message as lowercase
		$message = UTF8::trim(UTF8::strtolower($item['message']));

		// Get location
		$location = $item['location'];
		$location['raw'] = UTF8::trim(UTF8::strtolower($location['raw']));

		// Get current state
		$state = $package->state;

		// Check if registered timestamp is newer than current, or empty
		if ((empty($package->registered_at) OR strtotime($package->registered_at) > $timestamp) AND $this->checkRegistered($message, $timestamp, $location))
		{
			// Set package registered timestamp
			$this->package->registered_at = $datetime;

			// Set package state
			$state = Model_Package::REGISTERED;
		}

		// Check if dispatched at timestamp is newer than current, or empty
		if ($this->checkDispatched($message, $timestamp, $location))
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
			Model_Package::EXCEPTION        => 'checkException',
			Model_Package::FAILED_ATTEMPT   => 'checkFailedAttempt',
			Model_Package::PICK_UP          => 'checkPickUp'
		);

		foreach ($checkCallbacks as $checkState => $callback)
		{
			if ($state < $checkState AND call_user_func_array(array($this, $callback), array($message, $timestamp, $location)))
			{
				// Set current state
				$state = $checkState;
			}
		}

		if ($this->checkDelivered($message, $timestamp, $location))
		{
			// Set package complete timestamp
			if (empty($package->completed_at) OR strtotime($package->completed_at) < $timestamp)
			{
				$this->package->completed_at = $datetime;
			}

			if ($state < Model_Package::DELIVERED)
			{
				// Set package state to "Delivered".
				$state = Model_Package::DELIVERED;
			}
		}

		// Set package model state
		$this->package->state = $state;

		return $state;
	}

	/**
	 * Check if status matches when shipping info was received.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkRegistered($message, $timestamp, $location)
	{
		if ( ! $this->matchRegistered)
			return FALSE;

		return preg_match($this->matchRegistered, $message);
	}

	/**
	 * Check if status matches when package was dispatched.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkDispatched($message, $timestamp, $location)
	{
		if ( ! $this->matchDispatched)
			return FALSE;

		return preg_match($this->matchDispatched, $message);
	}

	/**
	 * Check if status matches when package in customs.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkInCustoms($message, $timestamp, $location)
	{
		if ( ! $this->matchInCustoms)
			return FALSE;

		return preg_match($this->matchInCustoms, $message);
	}

	/**
	 * Check if status matches when package is out for delivery.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkOutForDelivery($message, $timestamp, $location)
	{
		if ( ! $this->matchOutForDelivery)
			return FALSE;

		return preg_match($this->matchOutForDelivery, $message);
	}

	/**
	 * Check if status matches when package has failed to deliver.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkFailedAttempt($message, $timestamp, $location)
	{
		if ( ! $this->matchFailedAttempt)
			return FALSE;

		return preg_match($this->matchFailedAttempt, $message);
	}

	/**
	 * Check if status matches when package delivery has exception.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkException($message, $timestamp, $location)
	{
		if ( ! $this->matchException)
			return FALSE;

		return preg_match($this->matchException, $message);
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
		if ( ! $this->matchPickUp)
			return FALSE;

		return preg_match($this->matchPickUp, $message);
	}

	/**
	 * Check if status matches when package is delivered.
	 *
	 * @param  string $message
	 * @param  int    $timestamp
	 * @return bool
	 */
	public function checkDelivered($message, $timestamp, $location)
	{
		if ( ! $this->matchDelivered)
			return FALSE;

		return preg_match($this->matchDelivered, $message);
	}

	/**
	 * Convert string of location to array of useful info
	 *
	 * @param  string Location
	 * @return string Cordinates
	 */
	public static function getLocation($str)
	{
		if ( ! isset(Carrier::$coords[$str]))
		{
			// Defaults to original value
			$result = array('raw' => $str, 'name' => $str, 'coordinates' => NULL);

			// Create google maps geocode request
			$request = Request::factory('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($str));

			// Execute request for response
			$response = $request->execute();

			// Convert body JSON to Array
			$data = json_decode($response->body());

			if (isset($data->results[0]) AND $data->status === 'OK')
			{
				// Easy results access
				$res = $data->results[0];

				foreach ($res->address_components as $cmp)
				{
					foreach ($cmp->types as $type)
					{
						if ($type === 'country')
						{
							// Append country to result array
							$result['country'] = $cmp->long_name;
						}
					}
				}

				// Append to result
				$result['name'] = $res->formatted_address;
				$result['coordinates'] = $res->geometry->location->lat.','.$res->geometry->location->lng;
				$result['timezone'] = Carrier::getTimezone($result['coordinates']);
			}

			// Set coords as cache
			Carrier::$coords[$str] = $result;
		}

		return Carrier::$coords[$str];
	}

	/**
	 * Get timezone by coordinates
	 *
	 * @param  string Coordinates
	 * @return string Timezone
	 */
	public static function getTimezone($coords)
	{
		if ( ! isset(Carrier::$timezones[$coords]))
		{
			// Create google maps geocode request
			$request = Request::factory('https://maps.googleapis.com/maps/api/timezone/json?location='.urlencode($coords).'&timestamp=0');

			// Execute request for response
			$response = $request->execute();

			// Convert body JSON to Array
			$data = json_decode($response->body());

			if ($data->status === 'OK')
			{
				// Attach timezone to array cache
				Carrier::$timezones[$coords] = $data->timeZoneId;
			}
		}

		return Carrier::$timezones[$coords];
	}

} // End Carrier