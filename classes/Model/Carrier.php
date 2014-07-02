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

} // End Carrier Model