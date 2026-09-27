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
			ini_set('session.gc_maxlifetime', $this->config->get('session_timeout'));
			session_set_cookie_params($this->config->get('session_timeout'));
			session_start();
		}
		
		$this->load->model('configuration');
		$this->load->helper('lang');
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
		// Session lost: AJAX calls get a 401 and the page sends the user to the login.
		if (!isset($_SESSION['id'])) {
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}

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

			// Only the admin can change the server configuration.
			if ($_POST['timezone'] && $this->config->get('admin') == $_SESSION['id']) {
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

				$this->load->model('connections');
				$data['feed_list']	= $this->connections->get_all_feeds();
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

	public function img($num = 1)
	{
		$x = 32;
		$y = 0;

		$newWidth = 32;
		$font_size = 5;

		if ($num > 99) {
			$num = "+99";
		}

		$src = imagecreatefrompng("public_data/images/favicon.png");

		list($width, $height) = array(imagesx($src), imagesy($src));

		$newHeight = ($height / $width) * $newWidth;
		$trg = imagecreatetruecolor($newWidth, $newHeight);

		imagealphablending($trg, false);
		imagesavealpha($trg, true);
		imagecopyresampled($trg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		$colorBg = imagecolorallocate($trg, 255, 255, 255);
		imagestring($trg, $font_size, $x -2 - ((strlen($num) * imagefontwidth($font_size))), $y -1, $num, $colorBg);
		imagestring($trg, $font_size, $x -2 - ((strlen($num) * imagefontwidth($font_size))), $y +1, $num, $colorBg);
		imagestring($trg, $font_size, $x - ((strlen($num) * imagefontwidth($font_size))), $y -1, $num, $colorBg);
		imagestring($trg, $font_size, $x - ((strlen($num) * imagefontwidth($font_size))), $y +1, $num, $colorBg);

		$color = imagecolorallocate($trg, 50, 50, 50);
		imagestring($trg, $font_size, $x -1 - ((strlen($num) * imagefontwidth($font_size))), $y, $num, $color);
		header('Content-type: image/png');
		imagepng($trg);

		imagedestroy($trg);
	}
}
