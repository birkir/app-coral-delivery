<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Country Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Country extends ORM {

	/**
	 * @var array Has-many relationships
	 */
	protected $_has_many = array(
		'packages' => array()
	);

} // End Country Model