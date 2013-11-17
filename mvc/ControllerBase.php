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
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		//parent::__construct();
	}

	/**
	 * Get instance from MVCious.
	 *
	 * @access	public
	 * @param	mixed
	 * @return	array
	 */
	function __get($key)
	{
		$inst =& get_instance();
		return $inst->$key;
	}
}
