<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Core Carrier based on 17track
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_Core extends Carrier {

	/**
	 * @var string 17 Track hash algorithm salt
	 */
	protected $_hash = '{EDFCE98B-1CE6-4D87-8C4A-870D140B62BA}0{EDFCE98B-1CE6-4D87-8C4A-870D140B62BA}www.17track.net';

	/**
	 * @var array Services needing flip
	 */
	protected $_countries_flip = array(
		'RO' => TRUE,
		'GB' => FALSE
	);

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		if ( ! Fragment::load('17track_captcha', Date::HOUR))
		{
			// Solve captcha on 1 hour interval
			$this->solveCaptcha();

			Fragment::save();
		}

		// Create HTTP Request
		$request = Request::factory('http://s1.17track.net/Rest/HandlerTrackPost.ashx')
		->query(array(
			'callback' => 'data',
			'lo'       => 'www.17track.net',
			'pt'       => '0',
			'num'      => $this->package->tracking_number,
			'hs'       => openssl_digest($this->package->tracking_number.$this->_hash, 'md5')
		));

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_COOKIEJAR, APPPATH.'cache/cookies/'.sha1('www.17track.net').'.cookie');
		$client->options(CURLOPT_FOLLOWLOCATION, TRUE);
		$client->options(CURLOPT_SSL_VERIFYPEER, FALSE);
		$client->options(CURLOPT_USERAGENT, Carrier::$user_agent);
		$client->options(CURLOPT_REFERER, 'http://17track.net');

		// Execute request and get response body
		$response = $request->execute()->body();

		return $response;
	}

	/**
	 * Get status items categorized by direction
	 *
	 * @return array
	 */
	public function getStatusItems($response)
	{
		// Setup returned items array
		$items = array(
			Carrier::ORIGIN      => array(),
			Carrier::DESTINATION => array()
		);

		// Get first two codes in tracking number
		$first = UTF8::substr($this->package->tracking_number, 0, 2);

		// Get last two codes in tracking number
		$last = UTF8::substr($this->package->tracking_number, -2);

		if (substr($response, 0, 4) === 'data')
		{
			// Remove callback fn and parse JSON
			$data = json_decode(substr(substr($response, 5), 0, strlen($response) - 6));

			if ($data->msg === 'Ok' AND $data->ret == 1)
			{
				if (empty($this->package->origin_country_id) AND ! empty($data->dat->d))
				{
					// Set origin country id
					$this->package->origin_country_id = ORM::factory('Country', array('external_id' => $data->dat->d))->id;
				}

				if (empty($this->package->destination_country_id) AND ! empty($data->dat->e))
				{
					// Set destination country id
					$this->package->destination_country_id = ORM::factory('Country', array('external_id' => $data->dat->e))->id;
				}

				foreach (array(Carrier::ORIGIN => 'x', Carrier::DESTINATION => 'y') as $dir => $i)
				{
					foreach ($data->dat->{$i} as $status)
					{
						// Explode locations
						$parts = explode(',', $status->b);

						foreach ($this->_countries_flip as $code => $atFirst)
						{
							if (($atFirst AND $first === $code) OR ( ! $atFirst AND $last === $code))
							{
								$tmp = $parts[0];
								$parts[0] = $parts[1];
								$parts[1] = $tmp;
								break;
							}
						}

						// Create status item
						$item = array();
						$item['location'] = Carrier::getLocation(Arr::get($parts, 0));
						$item['message'] = UTF8::trim(join(',', array_slice($parts, 1)));
						$item['datetime'] = new DateTime();
						$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
						$item['datetime']->setTimestamp(strtotime($status->a.':00'));
						$item['datetime']->setTimezone(new DateTimeZone(date_default_timezone_get()));

						// Append item to items array
						$items[$dir][] = $item;
					}
				}
			}

			if ($this->package->state === Model_Package::LOADING)
			{
				// Set in transit if still loading
				$this->package->state = Model_Package::NOT_FOUND;
			}
		}

		return $items;
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
		return preg_match('/(collection|collected)/s', $message);
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
		return preg_match('/(despatch|dispatch|depart|left.*destination)/s', $message);
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
		return preg_match('/(unsuccessful delivery|failed delivery)/s', $message);
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
		return preg_match('/(final delivery|product delivered)/s', $message);
	}

	/**
	 * Solve 17track captcha
	 *
	 * @todo   reconstruct with comments and Request class
	 * @param  int   $attempt
	 * @return void
	 */
	public static function solveCaptcha($attempt = 0)
	{
		// Get config array
		$config = Arr::get(Kohana::$config->load('carrier'), 'core');

		// Only try three times to solve captcha
		if ($attempt > 2) return FALSE;

		// Get captcha image from 17track
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.17track.net/f/MyCaptchaHandler.ashx?get=image&d=&d=0.1563');
		curl_setopt($ch, CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
		curl_setopt($ch, CURLOPT_REFERER, 'http://17track.net');
		$body = curl_exec($ch);

		// Create API Request
		$request = Request::factory('http://api.dbcapi.me/api/captcha')
		->method(HTTP_Request::POST)
		->post(array(
			'username'    => Arr::get($config, 'captcha_user'),
			'password'    => Arr::get($config, 'captcha_pass'),
			'captchafile' => 'base64:'.base64_encode($body) 
		));

		// Execute request and get poll location
		$poll = $request->execute()->headers('location');

		// Set result as null by default
		$result = NULL;

		for ($i = 0; $i < 3; $i++)
		{
			if ($result !== NULL) continue;

			// Wait 5 seconds
			sleep(5);

			try
			{
				$data = json_decode(Request::factory($poll)
				->headers('accept', 'application/json')
				->execute()
				->body());

				if ( ! $data->is_correct)
				{
					// Try another captcha with bumped attempt counter
					return self::solve_captcha($attempt + 1);
				}

				if ( ! empty($data->text))
				{
					// Set result data
					$result = $data;
				}
			}
			catch (Exception $e) {}
		}

		// Solve 17track captcha
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.17track.net/f/MyCaptchaHandler.ashx?get=validationresult&i='.UTF8::strtoupper($result->text).'&d=0.1563');
		curl_setopt($ch, CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie');
		curl_setopt($ch, CURLOPT_COOKIEFILE, APPPATH.'cache/17track.cookie');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
		curl_setopt($ch, CURLOPT_REFERER, 'http://17track.net');
		$body = curl_exec($ch);

		if ($body !== 'true')
		{
			// Report as dead captcha
			$request = Request::factory('http://api.dbcapi.me/api/captcha/'.$result->captcha.'/report')
			->method(HTTP_Request::POST)
			->post(array(
				'username' => Arr::get($config, 'captcha_user'),
				'password' => Arr::get($config, 'captcha_pass')
			));
		}

		return TRUE;
	}

} // End Core Carrier