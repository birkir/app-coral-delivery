<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Package Status Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Package_Status extends ORM {

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'package' => array()
	);

	/**
	 * Process triggers on create attempt
	 *
	 * @return bool
	 */
	public function create(Validation $validation = NULL)
	{
		parent::create($validation);

		if ($this->_saved === TRUE)
		{
			// Loop through hooks
			foreach ($this->package->hooks->where('enabled', '=', 1)->find_all() as $hook)
			{
				// Process status
				Hook::factory($hook)->_status_hook($this);
			}
		}

		return $this;
	}

} // End Package Status Model