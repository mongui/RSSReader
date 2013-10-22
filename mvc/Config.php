<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Config Class
 *
 * Manages the config.php file inside the framework.
 *
 * @package		MVCious
 * @subpackage	Core
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Config
{
	/**
	 * Array where the config options are stored.
	 *
	 * @var		array
	 * @access	private
	 */
	private $_vars;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->_vars = array();
	}

	/**
	 * Set
	 *
	 * Sets or changes a config option.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function set($name, $value)
	{
		$this->_vars[$name] = $value;
	}

	/**
	 * Get
	 *
	 * Returns the config value assigned to the inserted key.
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	public function get($name)
	{
		if (isset($this->_vars[$name])) {
			return $this->_vars[$name];
		}
	}

	/**
	 * Set Array
	 *
	 * Sets or changes a complete config array.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_array($arr)
	{
		foreach ($arr as $key => $value) {
			$this->set($key, $value);
		}
	}
}
