<?php defined('SYSPATH') or die('No direct script access.');

class HTTP_Exception extends Kohana_HTTP_Exception {

	/**
	 * Display fancy errors, lets hope the exception
	 * thrown ain't in our templates (fingers crossed).
	 */
	public function get_response()
	{
		// Log this exception
		Kohana_Exception::log($this);

		// Factorize the response
		$response = Response::factory()
		->status($this->getCode());

		$template = View::factory('template')
		->set('hide_header', TRUE)
		->set('no_main', TRUE);

		$template->view = View::factory('exception');
		$template->view->item = $this;

		// TODO: add stack trace (for admins and ?stacktrace).
		// TODO: add better messages what went wrong (for normal users).
		// TODO: add possible next steps as call to action.

		$response->body($template->render());

		return $response;
	}

} // End HTTP Exception