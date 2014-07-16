<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Carrier Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Carrier extends ORM {

	/**
	 * @var array Has-many relationships
	 */
	protected $_has_many = array(
		'packages' => array()
	);

	/**
	 * Process our carrier driver stuff
	 *
	 * @param  ORM $package
	 * @return bool
	 */
	public function process($package)
	{
		// Setup driver name list
		$driver = array('Carrier');

		if ($this->express === 1)
		{
			// Push express to array
			$driver[] = 'Express';
		}

		// Push driver name to array
		$driver[] = empty($this->driver) ? 'Core' : UTF8::ucfirst($this->driver);

		// Set the driver class name
		$driver = implode('_', $driver);

		// Create the carrier driver
		$driver = new $driver($package, $this);

		// Process carrier driver
		$driver->process();

		return $this;
	}

} // End Carrier Model