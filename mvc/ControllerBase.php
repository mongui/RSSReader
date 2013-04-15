<?php if ( !defined('MVCious')) exit('No direct script access allowed');

abstract class ControllerBase
{
	private static $inst;

	function __construct()
	{
		$this->config = new Config();
		$this->load = new Load();

		global $folders, $database;

		$this->config->set('folders', $folders);
		$this->config->set('database', $database);
		self::$inst =& $this;
	}

	public static function &get_instance()
	{
		return self::$inst;
	}
}
?>