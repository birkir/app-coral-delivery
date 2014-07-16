<?php

class Delivery {

	public function __construct()
	{
		if ( ! class_exists('HTML_Parser', FALSE))
		{
			// Load Ganon
			require Kohana::find_file('vendor', 'ganon/ganon');
		}

	}

	public static function ac($controller, $action = NULL)
	{
		return ' class="'.(Delivery::active($controller, $action) ? 'active' : NULL).'"';
	}

	public static function active($controller, $action = NULL)
	{
		// Get current request
		$request = Request::current();

		if (UTF8::strtolower($request->controller()) !== UTF8::strtolower($controller))
		{
			return FALSE;
		}
		
		if ($action AND UTF8::strtolower($request->action()) !== UTF8::strtolower($action))
		{
			return FALSE;
		}

		return TRUE;
	}

} // End Delivery