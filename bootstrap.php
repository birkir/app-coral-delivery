<?php defined('SYSPATH') or die('No direct script access.');

// -- Environment setup --------------------------------------------------------

// Load the core Kohana class
require SYSPATH.'classes/Kohana/Core'.EXT;

if (is_file(APPPATH.'classes/Kohana'.EXT))
{
	// Application extends the core
	require APPPATH.'classes/Kohana'.EXT;
}
else
{
	// Load empty core extension
	require SYSPATH.'classes/Kohana'.EXT;
}

/**
 * Set the default time zone.
 *
 * @link http://kohanaframework.org/guide/using.configuration
 * @link http://www.php.net/manual/timezones
 */
date_default_timezone_set('Atlantic/Reykjavik');

/**
 * Set the default locale.
 *
 * @link http://kohanaframework.org/guide/using.configuration
 * @link http://www.php.net/manual/function.setlocale
 */
setlocale(LC_ALL, 'is_IS.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @link http://kohanaframework.org/guide/using.autoloading
 * @link http://www.php.net/manual/function.spl-autoload-register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Optionally, you can enable a compatibility auto-loader for use with
 * older modules that have not been updated for PSR-0.
 *
 * It is recommended to not enable this unless absolutely necessary.
 */
//spl_autoload_register(array('Kohana', 'auto_load_lowercase'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @link http://www.php.net/manual/function.spl-autoload-call
 * @link http://www.php.net/manual/var.configuration#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

/**
 * Set the mb_substitute_character to "none"
 *
 * @link http://www.php.net/manual/function.mb-substitute-character.php
 */
mb_substitute_character('none');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('en-us');

// Set cookie salt
Cookie::$salt = $_SERVER['COOKIE_SALT'];

if (isset($_SERVER['SERVER_PROTOCOL']))
{
	// Replace the default protocol.
	HTTP::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

// Set site url
define('SITE_URL', 'http://delivery.pipe.is');
define('SITE_NAME', 'Delivery Pipe');

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOHANA_ENV']))
{
	Kohana::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - integer  cache_life  lifetime, in seconds, of items cached              60
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 * - boolean  expose      set the X-Powered-By header                        FALSE
 */
Kohana::init(array(
	'base_url'   => '/',
	'index_file' => ''
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	'swiftmail'  => MODPATH.'swiftmail',  // Swift Mailer
	'oauth2'     => MODPATH.'oauth2',     // OAuth2 Client
	'mysqli'     => MODPATH.'mysqli',     // MySQLi database driver
	'auth'       => MODPATH.'auth',       // Basic authentication
	'database'   => MODPATH.'database',   // Database access
	'minion'     => MODPATH.'minion',     // CLI Tasks
	'orm'        => MODPATH.'orm'         // Object Relationship Mapping
	));


// Account profile router
Route::set('profile', 'profile')
	->defaults(array('controller' => 'account'));

// Account login, register, reset and logout
Route::set('account', '<action>', array('action' => '(login|logout|register|reset)'))
	->defaults(array('controller' => 'account'));

// Account link and unlink social network
Route::set('link', '<action>/<id>', array('action' => '(link|unlink)'))
	->defaults(array('controller' => 'account'));

// Package hooks list
Route::set('hooks', 'package/<id>/hooks')->defaults(array('controller' => 'hook'));

// Package hook actions
Route::set('hook', 'package/<id>/hook(/<hook>/<action>)', array('action' => '(edit|delete)'))
	->defaults(array('controller' => 'hook', 'action' => 'add'));

// Add package
Route::set('add_package', 'package/add')->defaults(array('controller' => 'package', 'action' => 'add'));
Route::set('add_service', 'service/add')->defaults(array('controller' => 'service', 'action' => 'add'));

// Default router
Route::set('default', '(<controller>(/<id>(/<action>)))')
	->filter(function($route, $params, $request)
	{
		// Singularize the controller name
		$singular = UTF8::ucfirst(Inflector::singular($params['controller']));

		if (empty($params['id']) AND $singular === $params['controller'])
		{
			// Don't allow singular controller with no id specified
			return FALSE;
		}

		// Set controller as singular for "list" view
		$params['controller'] = $singular;

		if ( ! empty($params['id']) AND $params['action'] === 'index')
		{
			// Change default action to detail if none given.
			$params['action'] = 'detail';
		}

		// Pass changed parameters
		return $params;
	})
	->defaults(array(
		'controller' => 'packages',
		'action'     => 'index',
	));
