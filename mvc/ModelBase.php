<?php if ( !defined('MVCious')) exit('No direct script access allowed');

abstract class ModelBase
{
	protected $db;
	protected $dbdata;

	public function __construct()
	{
		$inst =& get_instance();

		$this->dbdata = $inst->config->get('database');
		$this->database($this->dbdata);
	}

	function __get($key)
	{
		$inst =& get_instance();
		return $inst->$key;
	}

	protected function database( $connection, $tovar = FALSE )
	{
		$newdb = new PDO('mysql:host=' . $connection['dbhost'] . ';dbname=' . $connection['dbname'], $connection['dbuser'], $connection['dbpass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));
		if ( $tovar )
			return $newdb;
		else
			$this->db = $newdb;
	}
}
?>