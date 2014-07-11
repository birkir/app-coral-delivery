<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SparkFun Service
 *
 * @package    Coral
 * @category   Service
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Service_Sparkfun extends Service {

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
	 * @return Service_Sparkfun
	 */
	public static function instance($service)
	{
		return new Service_Sparkfun($service);
	}

	/**
	 * Class constructor
	 *
	 * @param  Model_User_Service
	 * @return Service_Sparkfun
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
	public function detail() { return ''; }

	/**
	 * Method to add all completed orders as packages
	 *
	 * @param Controller
	 * @return void
	 */
	public function add_completed_order($controller)
	{
		$orders = $this->getOrderList();

		foreach ($orders as $order)
		{
			if (ORM::factory('Package', array('tracking_number' => $order['tracking_number']))->loaded())
				continue;

			if ($order['status'] !== 'Shipped')
				continue;

			// Create new package and set properties
			$package = ORM::factory('Package');
			$package->user_id = $controller->user->id;
			$package->tracking_number = $order['tracking_number'];
			$package->description = NULL;

			if (preg_match('#FedEx#s', $order['shipping']))
			{
				$package->origin_carrier_id = ORM::factory('Carrier', array('name' => 'FedEx'))->id;
			}
			else if (preg_match('#USP Worldwide#s', $order['shipping']))
			{
				$package->origin_carrier_id = ORM::factory('Carrier', array('name' => 'FedEx'))->id;
			}
			else
			{
				$package->origin_carrier_id = 4;
				$package->destination_carrier_id = 4;
			}

			// Save package
			$package->created_at = date('Y-m-d H:i:s');
			$package->save();

			// Save uploaded file
			$filename = sha1($package->tracking_number).'.pdf';
			file_put_contents(APPPATH.'cache/uploads/'.$filename, $order['invoice']);

			// Add hooks for icelandic destination
			$hook = ORM::factory('Package_Hook');
			$hook->package_id = $package->id;
			$hook->name = 'Invoice';
			$hook->method = 'Emailattachment';
			$hook->enabled = 0;
			$hook->data = json_encode(array('filename' => $package->tracking_number.'.pdf', 'filepath' => $filename));
			$hook->save();
		}
	}

	/**
	 * Function that task processor runs on 6 hour interval
	 *
	 * @return void
	 */
	public function process() { return array(); }



	/**
	 * Download invoice with credentials
	 *
	 * @return void
	 */
	public function getInvoiceFile($url)
	{
		// Setup request
		$request = Request::factory($url);

		// Act as browser
		$request->client()
		->options(CURLOPT_COOKIEFILE, APPPATH.'cache/sparkfun.com.cookie')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36')
		->options(CURLOPT_REFERER, 'https://www.sparkfun.com/')
		->options(CURLOPT_HEADER, 'Accept-Encoding: gzip,deflate,sdch');

		return $request->execute()->body();
	}

	/**
	 * Extract tracking number from invoice pdf file
	 *
	 * @return void
	 */
	public function getTrackingNumber($file)
	{
		if (preg_match_all("#obj(.*)endobj#ismU", $file, $objects))
		{
			foreach ($objects[1] as $object)
			{
				if (preg_match('#\/FlateDecode#s', $object) AND preg_match('#stream(.*)endstream#ismU', $object, $stream))
				{
					if (preg_match('#\(Tracking Number: (.*?)\)#s', @gzuncompress(ltrim($stream[1])), $tracking_number))
					{
						return $tracking_number[1];
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Authenticate user with credentials provided.
	 *
	 * @return void
	 */
	public function authenticate()
	{
		// Setup request
		$request = Request::factory('https://www.sparkfun.com/account/login')
		->method(HTTP_Request::POST)
		->post(array(
			'user' => $this->username,
			'passwd' => $this->password
		));

		// Act as browser
		$request->client()
		->options(CURLOPT_COOKIEJAR, APPPATH.'cache/sparkfun.com.cookie')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36')
		->options(CURLOPT_REFERER, 'https://www.sparkfun.com/')
		->options(CURLOPT_HEADER, 'Accept-Encoding: gzip,deflate,sdch');

		// Execute request and get response body
		$response = $request->execute()->body();

		if (preg_match('#Invalid username or password#s', $response))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get list of members orders
	 *
	 * @param  string Status
	 * @return array
	 */
	public function getOrderList()
	{
		// Setup request
		$request = Request::factory('https://www.sparkfun.com/orders');

		// Act as browser
		$request->client()
		->options(CURLOPT_COOKIEFILE, APPPATH.'cache/sparkfun.com.cookie')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36')
		->options(CURLOPT_REFERER, 'https://www.sparkfun.com/')
		->options(CURLOPT_HEADER, 'Accept-Encoding: gzip,deflate,sdch');

		// Check the login
		$response = $request->execute()->body();

		if (preg_match('#You are \<strong\>not\<\/strong\> logged in#s', $response))
		{
			// Not logged in... sorry dude.
			return FALSE;
		}

		$items = array();

		if (preg_match('#<tbody.*?>(.*)<\/tbody>#s', $response, $table))
		{
			if (preg_match_all('#<tr.*?>(.*?)<\/tr>#s', $table[1], $rows))
			{
				foreach ($rows[1] as $row)
				{
					// Match cells
					preg_match_all('#<td.*?>(.*?)<\/td>#s', $row, $cells);

					// Match invoice file
					preg_match('#href\=\"(.*?)\"#s', $cells[1][9], $invoice);

					// Create package item
					$item = array();
					$item['id'] = UTF8::trim(strip_tags($cells[1][0]));
					$item['date'] = UTF8::trim($cells[1][2]);
					$item['shipping'] = UTF8::trim($cells[1][3]);
					$item['items'] = UTF8::trim($cells[1][4]);
					$item['units'] = UTF8::trim($cells[1][5]);
					$item['price'] = UTF8::trim(strip_tags($cells[1][6]));
					$item['balance'] = UTF8::trim(strip_tags($cells[1][7]));
					$item['status'] = UTF8::trim($cells[1][8]);
					$item['invoice'] = $this->getInvoiceFile($invoice[1]);
					$item['tracking_number'] = $this->getTrackingNumber($item['invoice']);

					// Do something with package item
					$items[] = $item;
				}
			}
		}

		return $items;
	}

} // End Sparkfun Service