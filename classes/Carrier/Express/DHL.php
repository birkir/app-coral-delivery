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
	 * Get statuses for tracking number
	 *
	 * @return int
	 */
	public function track()
	{
		// SimpleHTMLDom initialize
		Simplehtmldom::init();

		// Create HTTP Request
		$request = Request::factory('http://www.dhl.is/content/is/en/express/tracking.shtml')
		->query(array(
			'brand' => 'DHL',
			'AWB'   => $this->tracking_number
		));

		// Zero counter
		$count = 0;

		// Act as web browser
		$request->client()
		->options(CURLOPT_COOKIEJAR, APPPATH.'cache/dhl.cookie')
		->options(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36')
		->options(CURLOPT_REFERER, 'http://www.dhl.is/en/express/tracking.html')
		->options(CURLOPT_HEADER, 'Accept-Encoding: gzip,deflate,sdch');

		// Execute request
		$body = $request->execute()->body();

		// Parse signed by if available
		if (preg_match('#Signed for by \: (.*?)<\/td>#s', $body, $signature))
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
			if (empty($this->package->{$type.'_location'}))
			{
				if (preg_match('#'.UTF8::ucfirst($type).' Service Area\:(.*?)<\/span>#s', $body, $location))
				{
					// Explode location string
					$parts = explode('-', str_replace('&nbsp;', '', strip_tags($location[1])));

					// Get location info and coordinates
					$location = Carrier::location_to_coords(UTF8::trim(end($parts)));

					// Set origin and destination location
					$this->package->{$type.'_location'} = $location['country'];
				}
			}
		}

		// Find table
		if (preg_match('#<table.*?>(.*?)</table>#s', $body, $table))
		{
			// Parse HTML string
			$html = str_get_dom($table[0]);
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
					$item['location_raw'] = UTF8::trim($td[2]->getPlainText());
					$item['location'] = Carrier::location_to_coords($item['location_raw']);
					$item['message'] = UTF8::trim($td[1]->getPlainText());
					$item['datetime'] = new DateTime();
					$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
					$item['datetime']->setTimestamp(strtotime($date.' '.UTF8::trim($td[3]->getPlainText()).':00'));

					// Append to array of items
					$items[] = $item;
				}
			}

			foreach (array_reverse($items) as $item)
			{
				if ($this->append_status($item))
				{
					// Parse status item state
					$this->parse_state($item);

					// Increment count status
					$count++;
				}
			}
		}

		if (intval($this->package->state) === Model_Package::LOADING)
		{
			// Set not found if still loading
			$this->package->state = Model_Package::NOT_FOUND;
		}

		return $count;
	}

	/**
	 * Find information in message that relates to state update
	 *
	 * @param  array Status item
	 * @return void
	 */
	public function parse_state($item)
	{
		if (intval($this->package->state) < Model_Package::SHIPPING_INFO_RECEIVED)
		{
			// Set in transit if still loading
			$this->package->state = Model_Package::IN_TRANSIT;
		}

		// Set correct timezone
		$item['datetime']->setTimezone(new DateTimeZone(date_default_timezone_get()));

		// Get message and timestamp
		$msg = UTF8::strtolower($item['message']);
		$ts = $item['datetime']->getTimestamp();

		if ((empty($this->package->registered_at) OR strtotime($this->package->registered_at) > $ts))
		{
			// Set registered at, if not already set
			$this->package->registered_at = $item['datetime']->format('Y-m-d H:i:s');
		}
		else if (empty($this->package->dispatched_at))
		{
			// Set dispatched at, as next status if not already set
			$this->package->dispatched_at = $item['datetime']->format('Y-m-d H:i:s');
		}

		if (preg_match('#customs status updated#s', $msg) AND intval($this->package->state) < Model_Package::IN_CUSTOMS)
		{
			// In customs state
			$this->package->state = Model_Package::IN_CUSTOMS;
		}

		if (preg_match('#awaiting collection#s', $msg) AND intval($this->package->state) < Model_Package::PICK_UP)
		{
			// Set pick up state
			$this->package->state = Model_Package::PICK_UP;
		}

		if (preg_match('#delivered#s', $msg))
		{
			if (intval($this->package->state) < Model_Package::DELIVERED)
			{
				// Set delivered status
				$this->package->state = Model_Package::DELIVERED;
			}

			if ((empty($this->package->completed_at) OR strtotime($this->package->completed_at) < $ts))
			{
				// Set completed at timestamp
				$this->package->completed_at = $item['datetime']->format('Y-m-d H:i:s');
			}
		}
	}

} // End DHL Express Carrier