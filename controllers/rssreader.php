<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Main Controller.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Rssreader extends ControllerBase
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (!isset($_SESSION)) {
			session_start();
		}
		
		$this->load->model('configuration');
	}

	/**
	 * Index
	 */
	public function index()
	{
		$this->load->helper('url');

		// Is the user logged?
		if (isset($_SESSION['id'])) {
			$data = array();
			$data['feed_updatable'] = $this->config->get('feed_updatable');

			$this->load->helper('phone');
			if (is_phone()) {
				$data['is_phone'] = TRUE;
			}

			$html = $this->load->view('main', $data, TRUE);
			$this->load->library('minifier');
			echo $this->minifier->minify_html($html);
		} else {
			// Send him to login form.
			redirect(site_url('login'));
		}
	}

	/**
	 * Preferences
	 * 
	 * If $_POST is set, saves the user config.
	 * If not, sends the view.
	 */
	public function preferences()
	{
		if(isset($_POST['timeformat']) && isset($_POST['language'])) {
			$userdata = array (
							'time_format'	=> filter_var($_POST['timeformat'], FILTER_SANITIZE_STRING),
							'language'		=> filter_var($_POST['language'], FILTER_SANITIZE_STRING)
						);

			if ($_POST['curPassword'] && $_POST['newPassword']) {
				$userdata['password']	= $_POST['curPassword'];
				$userdata['newpassword']= $_POST['newPassword'];

				$this->load->model('manage_users');
				$rtrn = $this->manage_users->update_user($userdata);
			}

			if ($_POST['timezone']) {
				$serverdata = array (
								'timezone'					=> filter_var($_POST['timezone'], FILTER_SANITIZE_STRING),
								'minutes_between_updates'	=> filter_var($_POST['mins_updates'], FILTER_VALIDATE_INT),
								'max_feeds_per_update'		=> filter_var($_POST['max_feeds'], FILTER_VALIDATE_INT),
								'show_favicons'				=> ( $_POST['show_favicons']  ) ? $_POST['show_favicons']  : 'false',
								'feed_updatable'			=> ( $_POST['feed_updatable'] ) ? $_POST['feed_updatable'] : 'false'
							);

				$this->configuration->save_config($serverdata);
			}

			if (isset($rtrn) || (!$_POST['curPassword'] && !$_POST['newPassword'])) {
				$_SESSION['timeformat']	= $userdata['time_format'];
				$_SESSION['language']	= $userdata['language'];
				echo 'success';
			} else {
				echo 'curPass';
			}
		} else {
			$data = NULL;
			if ($this->config->get('admin') == $_SESSION['id']) {
				$data['is_admin']	= TRUE;
				$data['timezones']	= file($this->config->get('app_path') . 'timezones.txt');
				$data['timezones']	= array_map('trim', $data['timezones']);
			}

			$data['timezone']				= $this->config->get('timezone');
			$data['minutes_between_updates']= $this->config->get('minutes_between_updates');
			$data['max_feeds_per_update']	= $this->config->get('max_feeds_per_update');
			$data['show_favicons']			= $this->config->get('show_favicons');
			$data['feed_updatable']			= $this->config->get('feed_updatable');

			$this->load->library('minifier');
			$html = $this->load->view('preferences', $data, TRUE);
			echo $this->minifier->minify_html($html);
		}
	}
}
