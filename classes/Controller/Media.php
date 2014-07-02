<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Media Controller
 *
 * @package    Coral
 * @category   Controller
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Controller_Media extends Controller {

	/**
	 * Process the file
	 *
	 * @param   string  filename
	 * @return  void
	 */
	public function action_media()
	{
		$filename = $this->request->param('file');
		$info = pathinfo($filename);
		$root = APPPATH.'media/';

		// we have combined file here
		if (strlen($info['filename']) === 43 AND substr($info['filename'], -3) === '.km')
		{
			$root = APPPATH.'cache/media/';
		}

		$file = pathinfo($filename, PATHINFO_FILENAME);
		$filename = $root.$filename;

		if ( ! file_exists($filename))
			throw HTTP_Exception::factory(404, 'This file is not available.');

		// Set response body and headers
		$this->check_cache(sha1($this->request->uri()).filemtime($filename));
		$this->response->body(file_get_contents($filename));
		$this->response->headers('content-type', File::mime_by_ext($info['extension']));
		$this->response->headers('last-modified', date('r', filemtime($filename)));
	}

} // End Media