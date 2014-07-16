<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Package Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Package extends ORM {

	/**
	 * @var int Package states
	 */
	const LOADING = 0;
	const NOT_FOUND = 1;
	const REGISTERED = 2;
	const SHIPPING_INFO_RECEIVED = 2;
	const IN_TRANSIT = 3;
	const IN_CUSTOMS = 4;
	const OUT_FOR_DELIVERY = 5;
	const FAILED_ATTEMPT = 6;
	const EXCEPTION = 7;
	const PICK_UP = 8;
	const DELIVERED = 9;

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'origin_carrier' => array(
			'model'       => 'Carrier',
			'far_key'     => 'id',
			'foreign_key' => 'origin_carrier_id'
		),
		'destination_carrier' => array(
			'model'       => 'Carrier',
			'far_key'     => 'id',
			'foreign_key' => 'destination_carrier_id'
		),
		'origin_country' => array(
			'model'       => 'Country',
			'far_key'     => 'id',
			'foreign_key' => 'origin_country_id'
		),
		'destination_country' => array(
			'model'       => 'Country',
			'far_key'     => 'id',
			'foreign_key' => 'destination_country_id'
		),
		'user' => array(),
		'service' => array(
			'model'       => 'User_Service'
		)
	);

	/**
	 * @var array Has-many relationships
	 */
	protected $_has_many = array(
		'status' => array(
			'model' => 'Package_Status'
		),
		'hooks' => array(
			'model' => 'Package_Hook'
		)
	);


	/**
	 * @var Hashids
	 */
	public $hashids;

	/**
	 * Constructs a new model and loads a record if given
	 *
	 * @param mixed $id Parameter for find or object to load
	 */
	public function __construct($id = NULL)
	{
		if (is_string($id))
		{
			if ( ! $this->hashids)
			{
				$this->hashids = new Hashids('CoralDelivery.Package.PK', 6);
			}

			$id = current($this->hashids->decrypt($id));
		}

		parent::__construct($id);
	}

	/**
	 * Process package
	 *
	 * @return void
	 */
	public function process()
	{
		if ( ! $this->_loaded)
			throw new Kohana_Exception('Cannot process :model model because it is not loaded.', array(':model' => $this->_object_name));

		// Process original carrier
		$this->origin_carrier->process($this);

		if (intval($this->destination_carrier_id) > 0 AND $this->destination_carrier_id !== $this->origin_carrier_id)
		{
			// Process destination carrier
			$this->destination_carrier->process($this);
		}

		// Updated at time
		$this->updated_at = date('Y-m-d H:i:s');

		// Save package
		$this->save();

		return $this;
	}

	/**
	 * Filters to format fields before insert
	 *
	 * @return array
	 */
	public function filters()
	{
		return array(
			'photo' => array(
				array(array($this, 'save_photo'))
			)
		);
	}

	/** 
	 * Download the photo and return local path
	 *
	 * @param  string $url
	 * @return void
	 */
	public function save_photo($url)
	{
		// Where to store photos
		$prefix = '/application/cache/uploads/';

		// Skip if already uploaded!
		if ((substr($url, 0, strlen($prefix)) === $prefix) OR empty($url))
			return $url;

		// Setup request
		$request = Request::factory($url);

		// Set upload directory path
		$upload_dir = APPPATH.'cache/uploads';

		if ( ! is_dir($upload_dir))
		{
			try
			{
				// Create the cache directory
				mkdir($upload_dir, 0755, TRUE);

				// Set permissions (must be manually set to fix umask issues)
				chmod($upload_dir, 0755);
			}
			catch (Exception $e)
			{
				throw new Kohana_Exception('Could not create uploads directory :dir',
					array(':dir' => Debug::path($settings['cache_dir'])));
			}
		}

		try
		{
			// Execute request
			$response = $request->execute();

			// Get mime type of binary data
			$finfo = new finfo(FILEINFO_MIME);
			$mime = $finfo->buffer($response->body());

			// Check if its image mime type
			if ( ! preg_match('/(gif|jpeg|png|svg)/s', $mime, $ext))
				return NULL;

			// Set extension
			$ext = str_replace('jpeg', 'jpg', $ext[1]);

			// Set filename
			$filename = sha1($url).'.'.$ext;

			// Write file to disk
			$fh = fopen($upload_dir.'/'.$filename, 'w');
			fwrite($fh, $response->body());
			fclose($fh);

			return $prefix.$filename;
		}
		catch (Request_Exception $e)
		{
			return NULL;
		}
	}

	/**
	 * Add extra properties to json field
	 *
	 * @param string Param key
	 * @param string Param value
	 * @return Model_Package
	 */
	public function extras($key, $value)
	{
		// Decode JSON to array
		$extra = json_decode($this->extra, TRUE);

		// Set key, value pair
		$extra[$key] = $value;

		// Encode back as JSON
		$this->extra = json_encode($extra);

		return $this;
	}

	/**
	 * Get state as text
	 *
	 * @param  bool   Return as bootstrap 3 label?
	 * @return string
	 */
	public function state($use_label = FALSE)
	{
		// Get current status
		$state = intval($this->state);

		// Set status message
		$msg = 'Unknown';

		// Set label
		$label = 'default';

		switch ($state)
		{
			case 0: $msg = 'Loading'; $label = 'info'; break;
			case 1: $msg = 'Not found'; $label = 'error'; break;
			case 2: $msg = 'Shipping Info Received'; $label = 'info'; break;
			case 3: $msg = 'In transit'; $label = 'primary'; break;
			case 4: $msg = 'In customs'; $label = 'warning'; break;
			case 5: $msg = 'Out for Delivery'; $label = 'warning'; break;
			case 6: $msg = 'Failed attempt'; $label = 'warning'; break;
			case 7: $msg = 'Exception'; $label = 'warning'; break;
			case 8: $msg = 'Pick-up'; $label = 'info'; break;
			case 9: $msg = 'Delivered'; $label = 'success'; break;
		}

		return $use_label ? '<span class="badge label-'.$label.'">'.__($msg).'</span>' : __($msg);
	}

	/**
	 * Soft deletes a single record while ignoring relationships.
	 *
	 * @chainable
	 * @throws Kohana_Exception
	 * @return ORM
	 */
	public function delete()
	{
		if ( ! $this->_loaded)
			throw new Kohana_Exception('Cannot delete :model model because it is not loaded.', array(':model' => $this->_object_name));

		// Set timestamp to deleted column
		$this->deleted_at = date('Y-m-d H:i:s');

		// Save the model
		$this->save();

		return $this->clear();
	}

	/**
	 * Finds multiple database rows and returns an iterator of the rows found.
	 *
	 * @uses parent
	 * @return Database_Result
	 */
	public function find_all()
	{
		// Only non-deleted objects
		$this->where('deleted_at', 'IS', DB::expr('NULL'));

		return parent::find_all();
	}

	/**
	 * Finds single database rows and returns the row found
	 *
	 * @uses parent
	 * @return Database_Result
	 */
	public function find()
	{
		// Only non-deleted objects
		$this->where('deleted_at', 'IS', DB::expr('NULL'));

		return parent::find();
	}

	/**
	 * Get the primary key value of the object
	 *
	 * @uses Hashids
	 * @return string
	 */
	public function hashid()
	{
		if ( ! $this->hashids)
		{
			$this->hashids = new Hashids('CoralDelivery.Package.PK', 6);
		}

		return $this->hashids->encrypt($this->_primary_key_value);
	}

} // End Package Model