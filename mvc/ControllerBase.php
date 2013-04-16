<?php if ( !defined('MVCious')) exit('No direct script access allowed');

abstract class ControllerBase
{
	private static $inst;

	function __construct()
	{
		$this->config = new Config();
		$this->load = new Load();

		global $config;

		$this->config->set_array($config);
		self::$inst =& $this;
	}

	public static function &get_instance()
	{
		return self::$inst;
	}
}
?>