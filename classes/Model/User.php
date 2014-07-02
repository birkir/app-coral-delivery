<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_User extends Model_Auth_User {

	/**
	 * @var boolean Admin flag
	 */
	public $_is_admin;

	/**
	 * @var array Has-many relationships
	 */
	protected $_has_many = array(
		'user_tokens' => array(
			'model'   => 'User_Token'
		),
		'roles' => array(
			'model'   => 'Role',
			'through' => 'roles_users'
		),
		'packages' => array(
			'model'   => 'Package'
		),
		'hooks' => array(
			'model'   => 'User_Hook'
		),
		'services' => array(
			'model'   => 'User_Service'
		)
	);

	/**
	 * Set user model validation rules
	 *
	 * @return array
	 */
	public function rules()
	{
		// Get parent rules
		$rules = parent::rules();

		// Dont want the username to be validated
		$rules['username'] = [];

		return $rules;
	}

	/**
	 * Return field as unique key
	 *
	 * @param  string Value to search for
	 * @return string
	 */
	public function unique_key($value)
	{
		return 'email';
	}

	/**
	 * Do a check if user is admin
	 *
	 * @return boolean
	 */
	public function is_admin()
	{
		if ( ! $this->_is_admin)
		{
			// Get admin status only once
			$this->_is_admin = $this->has('roles', ORM::factory('Role', array('name' => 'admin')));
		}

		return $this->_is_admin;
	}

} // End User Model