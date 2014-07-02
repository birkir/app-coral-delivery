<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User Service Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_User_Service extends ORM {

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'user' => array()
	);

} // End User Service Model