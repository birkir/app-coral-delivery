<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hook Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Hook extends Controller_Template {

	/**
	 * Services available
	 */
	public static $methods = array(
		'Emailattachment' => 'Email with attachment'
	);

	/**
	 * List hooks for package
	 *
	 * @return void
	 */
	public function action_list()
	{
		$this->view = View::factory('hook/list')
		->bind('package', $package)
		->bind('hooks', $hooks);

		// Find package
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to view hooks for this package');
		}

		// Find package hooks
		$hooks = $package->hooks->order_by('name', 'ASC')->find_all();
	}

	/**
	 * Create hook for package
	 *
	 * @return void
	 */
	public function action_create()
	{
		// Setup view
		$this->view = View::factory('hook/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('hook', $hook)
		->bind('fields', $fields)
		->set('methods', self::$methods);

		// Find package
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to create hook for this package');
		}

		// Create Hook model
		$hook = ORM::factory('Package_Hook');

		if ($this->request->method() === HTTP_Request::POST)
		{
			try
			{
				// Save hook values
				$hook->package_id = $package->id;
				$hook->updated_at = date('Y-m-d H:i:s');
				$hook->values($this->request->post())->save();

				// Redirect to newly created hook
				HTTP::redirect('hook/edit/'.$hook->id);
			}
			catch (ORM_Validation_Exception $e)
			{
				// Attach errors to view
				$errors = $e->errors('models');
			}
		}
	}

	/**
	 * Edit specific hook for package
	 *
	 * @return void
	 */
	public function action_edit()
	{
		// Setup view
		$this->view = View::factory('hook/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('hook', $hook)
		->bind('fields', $fields)
		->set('methods', self::$methods);

		// Create Hook model
		$hook = ORM::factory('Package_Hook', $this->request->param('id'));

		// Get package from hook
		$package = $hook->package;

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to edit this hook');
		}

		// Get hook driver
		$class_name = 'Hook_'.$hook->method;
		$driver = new $class_name($hook);

		// Get request post data
		$post = $this->request->post();

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Set hook values
			$driver->hook->values($post);

			try
			{
				if ($driver->_fieldset_save($post))
				{
					// Save hook values and save
					$driver->hook->updated_at = date('Y-m-d H:i:s');
					$driver->hook->save();
				}
			}
			catch (ORM_Validation_Exception $e)
			{
				// Attach errors to view
				$errors = $e->errors('models');
			}
		}

		// Get hook fields
		$fields = $driver->_fieldset_view($post);
	}

	/**
	 * Delete package hook
	 *
	 * @return void
	 */
	public function action_delete()
	{
		// Skip auto rendering
		$this->auto_render = FALSE;

		// Get hook with identity from params
		$hook = ORM::factory('Package_Hook', $this->request->param('id'));

		// Get package from hook
		$package = $hook->package;

		if ( ! $hook->loaded())
		{
			throw HTTP_Exception::factory(404, 'Hook not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to delete this hook');
		}

		// Delete hook
		$hook->delete();

		// Redirect to hook list
		return HTTP::redirect('hook/list/'.$package->tracking_number);
	}

	/**
	 * Test the email hook
	 */
	public function action_test()
	{
		if ( ! $this->user->is_admin())
			throw HTTP_Exception::factory(401, 'Not authorized');

		$foo = Hook::factory(ORM::factory('Package_Hook', 1));
		$foo->send_email();

	}

} // End Hook Controller