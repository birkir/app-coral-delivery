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
	 * Creates and returns a new carrier driver.
	 * 
	 * @chainable
	 * @param   string  $package  Loaded package
	 * @return  Carrier
	 */
	public static function factory($package = NULL, $type = 0)
	{
		if ( ! $package->loaded())
		{
			throw new Kohana_Exception('Carrier not defined in :name package',
					array(':name' => $package->tracking_number));
		}

		// Get which carrier
		$carrier = ($type === Carrier::DESTINATION) ? $package->destination_carrier : $package->origin_carrier;

		// Set the driver class name
		$driver = empty($carrier->driver) ? 'Carrier' : 'Carrier_'.ucfirst($carrier->driver);

		// Create the carrier driver
		$driver = new $driver($package, $carrier, $type);

		return $driver;
	}

	/**
	 * Location to Cordinates converter
	 *
	 * @param  string Location
	 * @return string Cordinates
	 */
	public static function location_to_coords($str = NULL)
	{
		if ( ! isset(Carrier::$coords[$str]))
		{
			// Defaults to original value
			$result = array('name' => $str, 'coordinates' => NULL);

			// Create google maps geocode request
			$request = Request::factory('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($str));

			// Execute request for response
			$response = $request->execute();

			// Convert body JSON to Array
			$data = json_decode($response->body());

			if (isset($data->results[0]) AND $data->status === 'OK')
			{
				$res = $data->results[0];

				foreach ($res->address_components as $cmp)
					foreach ($cmp->types as $type)
						if ($type === 'country')
							$result['country'] = $cmp->long_name;

				$result['name'] = $res->formatted_address;
				$result['coordinates'] = $res->geometry->location->lat.','.$res->geometry->location->lng;
				$result['timezone'] = Carrier::coordinates_to_timezone($result['coordinates']);
			}

			Carrier::$coords[$str] = $result;
		}

		return Carrier::$coords[$str];
	}

	/**
	 * Location to timezone converter
	 *
	 * @param  string Location
	 * @return string Timezone
	 */
	public static function coordinates_to_timezone($coords = NULL)
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

	/**
	 * Get list of countries
	 *
	 * @return array
	 */
	public static function countries()
	{
		// Remove duplicates
		$countries = array_unique(self::$_countries);

		return $countries;
	}

	/**
	 * Convert country id to name
	 *
	 * @param  int    Country #
	 * @return string
	 */
	public static function country($id) {
		return self::$_countries[$id];
	}

	/**
	 * @var string  17 Track hash algorithm salt
	 */
	protected $_hash = '{EDFCE98B-1CE6-4D87-8C4A-870D140B62BA}0{EDFCE98B-1CE6-4D87-8C4A-870D140B62BA}www.17track.net';

	/**
	 * Constructs a new carrier driver for package if loaded.
	 *
	 * @param   string  $package  Loaded package
	 */
	public function __construct($package, $carrier, $type = 0)
	{
		// Assign package to driver
		$this->package = $package;

		// Assign carrier to driver
		$this->carrier = $carrier;

		// Assign carrier type
		$this->type = $type;

		if ($this->package->loaded())
		{
			// Set tracking number
			$this->tracking_number = ($this->type === Carrier::DESTINATION AND ! empty($package->destination_tracking_number)) ? $package->destination_tracking_number : $package->tracking_number;
		}
	}

	public function need_flip()
	{
		$no = $this->tracking_number;
		$first = substr($no, 0, 2);
		$last = substr($no, -2);

		if ($first === 'RO' AND $last === 'GB')
			return TRUE;

		return FALSE;
	}

	/**
	 * Get results for tracking number
	 *
	 * @return void
	 */
	public function track()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://s1.17track.net/Rest/HandlerTrackPost.ashx?callback=data&lo=www.17track.net&pt=0&num='.$this->tracking_number.'&hs='.openssl_digest($this->tracking_number.$this->_hash, 'md5'));
		curl_setopt($ch, CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
		curl_setopt($ch, CURLOPT_REFERER, 'http://17track.net');
		$body = curl_exec($ch);

		// Count
		$count = 0;

		if (substr($body, 0, 4) === 'data')
		{
			// Remove callback fn and parse JSON
			$data = json_decode(substr(substr($body, 5), 0, strlen($body) - 6));

			if ($data->msg === 'Ok' AND $data->ret == 1)
			{
				if (empty($this->package->origin_location) OR $this->package->origin_location === 'Unknown')
				{
					// Set origin location
					$this->package->origin_location = Arr::get(self::$_countries, intval($data->dat->d), 'Unknown');
				}

				if (empty($this->package->destination_location) OR $this->package->destination_location === 'Unknown')
				{
					// Set destination location
					$this->package->destination_location = Arr::get(self::$_countries, intval($data->dat->e), 'Unknown');

					if ($this->package->destination_location === 'Iceland')
					{
						// Set Pósturinn as driver
						$this->package->destination_carrier_id = ORM::factory('Carrier', array('driver' => 'IS_Posturinn'))->id;
					}
				}

				// Which items to get
				$items = ($this->type === Carrier::DESTINATION) ? $data->dat->y : $data->dat->x;

				foreach ($items as $status)
				{
					// Explode locations
					$parts = explode(',', $status->b);

					// Detect if we need to flip location/message fields
					if ($this->need_flip())
					{
						$tmp = $parts[0];
						$parts[0] = $parts[1];
						$parts[1] = $tmp;
					}

					// Create status item
					$item = array();
					$item['location_raw'] = $parts[0];
					$item['location'] = Carrier::location_to_coords($item['location_raw']);
					$item['message'] = UTF8::trim(join(',', array_slice($parts, 1)));
					$item['datetime'] = new DateTime();
					$item['datetime']->setTimeZone(new DateTimeZone(Arr::get($item['location'], 'timezone', 'Atlantic/Reykjavik')));
					$item['datetime']->setTimestamp(strtotime($status->a.':00'));

					// Insert status to database
					if ($this->append_status($item))
					{
						// Parse state
						$this->parse_state($item);

						// Increment count status
						$count++;
					}
				}
			}

			if (intval($this->package->state) === Model_Package::LOADING)
			{
				// Set in transit if still loading
				$this->package->state = Model_Package::NOT_FOUND;
			}
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

		// Get message, sometimes location = message
		$msg = UTF8::strtolower(empty($item['message']) ? $item['location_raw'] : $item['message']);

		$find = array(
			'collection',
			'collected'
		);

		foreach ($find as $word)
		{
			if (preg_match('/'.$word.'/', $msg) AND (empty($this->package->registered_at) OR strtotime($this->package->registered_at) > $item['datetime']->getTimestamp()))
			{
				// Set dispatched at
				$this->package->registered_at = $item['datetime']->format('Y-m-d H:i:s');
				break;
			}
		}

		$find = array(
			'despatch',
			'dispatch',
			'depart',
			'left.*destination'
		);

		foreach ($find as $word)
		{
			if (preg_match('/'.$word.'/', $msg) AND (empty($this->package->dispatched_at) OR strtotime($this->package->dispatched_at) > $item['datetime']->getTimestamp()))
			{
				// Set dispatched at
				$this->package->dispatched_at = $item['datetime']->format('Y-m-d H:i:s');
				break;
			}
		}

		$find = array(
			'final delivery',
			'product delivered'
		);

		foreach ($find as $word)
		{
			if (preg_match('/'.$word.'/', $msg) AND empty($this->package->completed_at))
			{
				// Set completed at
				$this->package->completed_at = $item['datetime']->format('Y-m-d H:i:s');
				break;
			}
		}
	}

	/**
	 * Insert current status item to database with package object loaded.
	 *
	 * @param  array   Status item
	 * @return integer Inserted statuses count
	 */
	public function append_status($item)
	{
		// Create identifier for status
		$identifier = sha1($item['location_raw'].$item['datetime']->getTimestamp().$item['message']);

		if ( ! $this->package->status->where('identifier', '=', $identifier)->find()->loaded())
		{
			// Convert timezone to application timezone
			$item['datetime']->setTimezone(new DateTimeZone(date_default_timezone_get()));

			try
			{
				// Create package status object
				$status = ORM::factory('Package_Status');
				$status->package_id = $this->package->id;
				$status->carrier_id = $this->carrier->id;
				$status->direction = $this->type;
				$status->identifier = $identifier;
				$status->raw = json_encode($item);
				$status->timestamp = $item['datetime']->format('Y-m-d H:i:s');
				$status->location = $item['location']['name'];
				$status->coordinates = $item['location']['coordinates'];
				$status->message = $item['message'];
				$status->save();

				return TRUE;
			}
			catch (ORM_Validation_Exception $e)
			{
				// Do nothing here
			}
		}

		return FALSE;
	}

	/**
	 * Solve 17track captcha
	 *
	 * @return void
	 */
	public static function solve_captcha($attempt = 0)
	{
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
			'username'    => 'SolidR53',
			'password'    => 'Solid.90',
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
				'username'    => 'SolidR53',
				'password'    => 'Solid.90'
			));
		}

		return TRUE;
	}

	/**
	 * @var array Country list
	 */
	protected static $_countries = array(
		1011 => 'Aland Island',
		1021 => 'Afghanistan',
		1031 => 'Albania',
		1041 => 'Algeria',
		1051 => 'Andorra',
		1061 => 'Angola',
		1071 => 'Anguilla',
		1081 => 'Antarctica',
		1091 => 'Ascension Island',
		1101 => 'Antigua And Barbuda',
		1111 => 'Netherlands Antilles',
		1121 => 'Argentina',
		1131 => 'Armenia',
		1141 => 'Aruba',
		1151 => 'Australia',
		1161 => 'Austria',
		1171 => 'Azerbaijan',
		1181 => 'Azores and Madeira',
		2011 => 'Bahamas',
		2021 => 'Bahrain',
		2031 => 'Bangladesh',
		2033 => 'Bangladesh',
		2041 => 'Barbados',
		2051 => 'Belarus',
		2061 => 'Belgium',
		2071 => 'Belize',
		2081 => 'Benin',
		2091 => 'Bermuda',
		2101 => 'Bhutan',
		2111 => 'Bolivia',
		2121 => 'Bosnia And Herzegovina',
		2131 => 'Botswana',
		2141 => 'Bouvet Island',
		2151 => 'Brazil',
		2161 => 'Brunei',
		2171 => 'Bulgaria',
		2181 => 'Burkina Faso',
		2191 => 'Burundi',
		3011 => 'China',
		3013 => 'China',
		3021 => 'Cambodia',
		3031 => 'Cameroon',
		3041 => 'Canada',
		3051 => 'Canary Islands',
		3061 => 'Cape Verde',
		3071 => 'Cayman Islands',
		3081 => 'Central African Republic',
		3101 => 'Chile',
		3111 => 'Christmas Island',
		3121 => 'Côte d\'Ivoire',
		3123 => 'Côte d\'Ivoire',
		3131 => 'Colombia',
		3141 => 'Comoros',
		3151 => 'Republic of Congo',
		3161 => 'Democratic Republic of Congo',
		3171 => 'Cook Islands',
		3181 => 'Costa Rica',
		3191 => 'Croatia',
		3201 => 'Cuba',
		3211 => 'Cyprus',
		3221 => 'Czech',
		3231 => 'Chad',
		3241 => 'Cocos (Keeling) Islands',
		3251 => 'Caroline Islands',
		4011 => 'Denmark',
		4021 => 'Djibouti',
		4031 => 'Dominica',
		4041 => 'Dominican',
		5011 => 'Ecuador',
		5021 => 'Egypt',
		5031 => 'United Arab Emirates',
		5041 => 'Estonia',
		5051 => 'Ethiopia',
		5061 => 'Eritrea',
		5071 => 'Equatorial Guinea',
		5081 => 'East Timor',
		6011 => 'Falkland Islands',
		6021 => 'Faroe Islands',
		6031 => 'Fiji',
		6041 => 'Finland',
		6051 => 'France',
		6053 => 'France',
		6061 => 'Metropolitan',
		6071 => 'Guiana',
		7011 => 'Gabon',
		7021 => 'Gambia',
		7031 => 'Georgia',
		7041 => 'Germany',
		7051 => 'Ghana',
		7061 => 'Gibraltar',
		7071 => 'Greece',
		7081 => 'Greenland',
		7091 => 'Grenada',
		7101 => 'Guadeloupe',
		7103 => 'Guadeloupe',
		7111 => 'Guam',
		7121 => 'Guatemala',
		7131 => 'Republic Of Guinea',
		7141 => 'Guyana',
		7151 => 'Guernsey',
		7161 => 'Guinea Bissau',
		8011 => 'Hong Kong',
		8021 => 'Haiti',
		8041 => 'Honduras',
		8051 => 'Hungary',
		9011 => 'Iceland',
		9021 => 'India',
		9031 => 'Indonesia',
		9041 => 'Iran',
		9051 => 'Ireland',
		9061 => 'Israel',
		9071 => 'Italy',
		9081 => 'Iraq',
		9091 => 'Isle Of Man',
		10011 => 'Jamaica',
		10021 => 'Japan',
		10031 => 'Jordan',
		10041 => 'Jersey Island',
		10043 => 'Jersey Island',
		11011 => 'Kazakhstan',
		11021 => 'Kenya',
		11031 => 'United Kingdom',
		11033 => 'United Kingdom',
		11041 => 'Kiribati',
		11051 => 'Korea',
		11061 => 'Democratic People\'s Republic of Kore',
		11071 => 'Kosovo',
		11081 => 'Kuwait',
		11091 => 'Kyrgyzstan',
		12011 => 'Laos',
		12021 => 'Latvia',
		12031 => 'Lebanon',
		12041 => 'Lesotho',
		12051 => 'Liberia',
		12061 => 'Libya',
		12071 => 'Liechtenstein',
		12081 => 'Lithuania',
		12091 => 'St. Lucia',
		12101 => 'Luxembourg',
		13011 => 'Macau',
		13021 => 'Macedonia',
		13031 => 'Madagascar',
		13041 => 'Malawi',
		13051 => 'Malaysia',
		13052 => 'Malaysia',
		13061 => 'Maldives',
		13071 => 'Mali',
		13081 => 'Malta',
		13091 => 'Mariana Islands',
		13101 => 'Marshall',
		13111 => 'Martinique',
		13113 => 'Martinique',
		13121 => 'Mauritania',
		13131 => 'Mauritius',
		13141 => 'Mexico',
		13151 => 'Micronesia',
		13161 => 'Moldova',
		13171 => 'Monaco',
		13181 => 'Mongolia',
		13191 => 'Montenegro',
		13201 => 'Montserrat',
		13211 => 'Morocco',
		13213 => 'Morocco',
		13221 => 'Mozambique',
		13231 => 'Myanmar',
		13241 => 'Mayotte',
		14011 => 'Namibia',
		14021 => 'Nauru',
		14031 => 'Nepal',
		14041 => 'Netherlands',
		14051 => 'New Caledonia',
		14061 => 'New Zealand',
		14071 => 'Nicaragua',
		14081 => 'Norway',
		14091 => 'Niger',
		14101 => 'Nigeria',
		14111 => 'Niue',
		14121 => 'Norfolk Island',
		14131 => 'Northern Cyprus',
		15011 => 'Oman',
		16011 => 'Pakistan',
		16021 => 'Palestine',
		16031 => 'Panama',
		16041 => 'Papua New Guinea',
		16051 => 'Paraguay',
		16061 => 'Peru',
		16071 => 'Philippines',
		16081 => 'Poland',
		16091 => 'Polynesia',
		16101 => 'Portugal',
		16111 => 'Puerto Rico',
		16121 => 'Pitcairn Islands',
		16131 => 'St. Pierre And Miquelon',
		16141 => 'Palau',
		17011 => 'Qatar',
		18011 => 'Reunion Island',
		18021 => 'Romania',
		18031 => 'Russia',
		18041 => 'Rwanda',
		19011 => 'Saint Christopher',
		19021 => 'Saint Vincent And Grenadines',
		19031 => 'Salvador',
		19041 => 'Samoa',
		19051 => 'San Marino',
		19061 => 'Sao Tome And Principe',
		19071 => 'Saudi Arabia',
		19081 => 'Senegal',
		19091 => 'Serbia',
		19101 => 'South Sandwich Islands',
		19111 => 'Seychelles',
		19121 => 'Sierra Leone',
		19131 => 'Singapore',
		19133 => 'Singapore',
		19141 => 'Slovakia',
		19151 => 'Slovenia',
		19161 => 'Solomon Islands',
		19171 => 'South Africa',
		19181 => 'Spain',
		19191 => 'Sri Lanka',
		19201 => 'Sudan',
		19211 => 'Surinam',
		19221 => 'Svalbard And Jan Mayen',
		19231 => 'Swaziland',
		19241 => 'Sweden',
		19251 => 'Switzerland',
		19261 => 'Syrian',
		19271 => 'Saint Kitts And Nevis',
		19281 => 'Western Samoa',
		19291 => 'Somalia',
		19301 => 'Scotland',
		19311 => 'Saint Helena',
		19321 => 'South Ossetia',
		20011 => 'Taiwan',
		20021 => 'Tajikistan',
		20031 => 'Tanzania',
		20041 => 'Thailand',
		20051 => 'Togo',
		20061 => 'Tonga',
		20071 => 'Trinidad And Tobago',
		20073 => 'Trinidad And Tobago',
		20081 => 'Tristan Da Cunha Islands',
		20091 => 'Tuvalu',
		20101 => 'Tunisia',
		20111 => 'Turkey',
		20121 => 'Turkmenistan',
		20131 => 'Turks And Caicos Islands',
		20141 => 'Tokelau Islands',
		21011 => 'Uganda',
		21021 => 'Ukraine',
		21023 => 'Ukraine',
		21031 => 'Uzbekistan',
		21033 => 'Uzbekistan',
		21041 => 'Uruguay',
		21043 => 'Uruguay',
		21051 => 'United States',
		22011 => 'Virgin Islands',
		22021 => 'Vanuatu',
		22023 => 'Vanuatu',
		22031 => 'Venezuela',
		22041 => 'Vietnam',
		22051 => 'Vatican',
		22061 => 'Virgin Islands',
		23011 => 'Wallis And Futuna',
		23021 => 'Western Sahara',
		25011 => 'Yemen',
		26011 => 'Zambia',
		26021 => 'Zimbabwe'
	);

