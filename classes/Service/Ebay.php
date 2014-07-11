<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Service base class
 *
 * @package    Coral
 * @category   Service
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Service_Ebay extends Service {

	public static function humanize($request, $jar = FALSE)
	{
		// Act as browser
		$request->client()
		->options(($jar === TRUE) ? CURLOPT_COOKIEJAR : CURLOPT_COOKIEFILE, APPPATH.'cache/ebay.com.cookie')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53')
		->options(CURLOPT_REFERER, 'https://m.ebay.com/signin')
		->options(CURLOPT_HEADER, 
			  "Accept-Encoding: gzip,deflate,sdch\r\n"
			. "Origin:https://m.ebay.com\r\n"
			. "Pragma:no-cache"
		);

		if ($jar)
		{
			$request->client()->options(CURLOPT_COOKIEFILE, APPPATH.'cache/ebay.com.cookie');
		}
	}

	/**
	 * Authenticate with ebay!
	 *
	 * @return boolean
	 */
	public function authenticate()
	{
		$request = Request::factory('https://m.ebay.com/signin');
		self::humanize($request, TRUE);
		$response = $request->execute()->body();

		if (preg_match('#dynseed".*?value\=\"(.*?)\"#s', $response, $dynseed))
		{
			$post = array(
				'userName' => 'solidr53',
				'pass' => 'Solid.2829',
				'gchru' => '',
				'keepOn' => '',
				'dynseed' => $dynseed[1]
			);

			// Setup request
			$request = Request::factory('https://m.ebay.com/signin')
			->method(HTTP_Request::POST)
			->post($post);

			self::humanize($request, TRUE);

			// Execute request
			$response = $request->execute();
		}
	}

	/**
	 * Get list of orders
	 *
	 * @return array
	 */
	public function getOrders()
	{
		$request = Request::factory('http://m.ebay.com/myebay?mfs=tabs&actionName=BUY_OVERVIEW');

		// Let us be human!
		self::humanize($request, TRUE);

		// Execute request
		$response = $request->execute()->body();

		// Items array placeholder
		$items = array();

		// Find links for orders in body
		preg_match_all('#orderDetails\?itemId\=(.*?)\".*?class\=\"srchLnk\"#s', $response, $links);

		foreach ($links[1] as $link)
		{
			// Build request for next request
			$request = Request::factory('http://m.ebay.com/orderDetails?itemId='.str_replace('&amp;', '&', $link));

			// Let us be human!
			self::humanize($request);

			// Execute request
			$response = $request->execute()->body();

			if ($item = $this->processOrder($response))
			{
				// Append to items array
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Process order html
	 *
	 * @param  string
	 * @return array
	 */
	public function processOrder($html)
	{
		$response = array(
			'id' => NULL,
			'description' => NULL,
			'photo' => NULL,
			'tracking_number' => NULL,
			'shipping_type' => NULL,
			'shipping_price' => 0.0,
			'price' => 0.0,
			'quantity' => 0,
		);

		if (preg_match('#<a.*?class\=\"tracking\".*?src\=\"(.*?)\" alt\=\"(.*?)\"#s', $html, $detail))
		{
			$response['photo'] = UTF8::trim(strip_tags($detail[1]));
			$response['description'] = UTF8::trim(strip_tags($detail[2]));
		}

		if (preg_match('#Tracking\:.*?<span.*?>(.*?)<\/span>#s', $html, $tracking))
		{
			$response['tracking_number'] = UTF8::trim(strip_tags($tracking[1]));
		}

		if (preg_match('#Item \#\:.*?<span.*?>(.*?)</span>#s', $html, $id))
		{
			$response['id'] = UTF8::trim(strip_tags($id[1]));
		}

		if (preg_match('#Sold for\:.*?<span.*?>(.*?)<\/span>#s', $html, $price))
		{
			$response['price'] = UTF8::trim(strip_tags($price[1]));
		}

		if (preg_match('#Quantity sold\:.*?<span.*?>(.*?)</span>#s', $html, $quantity))
		{
			$response['quantity'] = intval(UTF8::trim(strip_tags($quantity[1])));
		}

		if (preg_match('#Shipping\:.*?<span.*?>(.*?)<\/span>.*?<span.*?>(.*?)<\/span>#s', $html, $shipping))
		{
			$response['shipping_price'] = UTF8::trim(strip_tags($shipping[1]));
			$response['shipping_type'] = UTF8::trim(strip_tags($shipping[2]));
		}

		return $response;
	}

	/**
	 * List service available methods for user interface
	 *
	 * @return array
	 */
	public function methods()
	{
		return array();
	}

	/**
	 * Show detail page for service
	 *
	 * @return string
	 */
	public function detail()
	{
		return "No details available";
	}

} // End Ebay Service