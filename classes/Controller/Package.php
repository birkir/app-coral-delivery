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
	 * Add package
	 *
	 * @return void
	 */
	public function action_add()
	{
		// Setup view
		$this->view = View::factory('package/fieldset')
		->bind('package', $package)
		->bind('errors', $errors)
		->bind('carriers', $carriers);

		// Find carriers
		$carriers = ORM::factory('Carrier')
		->order_by('express', 'DESC')
		->order_by('name', 'ASC')
		->find_all();

		// Create new package
		$package = ORM::factory('Package');

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Set package owner
			$package->user_id = $this->user->id;
			$post = $this->request->post();

			// Find carriers
			$carrier = ORM::factory('Carrier', Arr::get($post, 'carrier'));
			$carrier2 = ORM::factory('Carrier', Arr::get($post, 'carrier2'));

			if ( ! $carrier->loaded() OR ($carrier->express == '0' AND ! $carrier2->loaded()))
			{
				$errors = array('Carrier was not found, please try again.');
				return;
			}

			// Set carriers 
			$post['origin_carrier_id'] = $carrier->id;
			$post['destination_carrier_id'] = ($carrier->express == '1') ? NULL : $carrier2->id;
			
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
			return HTTP::redirect('package/'.$package->hashid());
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
		->order_by('express', 'DESC')
		->order_by('name', 'ASC')
		->find_all();

		// Create new package
		$package = ORM::factory('Package', $this->request->param('id'));

		if ($this->request->method() === HTTP_Request::POST)
		{
			try
			{
				// Get post values
				$post = $this->request->post();

				// Find carriers
				$carrier = ORM::factory('Carrier', Arr::get($post, 'carrier'));
				$carrier2 = ORM::factory('Carrier', Arr::get($post, 'carrier2'));

				if ( ! $carrier->loaded() OR ($carrier->express == '0' AND ! $carrier2->loaded()))
				{
					$errors = array('Carrier was not found, please try again.');
					return;
				}

				// Set carriers 
				$post['origin_carrier_id'] = $carrier->id;
				$post['destination_carrier_id'] = ($carrier->express == '1') ? NULL : $carrier2->id;

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
			return HTTP::redirect('package/'.$package->hashid());
		}
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
		$package = ORM::factory('Package', $this->request->param('id'));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Package not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to view this package');
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
	 * Refresh package manually
	 *
	 * @return void
	 */
	public function action_reload()
	{
		if ( ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not allowed');
		}

		// Get package with identity from params
		$package = ORM::factory('Package', $this->request->param('id'));

		// Delete all statuses created by origin carrier driver
		foreach ($package->status->where('direction', '=', Carrier::ORIGIN)->find_all() as $status) $status->delete();

		// Track with destination carrier
		Carrier::factory($package, Carrier::ORIGIN)->track();

		// Only process carrier update once, if set as both.
		if ($package->origin_carrier->express == '0' AND ($package->origin_carrier_id !== $package->destination_carrier_id))
		{
			// Delete all statuses created by origin carrier driver
			foreach ($package->status->where('direction', '=', Carrier::DESTINATION)->find_all() as $status) $status->delete();

			// Track with destination carrier
			Carrier::factory($package, Carrier::DESTINATION)->track();
		}

		// Save package
		$package->save();

		return HTTP::redirect('package/'.$package->hashid());
	}

	/**
	 * Delete package tracking
	 *
	 * @return void
	 */
	public function action_delete()
	{
		// Skip auto rendering
		$this->auto_render = FALSE;

		// Get package with identity from params
		$package = ORM::factory('Package', $this->request->param('id'));

		if ( ! $package->loaded())
		{
			throw HTTP_Exception::factory(404, 'Service not found');
		}

		if ($package->user_id !== $this->user->id OR ! $this->user->is_admin())
		{
			throw HTTP_Exception::factory(403, 'Not authorized to delete this service');
		}

		// Delete service
		$package->delete();

		// Redirect to services list
		return HTTP::redirect('packages');
	}

} // End Package Controller