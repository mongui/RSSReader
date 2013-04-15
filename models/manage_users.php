<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Manage_users extends ModelBase
{
	private $conn;

	function __construct ()
	{
		parent::__construct();

		$this->conn = $this->db;
	}

	function register_user ( $username, $password, $email )
	{
		$strings = $this->hash_generator($password);

		$hash = $strings['hash'];
		$salt = $strings['salt'];

		$sql = "INSERT INTO users (username, password, salt, email) VALUES ('$username', '$hash', '$salt', '$email')";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $this->conn->lastInsertId('id_user');
	}

	function update_user ( $userdata )
	{
		if ( isset($userdata['password']) && isset($userdata['newpassword']) )
		{
			$user_db = $this->login_user($_SESSION['username'], $userdata['password']);
			if ( is_object($user_db) && $user_db->id_user == $_SESSION['id'] )
				return $this->change_password($user_db->id_user, $userdata['password']);
		}
		return FALSE;
	}

	function login_user ( $username, $password )
	{
		$sql = "
				SELECT *
				FROM users
				WHERE username = '$username' AND password = MD5(CONCAT('$password', salt))
			";
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	function check_user ( $username )
	{
		$sql = "
				SELECT *
				FROM users
				WHERE username = '$username'
				LIMIT 1
			";
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	function check_auth_key ( $auth_key )
	{
		$sql = "
				SELECT *
				FROM users
				WHERE auth_key = '$auth_key'
				LIMIT 1
			";
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	function check_email ( $email )
	{
		$sql = "
				SELECT *
				FROM users
				WHERE email = '$email'
				LIMIT 1
			";
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	function change_password ( $user_id, $newpass )
	{
		$data = $this->hash_generator($newpass);
		$hash = $data['hash'];
		$salt = $data['salt'];

		$sql = "
				UPDATE users
				SET password = '$hash', salt = '$salt'
				WHERE id_user = $user_id
			";

		$dbdata = $this->conn->prepare($sql);

		return $dbdata->execute();
	}

	function update_auth_key ( $user_id )
	{
		$string = $this->random_string(20);

		$sql = "
				UPDATE users
				SET auth_key = '$string'
				WHERE id_user = $user_id
			";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $string;
	}

	function random_string ( $min_size = 10, $max_size = NULL)
	{
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		if ( $max_size )
			$str_size = mt_rand($min_size, $max_size);
		else
			$str_size = $min_size;

		for ($i = 0; $i < $str_size; $i++)
			$string[$i] = substr($chars, mt_rand(0, strlen($chars) - 1), 1);

		return implode("", $string);
	}

	private function hash_generator ( $string )
	{
		$salt = $this->random_string (15, 25);

		return array(
				'hash'				=> hash('md5', $string . $salt),
				'salt'				=> $salt
			);
	}
}