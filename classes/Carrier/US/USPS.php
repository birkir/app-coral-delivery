<?php defined('SYSPATH') or die('No direct script access.');
/**
 * USPS Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_US_USPS extends Carrier {

	/**
	 * Parse datetime string from usps.com
	 *
	 * @param  string   Text to parse
	 * @return DateTime
	 */
	private function parse_datetime($str = NULL, $timezone = NULL)
	{
		// Create DateTime
		$dt = new DateTime();

		// Set timezone
		if ( ! empty($timezone))
		{
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		// Strip tags
		$str = strip_tags($str);

		// Explode by comma
		$parts = explode(',', $str);

		// Extract 
		$monthWithDate = UTF8::trim($parts[0]);

		// Extract year
		$year = UTF8::trim($parts[1]);

		// Set date
		$dt->setTimestamp(strtotime($monthWithDate.' '.$year));

		if (isset($parts[2]))
		{
			// Extract time
			$time = explode(':', UTF8::trim($parts[2]));
			$minutes = (intval($time[0]) * 60) + intval(UTF8::trim(substr($time[1], 0, 2))) + ((substr($time[1], -2) === 'pm') ? 720 : 0);
			$hours = floor($minutes / 60);
			$minutes -= ($hours * 60);
			
			// Set time
			$dt->setTime($hours, $minutes);
		}

		return $dt;
	}

	/**
	 * Get results for tracking number
	 *
	 * @return void
	 */
	public function track()
	{
		// Create Request URL
		$url = 'https://tools.usps.com/go/TrackConfirmAction!input.action?tRef=qt&tLc=0&tLabels='.$this->tracking_number;

		// Create request and execute it
		$response = Request::factory($url)->execute();

		// Extract body from response
		$body = $response->body();

		// How many records passed
		$count = 0;

		if (preg_match('#not available#s', $body))
		{
			return FALSE;
		}

		// Find status
		preg_match('#<h4>Postal Product:</h4>.*<li>(.*)\n\t+</li>#s', $body, $status);
		$status = UTF8::trim($status[1]);

		// Find feature
		if (preg_match('#<div class="feature">(.*?)</div>#s', $body, $feature))
		{
			$feature = UTF8::trim(strip_tags($feature[1]));
			$this->package->extra('feature', $feature);
		}


		// Find results table body
		if (preg_match('#<table.*id="tc-hits">.*</thead>(.*)</tbody>#s', $body, $table))
		{
			// Find all rows in table body
			preg_match_all('#<tr.*?>(.*?)</tr>#s', $table[1], $rows);

			foreach ($rows[1] as $row)
			{
				// Setup item
				$item = array();

				// Get location
				if (preg_match('#<td class="location">(.*?)</td>#s', $row, $location))
				{
					$item['location_raw'] = UTF8::trim(html_entity_decode(strip_tags($location[1])));
					$item['location'] = Carrier::location_to_coords($item['location_raw']);
				}

				// Get message
				if (preg_match('#<td class="status">(.*?)</td>#s', $row, $message))
				{
					$item['message'] = UTF8::trim(strip_tags($message[1]));
				}

				// Get date time of status
				if (preg_match('#<td class="date-time">(.*?)</td>#s', $row, $datetime))
				{
					$item['datetime'] = $this->parse_datetime($datetime[1], isset($item['location']['timezone']) ? $item['location']['timezone'] : NULL);
				}

				if ( ! empty($item))
				{
					// Append status to package
					if ($this->append_status($item))
					{
						// Increment statses
						$count++;
					}

				}
			}
		}

		return $count;
	}

} // End USPS Carrier