<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Package Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Package extends Controller_Template {

	/**
	 * Show list of packages and methods
	 * available for authenticated user.
	 *
	 * @return void
	 */
	public function action_index()
	{
		// Setup view
		$this->view = View::factory('package/list')
		->bind('packages', $packages)
		->bind('carriers', $carriers);

		// Find packages
		$packages = $this->user->packages
		->where('deleted_at', 'IS', DB::expr('NULL'))
		->order_by('state', 'ASC')
		->order_by('completed_at', 'DESC')
		->order_by('dispatched_at', 'ASC')
		->find_all();

		// Find carriers
		$carriers = ORM::factory('Carrier')
		->order_by('name', 'ASC')
		->find_all()
		->as_array('id', 'name');
	}

	/**
	 * Create package
	 *
	 * @return void
	 */
	public function action_create()
	{
		// Setup view
		$this->view = View::factory('package/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('carriers', $carriers);

		// Find carriers
		$carriers = ORM::factory('Carrier')
		->order_by('name', 'ASC')
		->find_all()
		->as_array('id', 'name');

		// Create new package
		$package = ORM::factory('Package');

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Set package owner
			$package->user_id = $this->user->id;
			$post = $this->request->post();
			$post['destination_carrier_id'] = Arr::get($post, 'destination_carrier_id', $post['origin_carrier_id']);

			try
			{
				// Set package values
				$package->values($post);

				// Save package
				$package->save();
			}
			catch (ORM_Validation_Exception $e)
			{
				// Bind errors to view
				$errors = $e->errors();
			}

			// Redirect to package detail page
			return HTTP::redirect('package/detail/'.$package->tracking_number);
		}
	}

	/**
	 * Edit package
	 *
	 * @return void
	 */
	public function action_edit()
	{
		// Setup view
		$this->view = View::factory('package/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('carriers', $carriers);

		// Find carriers
		$carriers = ORM::factory('Carrier')
		->order_by('name', 'ASC')
		->find_all()
		->as_array('id', 'name');

		// Create new package
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ($this->request->method() === HTTP_Request::POST)
		{
			try
			{
				// Get post values
				$post = $this->request->post();

				if ($package->origin_carrier_id !== Arr::get($post, 'origin_carrier_id'))
				{
					foreach ($package->status->where('direction', '=', Carrier::ORIGIN)->find_all() as $status)
					{
						// Delete all statuses created by origin carrier driver
						$status->delete();
					}
				}

				if ($package->destination_carrier_id !== Arr::get($post, 'destination_carrier_id'))
				{
					foreach ($package->status->where('direction', '=', Carrier::DESTINATION)->find_all() as $status)
					{
						// Delete all statuses created by destination carrier driver
						$status->delete();
					}
				}

				// Set package values
				$package->values($this->request->post());

				// Save package
				$package->save();
			}
			catch (ORM_Validation_Exception $e)
			{
				// Bind errors to view
				$errors = $e->errors();
			}

			// Redirect to package detail page
			return HTTP::redirect('package/detail/'.$package->tracking_number);
		}
	}

	/**
	 * Refresh package manually
	 *
	 * @return void
	 */
	public function action_refresh()
	{
		if ( ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not allowed');
		}

		// Get direction query string
		$direction = intval($this->request->query('direction'));

		// Get package with identity from params
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if (empty($direction) OR $direction === Carrier::ORIGIN)
		{
			foreach ($package->status->where('direction', '=', Carrier::ORIGIN)->find_all() as $status)
			{
				// Delete all statuses created by origin carrier driver
				$status->delete();
			}

			// Track with destination carrier
			Carrier::factory($package, Carrier::ORIGIN)->track();
		}

		if (empty($direction) OR $direction === Carrier::DESTINATION)
		{
			foreach ($package->status->where('direction', '=', Carrier::DESTINATION)->find_all() as $status)
			{
				// Delete all statuses created by origin carrier driver
				$status->delete();
			}

			// Track with destination carrier
			Carrier::factory($package, Carrier::DESTINATION)->track();
		}

		// Save package
		$package->save();

		return HTTP::redirect('package/detail/'.$package->tracking_number);
	}

	/**
	 * Delete package tracking
	 *
	 * @return void
	 */
	public function action_delete()
	{
		// Disable template auto rendering
		$this->auto_render = FALSE;

		// What response to give
		$response = array(200, 'Ok');

		// Get package with identity from params
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ( ! $package->loaded())
		{
			// Set 404 response
			$response = array(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			// Set 401 response
			$response = array(401, 'Not authorized to delete this package');
		}

		if ($response[0] === 200)
		{
			// Set deleted at timestamp
			$package->deleted_at = date('Y-m-d H:i:s');

			// Save package
			$package->save();
		}

		// Set HTTP status code
		$this->response->status($response[0]);

		// Set JSON response body
		$this->response->body(json_encode(array(
			'status'  => $response[0],
			'message' => $response[1],
			'data'    => array(
				'redirect' => '/package'
			)
		)));
	}

	/**
	 * Get tracking number details
	 * with status informations.
	 *
	 * @return void
	 */
	public function action_detail()
	{
		// Setup main template view
		$this->view = View::factory('package/detail')
		->bind('package', $package)
		->bind('extra', $extra)
		->bind('origin', $origin)
		->bind('destination', $destination);

		// Get package with identity from params
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to view this package');
		}

		// Bind extra information
		$extra = json_decode($package->extra);

		// Get origin status updates
		$origin = $package->status
		->where('direction', '=', Carrier::ORIGIN)
		->order_by('timestamp', 'DESC')
		->find_all();

		// Get destination status updates
		$destination = $package->status
		->where('direction', '=', Carrier::DESTINATION)
		->order_by('timestamp', 'DESC')
		->find_all();
	}

	/**
	 * Email status update to owner
	 *
	 * @return void
	 */
	public function action_email()
	{
		// Get package with identity from params
		$package = ORM::factory('Package', array('tracking_number' => $this->request->param('id')));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(401, 'Not authorized to view this package');
		}

		mail($package->user->email, 'Coral Delivery :: '.__('Status update for :tracking_number', array(':tracking_number' => $package->tracking_number)), View::factory('hook/mail/package-status-update')->set('package', $package), implode("\r\n", array(
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=utf-8',
			'To: '.$package->user->fullname.' <'.$package->user->email.'>',
			'From: Coral Delivery <www-data@corona.forritun.org>'
		)));

		return HTTP::redirect('package/detail/'.$package->tracking_number);
	}

} // End Package Controller