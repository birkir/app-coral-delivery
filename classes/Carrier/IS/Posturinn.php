<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Pósturinn Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_IS_Posturinn extends Carrier {

	/**
	 * @var array Month names
	 */
	protected $_months = array('JAN', 'FEB', 'MAR', 'APR', 'MAÍ', 'JÚN', 'JÚL', 'AGÚ', 'SEP', 'OKT', 'NÓV', 'DES');

	/**
	 * Parse location if internal status codes
	 *
	 * @param  string Location
	 * @return array
	 */
	private function parse_location($str = NULL)
	{
		if (preg_match('#(ISREKA|Reykjavík|Tollmiðlun|Heimkeyrsla|Bréfaflokkunarvél)#s', $str))
		{
			// Return Pósturinn head quarters location
			return array(
				'name'        => $str,
				'coordinates' => '64.1291751,-21.7966282',
				'timezone'    => 'Atlantic/Reykjavik',
				'country'     => 'Iceland'
			);
		}

		return Carrier::location_to_coords($str);
	}

	/**
	 * Get results for tracking number
	 *
	 * @return void
	 */
	public function track()
	{
		// Create HTTP Request
		$request = Request::factory('http://www.posturinn.is');

		// Set cURL options
		$request->client()
		->options(CURLOPT_RETURNTRANSFER, TRUE)
		->options(CURLOPT_FOLLOWLOCATION, TRUE)
		->options(CURLOPT_SSL_VERIFYPEER, FALSE);

		// Execute request and get body
		$body = $request->execute()->body();

		// Count status
		$count = 0;

		// Setup post data array
		$post = array(
			'ctl01$ctl10$txtLabel' => $this->tracking_number,
			'ctl01$ctl10$Button1'  => 'Finna'
		);

		// List parameters wanted
		$params = array('RadScriptManager1_TSM', '__EVENTTARGET', '__EVENTARGUMENT', '__VIEWSTATE', 'pathinfo', 'ctl01$ctl02$ctl00$query_string');

		foreach ($params as $param)
		{
			if (preg_match('/'.$param.'\" value=\"(.*)\"/i', $body, $value))
			{
				// Extract value and push to post array
				$post[$param] = Arr::get($value, 1, NULL);
			}
		}

		// Create next HTTP Request in series
		$request = Request::factory('http://www.postur.is/desktopdefault.aspx')
		->method(Request::POST)
		->post($post);

		// Set cURL options
		$request->client()
		->options(CURLOPT_RETURNTRANSFER, TRUE)
		->options(CURLOPT_FOLLOWLOCATION, TRUE)
		->options(CURLOPT_SSL_VERIFYPEER, FALSE)
		->options(CURLOPT_COOKIEJAR, APPPATH.'cache/posturinn.is.cookies');

		// Execute request and get body
		$body = $request->execute()->body();

		if (preg_match('#finnst ekki#s', $body) AND intval($this->package->state) < Model_Package::SHIPPING_INFO_RECEIVED)
		{
			// Set package state
			$this->package->state = Model_Package::NOT_FOUND;

			return FALSE;
		}

		// Which location
		$loc = ($this->type === Carrier::DESTINATION) ? $this->package->destination_location : $this->package->origin_location;

		if (empty($loc) OR $loc === 'Unknown')
		{
			// Set location to Iceland
			$this->package->{($this->type === Carrier::DESTINATION) ? 'destination_location' : 'origin_location'} = 'Iceland';
		}

		// Get months as keys
		$months = array_flip($this->_months);

		// Find results table body
		if (preg_match("#ctl01_ctl08_divOuput(.*?)</table>#s", $body, $table))
		{

			// Find all rows in table body
			preg_match_all("#<tr>(.*?)</tr>#s", $table[1], $rows);

			foreach ($rows[0] as $row)
			{
				// Find all table cells in row
				preg_match_all("#<td.*?>(.*?)</td>#s", $row, $cells);

				// No empty table rows, pretty please
				if (count($cells[1]) === 0)
					continue;

				// Extract day of month
				$dayOfMonth = trim(strip_tags($cells[1][0]));

				// Explode date by brake
				$date = explode("<br />", trim($cells[1][1]));

				// Get month as number
				$monthOfYear = intval(isset($months[trim($date[0])]) ? $months[trim($date[0])] : 0) + 1;

				// Extract time
				$timeOfDay = trim($date[1]);

				// Get current year
				$year = date('Y');

				// If month exceeds current month, set last year
				if (intval(date('n')) < $monthOfYear)
				{
					$year--;
				}

				// Create the timestamp
				$timestamp = strtotime($year.'-'.$monthOfYear.'-'.$dayOfMonth.' '.(empty($timeOfDay) ? '22:00' : $timeOfDay).':00');

				// Get location and description
				$locdesc = explode('<br />', isset($cells[1][2])? $cells[1][2] : '');

				// Extract location
				$location = preg_replace('/\s+/', ' ', trim(strip_tags($locdesc[0])));

				// Extract message
				$message = trim(strip_tags(isset($locdesc[1]) ? $locdesc[1] : ''));

				// Setup item
				$item = array();
				$item['location_raw'] = UTF8::trim(html_entity_decode(strip_tags($location)));
				$item['location'] = $this->parse_location($item['location_raw']);
				$item['message'] = UTF8::trim(strip_tags($message));
				$item['datetime'] = new DateTime();
				$item['datetime']->setTimeZone(new DateTimeZone('Atlantic/Reykjavik'));
				$item['datetime']->setTimestamp($timestamp);

				if ( ! empty($item))
				{
					// Append status to package
					if ($this->append_status($item))
					{
						if (intval($this->package->state) < Model_Package::SHIPPING_INFO_RECEIVED)
						{
							// Set in transit if still loading
							$this->package->state = Model_Package::IN_TRANSIT;
						}

						if (preg_match('#Tollmiðlun#', $item['message']) AND intval($this->package->state) < Model_Package::IN_CUSTOMS)
						{
							// Set package state
							$this->package->state = Model_Package::IN_CUSTOMS;
						}

						if (preg_match('#Fór frá erlendri#', $item['message']) AND empty($this->package->dispatched_at))
						{
							// Set package dispatched datetime
							$this->package->dispatched_at = $item['datetime']->format('Y-m-d H:i:s');
						}

						if (preg_match('#Póstlagt#', $item['message']) AND empty($this->package->registered_at))
						{
							// Set package registered datetime
							$this->package->registered_at = $item['datetime']->format('Y-m-d H:i:s');
						}

						if (preg_match('#Bréfaflokkunarvél#', $item['location_raw']) AND preg_match('#Tilkynning#', $item['message']) AND intval($this->package->state) < Model_Package::PICK_UP)
						{
							// Set package pickup state
							$this->package->state = Model_Package::PICK_UP;
						}

						if (preg_match('#[Afhent|Skannað til afhendingar]#', $item['message']))
						{
							if (empty($this->package->completed_at))
							{
								// Set package completed at datetime
								$this->package->completed_at = $item['datetime']->format('Y-m-d H:i:s');
							}

							if (intval($this->package->state) <= Model_Package::PICK_UP)
							{
								// Also set state if not greater
								$this->package->state = Model_Package::DELIVERED;
							}
						}

						// Increment count
						$count++;
					}
				}
			}

			if (preg_match('#Toll\-sendingarnúmer\:(.*?)<\/th>#s', $table[1], $tmp))
			{
				// Set customs number as extra
				$this->package->extras('customs_number', UTF8::trim($tmp[1]));
			}

			if (preg_match('#(Tollskyld .*?)<\/th>#s', $table[1], $tmp))
			{
				// Set customs type as extra
				$this->package->extras('customs_type', UTF8::trim($tmp[1]));
			}

			if (preg_match('#Gjöld kr\. (.*?)<\/th>#s', $table[1], $tmp))
			{
				// Set payment as extra
				$this->package->extras('payment', array('amount' => intval(str_replace('.', NULL, $tmp[1])), 'currency' => 'ISK'));
			}

			if (preg_match('#Þyngd ([0-9\,]*) KG<\/th>#s', $table[1], $tmp))
			{
				// Set package weight
				$this->package->weight = floatval(str_replace(',', '.', $tmp[1])) * 1000;
			}
		}

		return $count;
	}

} // End Posturinn IS Carrier