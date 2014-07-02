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

	public static function methods() {
		return array(
			'Hook::send_email' => __('Send email'),
			'Hook::send_sms'   => __('Send SMS'),
			'Hook::options'    => __('Set options')
		);
	}

	public function action_create()
	{
		// Setup view
		$this->view = View::factory('hook/builder')
		->bind('hook', $hook);

		// Find carriers
		$this->view->carriers = ORM::factory('Carrier')
		->order_by('name', 'ASC')
		->find_all()
		->as_array('id', 'name');

		// Find methods
		$this->view->methods = self::methods();

		// Create Hook model
		$hook = ORM::factory('User_Hook');

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Get parameters
			$trigger = $this->request->post('trigger');
			$carrier = $this->request->post('carrier_id');
			$origin = $this->request->post('origin');
			$destination = $this->request->post('destination');

			// Populate Hook model
			$hook->user_id = $this->user->id;
			$hook->created_at = date('Y-m-d H:i:s');
			$hook->trigger = empty($trigger) ? NULL : $trigger;
			$hook->carrier_id = empty($carrier) ? NULL : $carrier;
			$hook->origin = empty($origin) ? NULL : $origin;
			$hook->destination = empty($destination) ? NULL : $destination;
			$hook->name = $this->request->post('name');
			$hook->method = $this->request->post('method');
			$hook->save();

			return HTTP::redirect('account/index');
		}
	}

	public function action_edit()
	{
		// Setup view
		$this->view = View::factory('hook/builder')
		->bind('hook', $hook);

		// Find carriers
		$this->view->carriers = ORM::factory('Carrier')
		->order_by('name', 'ASC')
		->find_all()
		->as_array('id', 'name');

		// Find methods
		$this->view->methods = self::methods();

		// Create Hook model
		$hook = ORM::factory('User_Hook', $this->request->param('id'));

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Get parameters
			$trigger = $this->request->post('trigger');
			$carrier = $this->request->post('carrier_id');
			$origin = $this->request->post('origin');
			$destination = $this->request->post('destination');

			// Populate Hook model
			$hook->created_at = date('Y-m-d H:i:s');
			$hook->trigger = empty($trigger) ? NULL : $trigger;
			$hook->carrier_id = empty($carrier) ? NULL : $carrier;
			$hook->origin = empty($origin) ? NULL : $origin;
			$hook->destination = empty($destination) ? NULL : $destination;
			$hook->name = $this->request->post('name');
			$hook->method = $this->request->post('method');
			$hook->save();
		}
	}

} // End Hook