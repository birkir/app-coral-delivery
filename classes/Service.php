<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Service base class
 *
 * @package    Coral
 * @category   Service
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Service {

	/**
	 * List service available methods for user interface
	 *
	 * @return array
	 */
	public function methods()
	{
		return array();
	}

	/**
	 * Show detail page for service
	 *
	 * @return string
	 */
	public function detail()
	{
		return "No details available";
	}

} // End Service