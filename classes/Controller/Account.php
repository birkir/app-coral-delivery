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
		->bind('languages', $languages)
		->bind('success', $success);

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
				$link = URL::base().'/confirm/email/'.$encrypt->encode(json_encode(array($this->user->id, time(), $email)));

				// Send email to new email address
				mail($email, 'Confirm your new email address', View::factory('mail/account/confirm-email')->set('link', $link), implode("\r\n", array(
					'MIME-Version: 1.0',
					'Content-type: text/html; charset=utf-8',
					'To: '.$this->user->fullname.' <'.$email.'>',
					'From: Coral Delivery <www-data@corona.forritun.org>'
				)));
			}

			if ( ! empty($password) AND ($password === $password_confirm))
			{
				// Attempt to update email
				$link = URL::base().'/confirm/password/'.$encrypt->encode(json_encode(array($this->user->id, time(), $password)));

				// Send email to new email address
				mail($email, 'Confirm your password change', View::factory('mail/account/confirm-password')->set('link', $link), implode("\r\n", array(
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

			// Show success message
			$success = TRUE;
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
			return HTTP::redirect('packages');
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

		// Set template flags
		$this->template->hide_header = TRUE;
		$this->template->no_main = TRUE;

		if ($this->request->method() === HTTP_Request::POST)
		{
			try
			{
				// Get post data
				$post = $this->request->post();

				// Create user using Auth create_user method
				$user = ORM::factory('User')
				->create_user($post, array('email', 'password'));


				if (ORM::factory('User')->count_all() === 1)
				{
					// Initialize database
					$this->initializeDatabase();

					// Add admin role to user
					$user->add('roles', ORM::factory('Role', array('name' => 'admin')));

				}

				// Add logged in role
				$user->add('roles', ORM::factory('Role', array('name' => 'login')));

			}
			catch (ORM_Validation_Exception $e)
			{
				// Set errors
				$errors = $e->errors('models');
			}
		}
	}

	/**
	 * Logs the user out
	 * 
	 * @uses   HTTP
	 * @return void
	 */
	public function action_logout()
	{
		// Log user out
		$this->auth->logout();

		// Redirect to login
		HTTP::redirect('login');
	}

	/**
	 * Reset user password
	 * 
	 * @uses   HTTP
	 * @return void
	 */
	public function action_reset()
	{
		// Setup register form
		$this->view = View::factory('account/reset-password')
		->bind('errors', $errors);

		// Set template flags
		$this->template->hide_header = TRUE;
		$this->template->no_main = TRUE;

	}

	/**
	 * Open Authentication register or login
	 *
	 * @return void
	 */
	public function action_link()
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
			return HTTP::redirect('login');

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

			try
			{
				// Get access token
				$token = $client->get_access_token(OAuth2_Client::GRANT_TYPE_AUTHORIZATION_CODE, $params);

				// Set client access token
				$client->set_access_token($token);
				$client->set_curl_option(CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Kohana v'.Kohana::VERSION.' +http://kohanaframework.org/)');

				// Get user data
				$data = $client->get_user_data();
			}
			catch (OAuth2_Exception $e)
			{
				throw HTTP_Exception::factory(500, 'Could not authenticate using :method.', array(':method' => $method), $e);
			}


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
					try
					{
						$user->email = Arr::get($data, 'email');
						$user->fullname = Arr::get($data, 'name');
						$user->password = 'oauth-only';
						$user->save();

						$role = ORM::factory('Role', array('name' => 'login'));
						$user->add('roles', $role);
					}
					catch (ORM_Validation_Exception $e)
					{
						echo Debug::vars($e->errors());
						return;
					}
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

			// Token data
			$data = array(
				'user_id'    => $auth->user->pk(),
				'expires'    => time() + 1209600,
				'user_agent' => sha1(Request::$user_agent),
			);

			// Create a new autologin token
			$token = ORM::factory('User_Token')
						->values($data)
						->create();

			// Set the autologin cookie
			Cookie::set('authautologin', $token->token, 1209600);

			// Redirect to profile
			return HTTP::redirect('packages');
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
			throw HTTP_Exception::factory(403, 'Not allowed');
		}

		// Delete authentication
		$auth->delete();

		// Redirect to account profile
		HTTP::redirect('profile');
	}

	/**
	 * Add roles and carriers to database
	 *
	 * @return void
	 */
	private function initializeDatabase()
	{
		$roles = array(
			'login' => 'Allows user to log in',
			'admin' => 'Administrator permissions'
		);

		foreach ($roles as $role => $name)
		{
			if (ORM::factory('Role', array('name' => $role))->loaded())
				continue;

			$item = ORM::factory('Role');
			$item->name = $role;
			$item->description = $name;
			$item->save();
		}

		$carriers = array(
			array(' - Auto detect - ', NULL, 0, NULL),
			array('Pósturinn', 'Iceland', 0, 'IS_Posturinn'),
			array('USPS', 'United States', 0, 'US_USPS'),
			array('Royal Mail', 'United Kingdom', 0, 'UK_RoyalMail'),
			array('DHL', NULL, 1, 'Express_DHL'),
			array('FedEx', NULL, 1, 'Express_FedEx'),
			array('TNT', NULL, 1, 'Express_TNT')
		);

		foreach ($carriers as $carrier)
		{
			if (ORM::factory('Carrier', array('name' => $carrier[0]))->loaded())
				continue;

			$item = ORM::factory('Carrier');
			$item->name = $carrier[0];
			$item->country = $carrier[1];
			$item->express = $carrier[2];
			$item->driver = $carrier[3];
			$item->save();
		}
	}

} // End Account
