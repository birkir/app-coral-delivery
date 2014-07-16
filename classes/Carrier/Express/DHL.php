<?php
/**
 * DHL Express Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_Express_DHL extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered = '/shipment picked up/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched = '/(processed at|departed)/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms = '/customs status updated/s';

	/**
	 * @var string Regex for out for delivery check
	 */
	public $matchOutForDelivery = '/with delivery courier/s';

	/**
	 * @var string Regex delivery exception check
	 */
	public $matchFailedAttempt = '/recipient refused delivery/s';

	/**
	 * @var string Regex for awaiting collection check
	 */
	public $matchPickUp = '/(awaiting collection|available upon receipt of payment)/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered = '/delivered/s';

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		// Create HTTP Request
		$request = Request::factory('http://www.dhl.is/content/is/en/express/tracking.shtml')
		->query(array(
			'brand' => 'DHL',
			'AWB'   => $this->package->tracking_number
		));

		// Get Request client
		$client = $request->client();

		// Set cURL options
		$client->options(CURLOPT_USERAGENT,  Carrier::$user_agent);
		$client->options(CURLOPT_REFERER, 'http://www.dhl.is/en/express/tracking.html');
		$client->options(CURLOPT_HEADER, 'Accept-Encoding: gzip,deflate,sdch');
		$client->options(CURLOPT_COOKIEJAR,  APPPATH.'cache/cookies/'.sha1('www.dhl.com').'.cookie');
		$client->options(CURLOPT_COOKIEFILE, APPPATH.'cache/cookies/'.sha1('www.dhl.com').'.cookie');

		// Execute request and get body
		$response = $request->execute()->body();

		return $response;
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

		// Parse signed by if available
		if (preg_match('#Signed for by \: (.*?)<\/td>#s', $response, $signature))
		{
			if ( ! empty($signature[1]))
			{
				// Set signature extra
				$this->package->extras('signature', $signature[1]);
			}
		}

		// Parse origin location
		foreach (array('origin', 'destination') as $type)
		{
			if (empty($this->package->{$type.'_country_id'}))
			{
				if (preg_match('#'.UTF8::ucfirst($type).' Service Area\:(.*?)<\/span>#s', $response, $location))
				{
					// Explode location string
					$parts = explode('-', str_replace('&nbsp;', '', strip_tags($location[1])));

					// Get location info and coordinates
					$location = Carrier::getLocation(UTF8::trim(end($parts)));

					// Get country row
					$country = ORM::factory('Country', array('name' => Arr::get($location, 'country')));

					if ($country->loaded())
					{
						// Set origin and destination location
						$this->package->{$type.'_location'} = $location['country'];
					}
				}
			}
		}

		if (preg_match('#<table.*?>(.*?)</table>#s', $response, $table))
		{
			// Parse response html dom
			$dom = new HTML_Parser_HTML5($response);

			// Get root node of html parser
			$html = $dom->root;

			// Set date buffer
			$date = NULL;

			foreach ($html('thead, tbody') as $item)
			{
				if ($item->tag === 'thead' AND empty($item->attributes))
				{
					// Get date from thead
					$date = date('Y-m-d', strtotime(UTF8::trim($item('th:eq(0)')[0]->getPlainText())));
				}

				if ($item->tag === 'tbody')
				{
					// Find table cells
					$td = $item('td');

					// Create item
					$item = array();
					$item['location'] = Carrier::getLocation(UTF8::trim($td[2]->getPlainText()));
					$item['message'] = UTF8::trim($td[1]->getPlainText());
					$item['datetime'] = new DateTime();
					$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
					$item['datetime']->setTimestamp(strtotime($date.' '.UTF8::trim($td[3]->getPlainText()).':00'));

					// Append to array of items
					$items[] = $item;
				}
			}
		}

		if ($this->package->state === Model_Package::LOADING)
		{
			// Set not found if still loading
			$this->package->state = Model_Package::NOT_FOUND;
		}

		return $items;
	}

} // End DHL Express Carrier