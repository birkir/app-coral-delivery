<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hook base class
 *
 * @package    Coral
 * @category   Hook
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Hook {

	/**
	 * Creates and returns a new hook class.
	 * 
	 * @chainable
	 * @param   object  $package  Loaded package
	 * @param   array   $status   Array of status item
	 * @return  Hook
	 */
	public static function factory($hook)
	{
		// Set the driver class name
		$driver = 'Hook_'.ucfirst($hook->method);

		// Create the carrier driver
		$driver = new $driver($hook);

		return $driver;
	}

	/**
	 * Constructs a new hook class.
	 *
	 * @param   string  $package  Loaded package
	 */
	public function __construct($hook)
	{
		// Assign hook to driver
		$this->hook = $hook;

		// Get hook package
		$this->package = $hook->package;

		// Get hook data attributes decoded
		$this->data = json_decode($this->hook->data, TRUE);
	}

	public function _fieldset_save(array $post = array()) {}
	public function _fieldset_view(array $post = array()) {}
	public function _status_hook($status) {}
	public function _update_hook() {}

} // End Hook