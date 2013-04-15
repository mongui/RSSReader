<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Login extends ControllerBase
{
	function __construct()
	{
		parent::__construct();

		session_start ();

		//http://simplepie.org/wiki/reference/start
		$this->load->helper('url');
		$this->load->model('connections');
		$this->load->model('manage_users');
	}

	private function sessions( $userdata )
	{
		$_SESSION['id']			= $userdata->id_user;
		$_SESSION['username']	= $userdata->username;
		$_SESSION['email']		= $userdata->email;
		$_SESSION['timeformat']	= $userdata->time_format;
		$_SESSION['language']	= $userdata->language;
		$_SESSION['lastactive']	= time();
	}

	public function index()
	{
		// Is the user logging in?
		if ( $_POST )
		{
			$username = filter_var(strtolower($_POST["username"]), FILTER_SANITIZE_STRING);
			$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
			$remember = filter_var($_POST["remember"], FILTER_SANITIZE_STRING);

			$userdata = $this->manage_users->login_user ($username, $password);

			if ( $userdata )
			{
				if ( $remember == 'yes' )
				{
					$cookie_auth = $this->manage_users->update_auth_key($userdata->id_user);
					setcookie("rss_sess", $cookie_auth, time() + 60 * 60 * 24 * 7, "/", "localhost", false, true);
				}

				$this->sessions($userdata);
				echo 'success';
			}
			else
			{
				echo 'failure';
			}
		}

		// Is the user already logged?
		elseif ( isset($_SESSION['id']) )
		{
			$this->load->helper('url');
			redirect( site_url() );
		}

		// Autologin?
		elseif ( isset($_COOKIE['rss_sess']) )
		{
			$cookie = filter_var($_COOKIE['rss_sess'], FILTER_SANITIZE_STRING);

			$userdata = $this->manage_users->check_auth_key($cookie);

			if ( $userdata )
			{
				$this->sessions($userdata);
				$this->load->helper('url');
				redirect( site_url() );
			}
		}
		// Show the login form.
		else
		{
			$this->load->view('login', NULL);
		}
	}

	public function logout()
	{
		if ( isset($_COOKIE['rss_sess']) )
			setcookie("rss_sess", "", time() - 3600, "/", "localhost", false, true);

		session_destroy();

		$this->load->helper('url');
		redirect( site_url('login') );
	}

	public function recover()
	{
		if ( isset($_POST['email']) ) {

			$email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

			$userdata = $this->manage_users->check_email($email);

			if ( $userdata )
			{
				$newpass = $this->manage_users->random_string();
				$this->manage_users->change_password($userdata->id_user, $newpass);

				$to = $userdata->email;
				$subject = "RSS Reader account management";
				$body = "Hello " . $userdata->username . ",\n\nYou are receiving this notification because you have requested a new password be sent. If you did not request this notification then please ignore it.\nYour new password is: " . $newpass . ".\n\nThe RSS Reader Team.";
				$headers = "From: RSS Reader";

				mail($to, $subject, $body, $headers);
			}

			echo 'success';
		}
	}

	public function register()
	{
		if ( $_POST )
		{
			$username	= filter_var(strtolower($_POST["username"]), FILTER_SANITIZE_STRING);
			$password	= filter_var($_POST["password"], FILTER_SANITIZE_STRING);
			$email		= filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

			$userdata = $this->manage_users->check_email($email);
			if ( $userdata )
			{
				echo 'email';
				return FALSE;
			}

			$userdata = $this->manage_users->check_user($username);
			if ( $userdata )
			{
				echo 'user';
				return FALSE;
			}

			$userdata = $this->manage_users->register_user($username, $password, $email);
			if ( is_numeric($userdata) && $userdata > 0 )
			{
				echo 'success';
				return FALSE;
			}
		}

		echo 'failure';
	}
}