<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User Auth Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_User_Auth extends ORM {

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'user'    => array()
	);

} // End User Auth Model