/**
 * - USA
 * 
 * CA	Canada Post
 * US	USPS
 * US	Asendia USA
 * 
 * - EUROPE
 * 
 * GB	Royal Mail
 * IE	An Post
 * SE	Direct Link
 * DE	Deutsche Post DHL
 * AT	Australian Post (Registered)
 * CH	Swiss Post
 * ES	Correos de España
 * NL	PostNL International
 * NO	Posten Norge
 * IT	Poste Italiane Paccocelere
 * HR	Hrvatska Pošta
 * UA	UkrPoshta
 * ES	Belgium Post
 * PL	Poczta Polska
 * LT	Lietuvos paštas
 * TR	PTT Posta
 * DK	Post Danmark
 * RU	Russian Post
 * CZ	Česká Pošta
 * BY	Belpost
 * RO	Poșta Română
 * PT	Portugal CTT
 * JB	Chronopost France
 * GR	ELTA Hellenic Post
 * BG	Bulgarian Posts
 * CY	Cyprus Post
 * 
 * - ASIA
 * 
 * SG	Singapore Post
 * 		Singapore Speedpost
 * KR	Korea Post
 * IN	India Post Domestic
 * 		India Post International
 * JP	Japan Post
 * GB??	Taiwan Post
 * MY	Malaysia Post EMS / Poslaju
 * 		Malaysia Post - Registered
 * TH	Thailand Thai Post
 * TW	Pos Indonesia
 * VN	Vietnam Post
 * 		Vietnam Post EMS
 * KH	Cambodia Post
 * 
 * - AUSTRALIA
 * 
 * NZ	New Zealand Post
 * 		CourierPost
 * 
 * - CHINA
 * 
 * CN	China Post
 * 		China EMS
 * 		WeDo Logistics
 * AU	AuPost China
 * HK	Hong Kong Post
 * 
 * - OTHER
 * 
 * MX	Correos de Mexico
 * BR	Brazil Correios
 * AR	Correo Argentino
 * IL	Israel Post
 * 		Israel Post Domestic
 * ZA	Soutch African Post Office
 * SG	Saudi Post
 * NG	NiPost
**/

} // End Carrier