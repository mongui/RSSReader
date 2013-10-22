<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Login System Controllers.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Login extends ControllerBase
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

		$this->load->model('manage_users');
	}

	/**
	 * Sessions
	 * 
	 * Recovers the userdata from database and prepares a session.
	 */
	private function sessions($userdata)
	{
		$_SESSION['id']			= $userdata->id_user;
		$_SESSION['username']	= $userdata->username;
		$_SESSION['email']		= $userdata->email;
		$_SESSION['timeformat']	= $userdata->time_format;
		$_SESSION['language']	= $userdata->language;
		$_SESSION['lastactive']	= time();
	}

	/**
	 * Index
	 * 
	 * If the user...
	 * - is logged, sends him to the RSSReader (main) controller.
	 * - is logging in, checks if the inserted data is correct.
	 * - is not logged, sends him the login form.
	 */
	public function index()
	{
		// Is the user logging in?
		if ($_POST) {
			$username = filter_var(strtolower($_POST['username']), FILTER_SANITIZE_STRING);
			$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
			$remember = filter_var($_POST['remember'], FILTER_SANITIZE_STRING);

			$userdata = $this->manage_users->login_user($username, $password);

			if ($userdata) {
				if ($remember == 'yes') {
					setcookie('rss_sess', $userdata->auth_key, time() + 60 * 60 * 24 * 365, '/', 'localhost', FALSE, TRUE);
				}

				$this->sessions($userdata);
				echo 'success';
			} else {
				echo 'failure';
			}
		} elseif (isset($_SESSION['id'])) {
			// Is the user already logged?
			$this->load->helper('url');
			redirect(site_url());
		} elseif (isset($_COOKIE['rss_sess'])) {
			// Autologin?
			$cookie = filter_var($_COOKIE['rss_sess'], FILTER_SANITIZE_STRING);

			$userdata = $this->manage_users->check_auth_key($cookie);

			if ($userdata) {
				$this->sessions($userdata);
				$this->load->helper('url');
				redirect(site_url());
			}
		} else {
			// Show the login form.
			$data = array();

			$this->load->helper('phone');
			if (is_phone()) {
				$data['is_phone'] = TRUE;
			}

			$this->load->library('minifier');
			$this->load->helper('url');
			$html = $this->load->view('login', $data, TRUE);
			echo $this->minifier->minify_html($html);
		}
	}

	/**
	 * Password recovery
	 */
	public function recover()
	{
		if (isset($_POST['email'])) {

			$email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

			$userdata = $this->manage_users->check_email($email);

			if ($userdata) {
				$newpass = $this->manage_users->random_string();
				$this->manage_users->change_password($userdata->id_user, $newpass);
				$cookie_auth = $this->manage_users->update_auth_key($userdata->id_user);

				$to = $userdata->email;
				$subject = "RSS Reader account management";
				$body = "Hello " . $userdata->username . ",\n\nYou are receiving this notification because you have requested a new password be sent. If you did not request this notification then please ignore it.\nYour new password is: " . $newpass . ".\n\nThe RSS Reader Team.";
				$headers = "From: RSS Reader";

				mail($to, $subject, $body, $headers);
			}

			echo 'success';
		}
	}

	/**
	 * Register
	 */
	public function register()
	{
		if ($_POST) {
			$username	= filter_var(strtolower($_POST["username"]), FILTER_SANITIZE_STRING);
			$password	= filter_var($_POST["password"], FILTER_SANITIZE_STRING);
			$email		= filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

			$userdata = $this->manage_users->check_email($email);
			if ($userdata) {
				echo 'email';
				return FALSE;
			}

			$userdata = $this->manage_users->check_user($username);
			if ($userdata) {
				echo 'user';
				return FALSE;
			}

			$userdata = $this->manage_users->register_user($username, $password, $email);
			if (is_numeric($userdata) && $userdata > 0) {
				echo 'success';
				return FALSE;
			}
		}

		echo 'failure';
	}


	/**
	 * Demo (if activated in the database)
	 */
	public function demo()
	{
		$this->load->model('configuration');

		if ($this->config->get('demo')) {
			$data->id_user		= 1;
			$data->username		= 'demo';
			$data->email		= 'demo@demo.com';
			$data->time_format	= 'd/m/Y';
			$data->language		= 'en';

			$this->sessions($data);
		}

		$this->load->helper('url');
		redirect( site_url() );
	}
}
