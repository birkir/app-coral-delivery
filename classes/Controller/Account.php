<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Account Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Account extends Controller_Template {

	/**
	 * Redirect to login page
	 *
	 * @return void
	 */
	public function action_index()
	{
		$this->view = View::factory('account/profile')
		->bind('languages', $languages);

		// Setup languages
		$languages = array(
			'en-us' => 'English',
			'is-is' => 'Íslenska'
		);

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Create encrypt instance
			$encrypt = Encrypt::instance();

			// Extract keys from post data
			$email = $this->request->post('email');
			$password = $this->request->post('password');
			$password_confirm = $this->request->post('password_confirm');

			if ($this->user->email !== $email)
			{
				if (ORM::factory('User', array('email' => $email))->loaded())
				{
					$this->view->error = 'Email address already in use by another account. Duplicate accounts?';

					return;
				}

				// Attempt to update email
				$link = URL::base().'/account/confirm/email?hash='.$encrypt->encode(json_encode(array($this->user->id, time(), $email)));

				// Send email to new email address
				mail($email, 'Confirm your new email address', View::factory('account/mail/confirm-email')->set('link', $link), implode("\r\n", array(
					'MIME-Version: 1.0',
					'Content-type: text/html; charset=utf-8',
					'To: '.$this->user->fullname.' <'.$email.'>',
					'From: Coral Delivery <www-data@corona.forritun.org>'
				)));
			}

			if ( ! empty($password) AND ($password === $password_confirm))
			{
				// Attempt to update email
				$link = URL::base().'/account/confirm/password?hash='.$encrypt->encode(json_encode(array($this->user->id, time(), $password)));

				// Send email to new email address
				mail($email, 'Confirm your password change', View::factory('account/mail/confirm-password')->set('link', $link), implode("\r\n", array(
					'MIME-Version: 1.0',
					'Content-type: text/html; charset=utf-8',
					'To: '.$this->user->fullname.' <'.$email.'>',
					'From: Coral Delivery <www-data@corona.forritun.org>'
				)));
			}

			// Get post values
			$post = $this->request->post();

			// Unset custom behaviour keys
			unset($post['email']);
			unset($post['password']);
			unset($post['password_confirm']);

			// Update user values
			$this->user->values($post)->save();

			// Set language
			I18n::lang($this->user->language);
		}
	}

	/**
	 * Handle email and password confirmation
	 *
	 * @return void
	 */
	public function action_confirm()
	{
		// setup encrypt instance
		$encrypt = Encrypt::instance();

		// Get type
		$type = $this->request->param('id');

		// Get hash
		$hash = $this->request->query('hash');

		// Attempt to decrypt hash
		try {
			$data = json_decode($encrypt->decode($hash), TRUE);
		} catch (Exception $e) {
			$data = array();
		}

		if ( ! in_array($type, array('email','password')) OR empty($hash) OR Arr::get($data, 1, 0) < (time() - 86400))
		{
			throw HTTP_Exception(500, 'Token expired or method not available.');
		}

		// Get user
		$user = ORM::factory('User', $data[0]);

		if ($type === 'email')
		{
			// Set new email
			$user->email = $data[2];

			// Save the user
			$user->save();

			// Show email confirmation success
			$this->view = View::factory('account/confirmed-email');
		}

		if ($type === 'password')
		{
			// Update the user
			$user->update_user(array(
				'password' => $data[2],
				'password_confirm' => $data[2]
			));

			// Save the user
			$user->save();

			// Log the user out
			$this->auth->logout();

			// Show password confirmation success
			$this->view = View::factory('account/confirmed-password');
		}

	}

	/**
	 * Show login form and process controller
	 *
	 * @uses   HTTP
	 * @return void
	 */
	public function action_login()
	{
		// Setup login view
		$this->view = View::factory('account/login')
		->bind('failed', $failed);

		// Set template flags
		$this->template->hide_header = TRUE;
		$this->template->no_main = TRUE;

		if ($this->request->method() === HTTP_Request::POST)
		{
			// Extract keys from POST data
			$email = $this->request->post('email');
			$password = $this->request->post('password');
			$remember = ($this->request->post('remember') == 1);

			if ( ! $this->auth->login($email, $password, $remember))
			{
				$failed = TRUE;
			}
		}

		if ($this->auth->logged_in())
		{
			return HTTP::redirect('account');
		}
	}

	/**
	 * Show register form and process controller
	 *
	 * @return void
	 */
	public function action_register()
	{
		// Setup register form
		$this->view = View::factory('account/register')
		->bind('errors', $errors);

		if ($this->request->method() === HTTP_Request::POST)
		{
			try
			{
				// Create user using Auth create_user method
				ORM::factory('User')
				->create_user($this->request->post(), array('email', 'password'));

				// Add logged in role
				$item->add('roles', ORM::factory('Role', array('name' => 'login')));
			}
			catch (ORM_Validation_Exception $e)
			{
				$errors = $e;
			}
		}
	}

	/**
	 * Logout controller
	 * 
	 * @uses   HTTP
	 * @return void
	 */
	public function action_logout()
	{
		// Log user out
		$this->auth->logout();

		// Redirect to login
		return HTTP::redirect('account/login');
	}

	/**
	 * Open Authentication register or login
	 *
	 * @return void
	 */
	public function action_oauth()
	{
		if ($this->request->query('error'))
		{
			// Proxy login page
			$this->action_login();

			// Set errors
			$this->view->oauth_errors = $this->request->query();

			return;
		}

		// Get allowed OAuth methods
		$methods = array('facebook', 'google', 'github');

		// Get requested method
		$method = $this->request->param('id');

		if (empty($method))
			return HTTP::redirect('account/login');

		if ( ! in_array($method, $methods))
		{
			throw HTTP_Exception::factory(500, 'Authentication method :method not allowed.', array(
				':method' => $method));
		}

		// Get OAuth Config
		$config = Kohana::$config->load('oauth2')->get($method);

		// Setup OAuth client
		$client = OAuth2_Client::factory(UTF8::ucfirst($method), Arr::get($config, 'client_id'), Arr::get($config, 'client_secret'));

		if ($this->request->query('code'))
		{
			// Setup parameters
			$params = array(
				'code'         => $this->request->query('code'),
				'redirect_uri' => Arr::get($config, 'redirect_uri')
			);

			// Get access token
			$token = $client->get_access_token(OAuth2_Client::GRANT_TYPE_AUTHORIZATION_CODE, $params);

			// Set client access token
			$client->set_access_token($token);

			// Get user data
			$data = $client->get_user_data();

			// Get auth token
			$auth = ORM::factory('User_Auth')
			->where('method', '=', $method)
			->where('identifier', '=', Arr::get($data, 'id'))
			->find();

			if ( ! $auth->loaded())
			{
				// Find user by email
				$user = ORM::factory('User', array('email' => Arr::get($data, 'email')));

				if ( ! $user->loaded())
				{
					// Create User
					$user->email = Arr::get($data, 'email');
					$user->fullname = Arr::get($data, 'name');
					$user->save();
				}

				// Assign this method to user oauths
				$auth->identifier = Arr::get($data, 'id');
				$auth->method = $method;
				$auth->user_id = $user->id;
			}

			// Update oauth token
			$auth->data = json_encode($data);
			$auth->token = $token;
			$auth->updated_at = date('Y-m-d H:i:s');
			$auth->save();

			// Login user
			Auth::instance()->force_login($auth->user);

			// Redirect to profile
			return HTTP::redirect('account');
		}
		else
		{
			// Get the authorization url
			$auth_url = $client->get_authentication_url(Arr::get($config, 'redirect_uri'), array(
				'scope' => 'email'
			));

			// Redirect to the authorization url
			HTTP::redirect($auth_url);
		}
	}

	/**
	 * Unlink OAuth2 connection
	 *
	 * @return void
	 */
	public function action_unlink()
	{
		// Get auth connection
		$auth = ORM::factory('User_Auth', $this->request->param('id'));

		if ($auth->user->id !== $this->user->id AND ! $this->user->is_admin())
		{
			// Dont allow unlinking others authentications
			throw HTTP_Exception::factory(401, 'Not allowed');
		}

		// Delete authentication
		$auth->delete();

		// Redirect to account profile
		HTTP::redirect('account');
	}


	public function action_test()
	{
		if ( ! empty($this->request->param('id')))
		{
			$request = Request::factory('http://www.17track.net/f/MyCaptchaHandler.ashx?get=validationresult&i='.$this->request->param('id').'&d=0.1563');
		
			$request->client()
			->options(CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie')
			->options(CURLOPT_COOKIEFILE, APPPATH.'cache/17track.cookie')
			->options(CURLOPT_FOLLOWLOCATION, TRUE);

			$response = $request->execute();

			echo Debug::vars($response);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://s1.17track.net/Rest/HandlerTrackPost.ashx?callback=data&lo=www.17track.net&pt=0&num=RC971420354CN&hs=207f7ee621120a7d1a84775ca5c33aea');
			curl_setopt($ch, CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$body = curl_exec($ch);

			echo Debug::vars($body);
			exit;
		}

		$request = Request::factory('http://www.17track.net/f/MyCaptchaHandler.ashx?get=image&d=&d=0.1563');

		$request->client()
		->options(CURLOPT_COOKIEJAR, APPPATH.'cache/17track.cookie')
		->options(CURLOPT_COOKIEFILE, APPPATH.'cache/17track.cookie')
		->options(CURLOPT_FOLLOWLOCATION, TRUE);

		$image = $request->execute();

		$this->auto_render = FALSE;
		$this->response->headers('content-type', 'image/jpeg');
		$this->view = $image->body();
	}

} // End Account