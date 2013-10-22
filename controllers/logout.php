<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * LogOut System Controllers.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Logout extends ControllerBase
{
	/**
	 * Index
	 * 
	 * If the user is logged, logs him out.
	 */
	public function index()
	{
		if (!isset($_SESSION)) {
			session_start();
		}
		
		if (isset($_COOKIE['rss_sess'])) {
			setcookie("rss_sess", "", time() - 3600, "/", "localhost", false, true);
		}

		if (isset($_SESSION)) {
			session_destroy();
		}
		
		$this->load->helper('url');
		redirect(site_url('login'));
	}
}
