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
	const SHIPPING_INFO_RECEIVED = 2;
	const IN_TRANSIT = 3;
	const IN_CUSTOMS = 4;
	const PICK_UP = 5;
	const DELIVERED = 6;

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
		'user' => array()
	);

	/**
	 * @var array Has-many relationships
	 */
	protected $_has_many = array(
		'status' => array(
			'model' => 'Package_Status'
		),
		'hook' => array(
			'model' => 'Package_Hook'
		)
	);

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
			case 5: $msg = 'Pick-up'; $label = 'info'; break;
			case 6: $msg = 'Delivered'; $label = 'success'; break;
		}

		return $use_label ? '<span class="badge label-'.$label.'">'.__($msg).'</span>' : __($msg);
	}

} // End Package Model