<?php
/**
 * Aramex Express Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_Express_FedEx extends Carrier {

	/**
	 * @var string Regex for registered check
	 */
	public $matchRegistered = '/shipment information sent to fedex/s';

	/**
	 * @var string Regex for dispatched check
	 */
	public $matchDispatched = '/left fedex origin facility/s';

	/**
	 * @var string Regex for in customs check
	 */
	public $matchInCustoms = '/international shipment release/s';

	/**
	 * @var string Regex for out for delivery check
	 */
	public $matchOutForDelivery = '/on fedex vehicle for delivery/s';

	/**
	 * @var string Regex for failed attempt check
	 */
	public $matchFailedAttempt = '/delivery exception future delivery requested/s';

	/**
	 * @var string Regex for delivered check
	 */
	public $matchDelivered = '/delivered/s';

	/**
	 * Create and execute request needed to process package properties and scan messages.
	 *
	 * @return Response
	 */
	public function getRequest()
	{
		// Setup HTTP Request
		$request = Request::factory('https://www.fedex.com/trackingCal/track');

		// Execute Curl Request
		$response = $request->execute()->body();

		return $response;
	}

	/**
	 * Get status items categorized by direction
	 *
	 * @return array
	 */
	function getStatusItems($response)
	{
		// Setup returned items array
		$items = array(
			Carrier::ORIGIN      => array(),
			Carrier::DESTINATION => array()
		);

		return $items;
	}

} // End Aramex Express Carrier