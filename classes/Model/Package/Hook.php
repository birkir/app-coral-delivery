<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Package Hook Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Package_Hook extends ORM {

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'package' => array()
	);

} // End Package Hook Model