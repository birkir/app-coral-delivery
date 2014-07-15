<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Template Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Template extends Controller {

	/**
	 * @var string Template filename
	 */
	public $template = 'template';

	/**
	 * @var View variable
	 */
	public $view;

	/**
	 * @var array Allowed controllers and actions
	 */
	public $allowed = array('Account' => array('login', 'register', 'link', 'reset'), 'Media');

	/**
	 * @var bool  Auto render template
	 */
	public $auto_render = TRUE;

	/**
	 * Before controller execution
	 *
	 * @uses   Auth
	 * @uses   View
	 * @return void
	 */
	public function before()
	{
		// Create auth instance
		$this->auth = Auth::instance();

		// Get authenticated user
		$this->user = $this->auth->get_user();

		if ( ! $this->auth->logged_in())
		{
			$redirect = TRUE;
			$controller = $this->request->controller();
			$action = $this->request->action();

			foreach ($this->allowed as $ctl => $act)
			{
				if ((is_array($act) AND $ctl === $controller AND in_array($action, $act)) OR ( ! is_array($act) AND $ctl === $controller))
				{
					$redirect = FALSE;
				}
			}

			if ($redirect)
			{
				HTTP::redirect('login');
			}
		}
		else
		{
			I18n::lang($this->user->language);
		}

		// Setup View template
		$this->template = View::factory($this->template);

		// Set global variables for template
		View::set_global('auth', $this->auth);
		View::set_global('user', $this->user);
	}

	/**
	 * After controller execution
	 *
	 * @return void
	 */
	public function after()
	{
		if ($this->template instanceof View)
		{
			// Add main view to template
			$this->template->view = $this->view;
		}

		// Attach template view to body response
		$this->response->body(($this->request == Request::initial() AND $this->auto_render === TRUE) ? $this->template : $this->view);
	}

} // End Template