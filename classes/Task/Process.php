<?php defined('SYSPATH') or die('No direct script access.');

class Task_Process extends Minion_Task {

	/**
	 * @var array Options
	 */
	protected $_options = array();

	/**
	 * @var integer Update interval in seconds
	 */
	protected $_update_interval = 0;

	/**
	 * Process all packages carrier drivers
	 *
	 * @param  array Parameters
	 * @return void
	 */
	protected function _execute(array $params)
	{
		// Only report catastrophic errors
		error_reporting(E_ERROR);

		// Find all user services 6 hour update delay
		$services = ORM::factory('User_Service')
		->and_where_open()
			->where('updated_at', 'IS', NULL)
			->or_where('updated_at', '<', date('Y-m-d H:i:s', time() - (86400 / 4)))
		->and_where_close()
		->find_all();

		Minion_CLI::write('');
		Minion_CLI::write('Process task daemon started...');
		Minion_CLI::write('');

		foreach ($services as $service)
		{
			// Get class name
			$class_name = 'Service_'.$service->method;

			// Create new service driver instance
			$driver = new $class_name($service);

			Minion_CLI::write('[Service '.Minion_CLI::color($service->method, 'yellow').' starting]');

			// Process service
			$msgs = $driver->process();

			foreach ($msgs as $msg)
			{
				Minion_CLI::write(' - '.$msg);
			}

			$service->updated_at = date('Y-m-d H:i:s');
			// $service->save();
		}

		// Load all packages that are not complete and last updated
		$packages = ORM::factory('Package')
		->where('completed_at', 'IS', DB::expr('NULL'))
		->where('deleted_at', 'IS', DB::expr('NULL'))
		->and_where_open()
			->where('updated_at', 'IS', NULL)
			->or_where('updated_at', '<', date('Y-m-d H:i:s', time() - $this->_update_interval))
		->and_where_close()
		->find_all();

		// Output help messages
		Minion_CLI::write('');
		Minion_CLI::write('Packages needing update: '.$packages->count());

		foreach ($packages as $package)
		{
			// Get state before processing
			$state = intval($package->state);

			Minion_CLI::write(' ');
			Minion_CLI::write('[Package '.Minion_CLI::color($package->tracking_number, 'light_blue').']');

			// Track origin carrier
			Minion_CLI::write(' - processing package for status updates');
			try
			{
				$package->process();
			}
			catch (Exception $e)
			{
				Minion_CLI::write(' - '.Minion_CLI::color('failed, check error log.', 'red'));
			}

			if ($package->state !== $state AND $package->notify_email === 1)
			{
				mail($package->user->email, SITE_NAME.' :: '.__('Status update for :tracking_number', array(':tracking_number' => $package->tracking_number)), View::factory('mail/package/status')->set('package', $package), implode("\r\n", array(
					'MIME-Version: 1.0',
					'Content-type: text/html; charset=utf-8',
					'To: '.$package->user->fullname.' <'.$package->user->email.'>',
					'From: '.SITE_NAME.' <www-data@corona.forritun.org>'
				)));
			}
		}

		Minion_CLI::write('All done!');
	}

} // End Process Task