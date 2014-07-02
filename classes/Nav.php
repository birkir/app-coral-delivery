<?php defined('SYSPATH') or die('No direct script access.');

class Nav {

	public static function ac($controller, $action = NULL)
	{
		return ' class="'.(Nav::active($controller, $action) ? 'active' : NULL).'"';
	}

	public static function active($controller, $action = NULL)
	{
		// Get current request
		$request = Request::current();

		if (strtolower($request->controller()) !== strtolower($controller))
		{
			return FALSE;
		}
		
		if ($action AND strtolower($request->action()) !== strtolower($action))
		{
			return FALSE;
		}

		return TRUE;
	}

}