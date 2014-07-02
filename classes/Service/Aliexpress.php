<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Aliexpress Service
 *
 * @package    Coral
 * @category   Service
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Service_Aliexpress extends Service {

	/**
	 * Constants
	 */
	const UNCOFIRMED_DELIVERY = 'unconfirmedDelivery';
	const PAYMENT_REQUIRED = 'paymentRequired';
	const COMPLETED_ORDER = 'completedOrder';
	const SHIPMENT_REQUIRED = 'shipmentRequired';

	/**
	 * @var object Member token array
	 */
	private $member;

	/**
	 * @var string Service username or email
	 */
	private $username;

	/**
	 * @var string Service password (encrypted)
	 */
	private $password;

	/**
	 * Create singleton instance
	 *
	 * @return Service_Aliexpress
	 */
	public static function instance($service)
	{
		return new Service_Aliexpress($service);
	}

	/**
	 * Class constructor
	 *
	 * @param  Model_User_Service
	 * @return Service_Aliexpress
	 */
	public function __construct($service)
	{
		// Create encrypt instance
		$encrypt = Encrypt::instance();

		// Set service
		$this->service = $service;

		// Set username and password
		$this->username = $service->username;
		$this->password = $encrypt->decode($service->password);

		try
		{
			// Set member
			$this->member = json_decode($service->data);

			// Try to access member access token
			$token = $this->member->accessToken;
		}
		catch (Exception $e)
		{
			// Attempt to authenticate
			$this->authenticate();
		}
	}

	/**
	 * List service available methods for user interface
	 *
	 * @return array
	 */
	public function methods()
	{
		return array(
			'add_completed_order' => 'Add completed orders'
		);
	}

	/**
	 * Show detail page for service
	 *
	 * @return string
	 */
	public function detail()
	{
		// Setup view
		$view = View::factory('service/aliexpress/detail')
		->bind('data', $data);

		// Set data as member info
		$data = $this->member;

		return $view;
	}

	/**
	 * Method to add all completed orders as packages
	 *
	 * @param Controller
	 * @return void
	 */
	public function add_completed_order($controller)
	{
		// Get list of completed orders
		$list = $this->getOrderList(Service_Aliexpress::COMPLETED_ORDER);

		foreach ($list->orderViewList as $item)
		{
			// Get order tracking information
			$track = $this->getTrackingInfo($item->orderId);

			if ( ! isset($track->shippingOrderList)) continue;

			foreach ($track->shippingOrderList as $info)
			{
				if (ORM::factory('Package', array('tracking_number' => $info->trackingNum))->loaded())
					continue;

				// Create new package and set properties
				$package = ORM::factory('Package');
				$package->user_id = $controller->user->id;
				$package->tracking_number = $info->trackingNum;
				$package->photo = 'http://g01.a.alicdn.com/kf/'.$item->smallPhotoPath;
				$package->description = $item->productName->value;
				$package->origin_carrier_id = 4;
				$package->destination_carrier_id = 4;
				$package->created_at = date('Y-m-d H:i:s');
				$package->save();
			}
		}
	}

	/**
	 * Function that task processor runs on 6 hour interval
	 *
	 * @return void
	 */
	public function process()
	{
		// Get list of unconfirmed delivery orders
		$list = $this->getOrderList(Service_Aliexpress::UNCOFIRMED_DELIVERY);

		// Setup messages to display in task
		$messages = array(
			'Processing aliexpress packages for "'.$this->service->name.'".'
		);

		// Counter to zero
		$count = 0;

		// Skip if empty list
		if ( ! isset($list->orderViewList)) return $messages;

		foreach ($list->orderViewList as $item)
		{
			// Get package tracking
			$track = $this->getTrackingInfo($item->orderId);

			// Skip if empty tracking
			if ( ! isset($track->shippingOrderList)) continue;

			foreach ($track->shippingOrderList as $info)
			{
				if (ORM::factory('Package', array('tracking_number' => $info->trackingNum))->loaded())
					continue;

				// Create package model and set values
				$package = ORM::factory('Package');
				$package->user_id = $this->service->user->id;
				$package->tracking_number = $info->trackingNum;
				$package->photo = 'http://g01.a.alicdn.com/kf/'.$item->smallPhotoPath;
				$package->description = $item->productName->value;
				$package->origin_carrier_id = 4;
				$package->destination_carrier_id = 4;
				$package->created_at = date('Y-m-d H:i:s');
				$package->save();

				// Append message to list
				$messages[] = 'Added [Package '.Minion_CLI::color($package->tracking_number, 'light_blue').'] to database.';

				// Bump the count
				$count++;
			}
		}

		// Set done message
		$messages[] = (count($messages) === 1) ? 'Done! No packages added.' : 'Done! Added '.$count.' packages to database.';

		return $messages;
	}

	/**
	 * Get access token for 36000 seconds (10 hours)
	 *
	 * @return void
	 */
	public function authenticate()
	{
		// Get Alixpress Request's response
		$response = $this->request('1/aliexpress.mobile/member.login', array(
			'name' => $this->username,
			'password' => $this->password,
			'appkey' => 6
		));

		if ($response->head->code !== '200')
		{
			throw new Kohana_Exception($response->head->message);
		}

		// Set member information
		$this->member = $response->body;

		// Update service
		$this->service->data = json_encode($this->member);
		$this->service->save();
	}

	/**
	 * Create HTTP Request to Aliexpress API
	 *
	 * @param  string Method to use
	 * @param  string Query string array
	 * @return HTTP_Response
	 */
	public function request($method, array $query = array(), $attempt = 0)
	{
		// Check if its a login
		$authenticating = (isset($query['name']) AND isset($query['password']));

		if ( ! $authenticating)
		{
			// Set access token to query
			$query['access_token'] = $this->member->accessToken;
		}

		// Bumpt the attempts
		$attempt++;

		// Create HTTP Request
		$request = Request::factory('https://gw.api.alibaba.com/openapi/param2/'.$method.'/6');

		// Set Request query string
		$request->query($query);

		// Execute the Request and return Response
		$response = json_decode($request->execute()->body());

		if (isset($query['name']) AND isset($query['password']))
		{
			return $response;
		}

		if (isset($response->error_code) AND $response->error_code === '401')
		{
			if ($attempt > 1)
				return $response;

			// Attempt to authenticate
			$this->authenticate();

			// Then try again
			return $this->request($method, $query, $attempt);
		}

		// Return data
		return $response->body;
	}

	/**
	 * Get list of members orders
	 *
	 * @param  string Status
	 * @return array
	 */
	public function getOrderList($status = NULL)
	{
		// Set list of allowed statuses
		$allowed_status = array(
			Service_Aliexpress::UNCOFIRMED_DELIVERY,
			Service_Aliexpress::PAYMENT_REQUIRED,
			Service_Aliexpress::COMPLETED_ORDER,
			Service_Aliexpress::SHIPMENT_REQUIRED
		);

		if (empty($status) OR ! in_array($status, $allowed_status))
		{
			throw new Kohana_Exception('The status :status is not valid.', array(
				':status' => $status));
		}

		// Get Alixpress Request's response
		$response = $this->request('4/aliexpress.mobile/order.listBusinessOrderList', array(
			'isAdmin' => 'y',
			'status' => $status,
			'currentPage' => 1,
			'pageSize' => 10
		));

		return $response;
	}

	/**
	 * Get tracking info
	 * 
	 * @param  int   Order ID
	 * @return array
	 */
	public function getTrackingInfo($orderId)
	{
		// Get Alixpress Request's response
		$response = $this->request('3/aliexpress.mobile/order.getLogisticsInfo', array(
			'orderId' => $orderId,
			'tradeWay' => 'NORMAL'
		));

		return $response;
	}

} // End Aliexpress Service