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
		'Emailattachment' => 'Email with attachment',
		'SMS'             => 'Text message'
	);

	/**
	 * List hooks for package
	 *
	 * @return void
	 */
	public function action_index()
	{
		$this->view = View::factory('hook/list')
		->bind('package', $package)
		->bind('hooks', $hooks);

		// Find package
		$package = ORM::factory('Package', $this->request->param('id'));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to view hooks for this package');
		}

		// Find package hooks
		$hooks = $package->hooks->order_by('name', 'ASC')->find_all();
	}

	/**
	 * Create hook for package
	 *
	 * @return void
	 */
	public function action_add()
	{
		// Setup view
		$this->view = View::factory('hook/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('hook', $hook)
		->bind('fields', $fields)
		->set('methods', self::$methods);

		// Find package
		$package = ORM::factory('Package', $this->request->param('id'));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to create hook for this package');
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
		$hook = ORM::factory('Package_Hook', $this->request->param('hook'));

		// Get package from hook
		$package = $hook->package;

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to edit this hook');
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
		$hook = ORM::factory('Package_Hook', $this->request->param('hook'));

		// Get package from hook
		$package = $hook->package;

		if ( ! $hook->loaded())
		{
			throw HTTP_Exception::factory(404, 'Hook not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to delete this hook');
		}

		// Delete hook
		$hook->delete();

		// Redirect to hook list
		return HTTP::redirect('package/'.$package->hashid().'/hooks');
	}

} // End Hook Controller