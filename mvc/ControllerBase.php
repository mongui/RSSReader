<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * ControllerBase Class
 *
 * Allows the controller to be part of the framework.
 *
 * @package		MVCious
 * @subpackage	Core
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
abstract class ControllerBase
{
	private static $_inst;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->config = new Config();
		$this->load = new Load();

		global $config;

		$this->config->set_array($config);
		self::$_inst =& $this;
	}

	/**
	 * Get instance from MVCious.
	 *
	 * @access	public
	 * @param	mixed
	 * @return	array
	 */
	public static function &get_instance()
	{
		return self::$_inst;
	}
}
