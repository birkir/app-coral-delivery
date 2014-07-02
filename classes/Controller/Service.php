<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Service Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Service extends Controller_Template {

	/**
	 * Services available
	 */
	public static $methods = array(
		'Aliexpress' => 'Aliexpress orders'
	);

	/**
	 * List user services
	 *
	 * @return void
	 */
	public function action_index()
	{
		// Setup view
		$this->view = View::factory('service/list')
		->bind('services', $services);

		// Find user services
		$services = $this->user->services
		->order_by('name', 'ASC')
		->find_all();
	}

	/**
	 * Add new service
	 *
	 * @return void
	 */
	public function action_create()
	{
		// Setup view
		$this->view = View::factory('service/fieldset')
		->bind('errors', $errors)
		->bind('service', $service)
		->bind('methods', $methods);

		// Create user service model
		$service = ORM::factory('User_Service');

		// List methods
		$methods = self::$methods;

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Get encrypt instance
			$encrypt = Encrypt::instance();

			// Get post data
			$post = $this->request->post();

			// Set correct user id and encrypt password
			$post['user_id'] = $this->user->id;
			$post['password'] = $encrypt->encode($post['password']);

			// Add post values and save
			$service->values($post)->save();

			// Redirect to detail page
			return HTTP::redirect('service/detail/'.$service->id);
		}
	}

	/**
	 * Get service details
	 *
	 * @return void
	 */
	public function action_detail()
	{
		// Setup view
		$this->view = View::factory('service/detail')
		->bind('service', $service)
		->bind('methods', $methods)
		->bind('detail', $detail);

		// Find user service
		$service = ORM::factory('User_Service', $this->request->param('id'));

		// Only allow owner and admins
		if ($service->user_id !== $this->user->id AND ! $this->user->has('roles', ORM::factory('Role', array('name' => 'admin'))))
		{
			throw HTTP_Exception::factory(404, 'Service not found.');
		}

		// Find driver class name
		$class_name = 'Service_'.$service->method;

		// Start the driver
		$driver = new $class_name($service);

		// Get detail view
		$detail = $driver->detail();

		// Load available options
		$methods = $driver->methods();

		// Get method
		$method = $this->request->query('method');

		if (isset($methods[$method]) AND method_exists($driver, $method))
		{
			// Call the method and inject request
			$output = call_user_func(array($driver, $method), $this);

			// Skip auto rendering
			$this->auto_render = FALSE;

			// Dump the juice
			$this->response->body($output);
		}
	}

	/**
	 * Edit user service
	 *
	 * @return void
	 */
	public function action_edit()
	{
		// Setup view
		$this->view = View::factory('service/fieldset')
		->bind('errors', $errors)
		->bind('service', $service)
		->bind('methods', $methods);

		// Find user service
		$service = ORM::factory('User_Service', $this->request->param('id'));

		// Only allow owner and admins
		if ($service->user_id !== $this->user->id AND ! $this->user->has('roles', ORM::factory('Role', array('name' => 'admin'))))
		{
			throw HTTP_Exception::factory(404, 'Service not found.');
		}

		// List methods
		$methods = self::$methods;

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Get post data
			$post = $this->request->post();

			// Set correct user id
			$post['user_id'] = $this->user->id;

			if (empty($post['password']))
			{
				// Unset password if not given
				unset($post['password']);
			}
			else
			{
				// Get encrypt instance
				$encrypt = Encrypt::instance();

				// Encode password for database storage
				$post['password'] = $encrypt->encode($post['password']);
			}

			// Add post values and save
			$service->values($post)->save();
		}
	}

	/**
	 * Delete user service
	 *
	 * @return void
	 */
	public function action_delete()
	{

	}

}