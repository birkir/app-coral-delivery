<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Email Attachment Hook
 *
 * @package    Coral
 * @category   Hook
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Hook_Emailattachment extends Hook {

	/**
	 * Process hook save method
	 *
	 * @return boolean
	 */
	public function _fieldset_save(array $post = array())
	{
		// Decode JSON
		$data = json_decode($this->hook->data, TRUE);

		// Setup validation
		$validation = Validation::factory($post)
		->rule('email_subject', 'not_empty')
		->rule('email_recipent', 'not_empty');


		if (Upload::valid(Arr::get($_FILES, 'email_attachment')) AND ! empty($_FILES['email_attachment']['tmp_name']))
		{
			// Get filename and extension
			$file = pathinfo($_FILES['email_attachment']['name']);
			$filename = sha1($file['filename']).'.'.$file['extension'];

			// Save uploaded file
			move_uploaded_file($_FILES['email_attachment']['tmp_name'], APPPATH.'cache/uploads/'.$filename);

			// Set file, sent and email attributes
			$data['filename'] = $file['basename'];
			$data['filepath'] = $filename;
		}

		if (Arr::get($post, 'email_nofile') === 'true')
		{
			unset($data['filename']);
			unset($data['filepath']);
		}

		// Set optimal parameters
		$data['email_subject'] = Arr::get($post, 'email_subject');
		$data['email_body'] = Arr::get($post, 'email_body');
		$data['email_recipent'] = Arr::get($post, 'email_recipent');
		$data['sent']  = Arr::get($data, 'sent', 0);

		// Rewrite JSON
		$this->hook->data = json_encode($data);

		if ( ! $validation->check())
		{
			throw new ORM_Validation_Exception('hook-emailattachment', $validation);
		}

		return TRUE;
	}

	/**
	 * Append something to fieldset
	 *
	 * @return View
	 */
	public function _fieldset_view(array $post = array())
	{
		$view = View::factory('hook/email-attachment/fieldset')
		->set('post', $post)
		->set('data', json_decode($this->hook->data, TRUE));

		return $view;
	}

	/**
	 * Send the email
	 */
	public function send_email()
	{
		if (empty($this->data['email_recipent']) OR empty($this->data['email_subject']))
			return FALSE;

		// Allowed replacements
		$replacements = array(
			':tracking_number' => $this->package->tracking_number,
			':fullname'        => $this->package->user->fullname,
			':email'           => $this->package->user->email,
			':date'            => date('Y-m-d')
		);

		// Get body text
		$bodyText = __(Arr::get($this->data, 'email_body', 'Best regards,<br/>'.$this->package->user->fullname), $replacements);

		if ( ! empty($this->data['filepath']) AND file_exists(APPPATH.'cache/uploads/'.$this->data['filepath']))
		{
			// Setup file object
			$file = (object) array();
			$file->file   = APPPATH.'cache/uploads/'.$this->data['filepath'];
			$file->name   = $this->data['filename'];
			$file->mime   = File::mime($file->file);
			$file->base64 = chunk_split(base64_encode(file_get_contents($file->file)));

			$random_hash = md5(date('r', time()));
			$nl = "\r\n";

			$body  = '--mixed-'.$random_hash.$nl;
			$body .= 'Content-Type: multipart/alternative; boundary="content-'.$random_hash.'"'.$nl.$nl;
			
			$body .= '--content-'.$random_hash.$nl;
			$body .= 'Content-Type: text/plain; charset=utf-8'.$nl; 
			$body .= 'Content-Transfer-Encoding: 7bit'.$nl.$nl;
			$body .= $bodyText.$nl.$nl;

			$body .= '--mixed-'.$random_hash.$nl;
			$body .= 'Content-Type: '.$file->mime.'; name="'.$file->name.'"'.$nl;
			$body .= 'Content-Transfer-Encoding: base64'.$nl;
			$body .= 'Content-Disposition: attachment'.$nl;
			$body .= $file->base64.$nl;
			$body .= '--mixed-'.$random_hash;
		}
		else
		{
			$body = $bodyText;
		}

		$headers = array(
			'MIME-Version: 1.0',
			'Content-type: '.(isset($file) ? 'multipart/mixed; boundary="mixed-'.$random_hash.'"' : 'text/plain; charset=utf-8'),
			'Bcc: '.$this->package->user->email,
			'Reply-To: '.$this->package->user->email,
			'From: '.$this->package->user->fullname.' <www-data@corona.forritun.org>'
		);

		mail($this->data['email_recipent'], __($this->data['email_subject'], $replacements), $body, implode("\r\n", $headers));

		// Send notification to user
		mail($this->package->user->email, 'Coral Delivery :: '.__('Email with attachment was sent for :tracking_number', array(':tracking_number' => $this->package->tracking_number)), View::factory('mail/hook/emailattachment')->set('package', $this->package)->set('data', $this->data), implode("\r\n", array(
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=utf-8',
			'To: '.$this->package->user->fullname.' <'.$this->package->user->email.'>',
			'From: Coral Delivery <www-data@corona.forritun.org>'
		)));

		return TRUE;
	}

	/**
	 * Runs when new status is added to package
	 *
	 * @return boolean
	 */
	public function _status_hook($status)
	{
		// Check if email has already been sent
		if (intval(Arr::get($this->data, 'sent', 0)) === 1)
		{
			return FALSE;
		}

		// Set matching messages for IS_Posturinn carrier
		if ($this->package->destination_carrier->driver === 'IS_Posturinn')
		{
			if (preg_match('#Kom til landsins#s', $status->message))
			{
				// Send the email
				$this->send_email();

				// Set data
				$this->data['sent'] = 1;

				// Update hook
				$this->hook->data = json_encode($this->data);
				$this->hook->processed_at = date('Y-m-d H:i:s');
				$this->hook->save();

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Runs each time package is updated
	 *
	 * @return boolean
	 */
	public function _update_hook()
	{

	}

} // End Email Attachment Hook