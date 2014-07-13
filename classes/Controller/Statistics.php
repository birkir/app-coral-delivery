<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Statistics Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Statistics extends Controller_Template {

	/**
	 * Generate all kinds of statistics for your packages
	 * and packages in whole.
	 *
	 * @return void
	 */
	public function action_index()
	{
		$this->view = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';

		// Setup transit times array
		$transit_times = array();
		$origins = array();
		$destinations = array();
		$states = array();

		// Find all packages
		$packages = $this->user->packages
		->where('origin_location', 'IS NOT', DB::expr('NULL'))
		->where('origin_location', '!=', 'Unknown')
		->where('destination_location', 'IS NOT', DB::expr('NULL'))
		->where('destination_location', '!=', 'Unknown')
		->where('deleted_at', 'IS', DB::expr('NULL'))
		->find_all();

		foreach ($packages as $package)
		{
			// Increment origins array
			$origins[$package->origin_location] = Arr::get($origins, $package->origin_location, 0) + 1;

			// Increment destinations array
			$destinations[$package->destination_location] = Arr::get($destinations, $package->destination_location, 0) + 1;

			// Increment states array
			$states[$package->state] = Arr::get($states, $package->state, 0) + 1;

			if (intval($package->state) < 2) continue;

			// Set label from origin and destination countries
			$label = $package->origin_location.'_'.$package->destination_location;

			// Get needed dates
			$registered_at = new DateTime($package->registered_at);
			$dispatched_at = new DateTime($package->dispatched_at);
			$completed_at = new DateTime($package->completed_at);

			if ( ! isset($transit_times[$label]))
			{
				// Initialize data array
				$transit_times[$label] = array(
					'collecting' => array(),
					'transit'    => array()
				);
			}

			// Append days to data array
			$transit_times[$label]['collecting'][] = intval($registered_at->diff($dispatched_at)->format('%a'));
			$transit_times[$label]['transit'][] = intval($dispatched_at->diff($completed_at)->format('%a'));

		}

		foreach ($transit_times as $label => $data)
		{
			// Sum them up and divide by count
			$transit_times[$label]['collecting'] = array_sum($data['collecting']) / count($data['collecting']);
			$transit_times[$label]['transit'] = array_sum($data['transit']) / count($data['transit']);
		}
/*
		echo Debug::vars($transit_times);
		echo Debug::vars($origins);
		echo Debug::vars($destinations);
		echo Debug::vars($states);
*/
		$this->view = '<script>window.stats_transit_times = '.json_encode($transit_times).';</script>';
		$this->view .= '<div class="chart chart-columns" data-chart="columns" data-json="stats_transit_times" style="width: 100%; height: 320px;"></div>';
	}

	public function action_test()
	{
		$hashids = new Hashids('CoralDelivery.Package.PK', 6);

		$foo = $hashids->decrypt('logout');

		echo Debug::vars($foo);

		exit;

		$package = ORM::factory('Package');
		$carrier = new Carrier_Express_TNT($package, NULL, 1);
		$carrier->tracking_number = '818210797';
		$carrier->track();
		exit;
	}

} // End Statistics Controller