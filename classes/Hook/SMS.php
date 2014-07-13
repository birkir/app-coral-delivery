<?php

class Hook_SMS {

	public function send_sms($number, $message)
	{
		// Setup request
		$request = Request::factory('http://www.alterna.is/ajax/getCommands.php')
		->query(array(
			'who_to_send_to'  => $number,
			'message_to_send' => $message,
			'sendSMS'         => 1
		));

		// Set cURL options
		$request->client()
		->options(CURLOPT_REFERER, 'http://www.alterna.is/')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1959.0 Safari/537.36')
		->options(CURLOPT_HEADER, 'X-Requested-With: XMLHttpRequest')
		->options(CURLOPT_COOKIEJAR, APPPATH.'cache/alterna.is.cookie');

		// Execute request
		$response = $request->execute()->body();

		if ( ! $response === '1')
		{
			// Setup request
			$request = Request::factory('http://hringdu.is/')
			->method(HTTP_Request::POST)
			->post(array(
				'number'  => $number,
				'email'   => 'info@foo.com',
				'message' => $message,
				'sms'     => 1,
				'submit'  => 'Senda SMS'
			));

			// Set cURL options
			$request->client()
			->options(CURLOPT_REFERER, 'http://www.hringdu.is/')
			->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1959.0 Safari/537.36')
			->options(CURLOPT_COOKIEJAR, APPPATH.'cache/hringdu.is.cookie');

			// Execute request
			$response = $request->execute();
		}
	}

} // End SMS Hook