<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Royal Mail Carrier
 *
 * @package    Coral
 * @category   Carrier
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Carrier_UK_RoyalMail extends Carrier {

	/**
	 * Get results for tracking number
	 *
	 * @return void
	 */
	public function track()
	{
		return 0;
	}

} // End RoyalMail UK Carrier