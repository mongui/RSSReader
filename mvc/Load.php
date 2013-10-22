<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Load Class
 *
 * Allows to load controllers, models, libraries, views and helpers in the framework.
 *
 * @package		MVCious
 * @subpackage	Core
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Load
{
	/**
	 * Get instance from MVCious.
	 *
	 * @param	mixed
	 * @access	private
	 */
	function __get($key)
	{
		$inst =& get_instance();
		return $inst->$key;
	}

	/**
	 * Model
	 *
	 * Loads a model and makes it accessible within the framework. 
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function model($filename)
	{
		$inst =& get_instance();
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['modelsFolder'];

		if ($this->_check_filename($filename, $foldername)) {
			$inst->$filename = $this->_load_filename($filename, $foldername);
		}
	}

	/**
	 * Library
	 *
	 * Loads a library and makes it accessible within the framework. 
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function library($filename)
	{
		$inst =& get_instance();
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['librariesFolder'];

		if ($this->_check_filename($filename, $foldername)) {
			$inst->$filename = $this->_load_filename($filename, $foldername);
		}
	}

	/**
	 * View
	 *
	 * Loads a view and makes it accessible within the framework. 
	 *
	 * @access	public
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	function view($filename, $vars = NULL, $return = FALSE)
	{
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['viewsFolder'];

		if (!$this->_check_filename($filename, $foldername)) {
			return FALSE;
		}

		if (is_array($vars)) {
			foreach ($vars as $key => $value) {
				$$key = $value;
			}
		}

		if ($return) {
			ob_start();
			include $foldername . $filename . '.php';
			return ob_get_clean();
		} else {
			ob_start();
			include $foldername . $filename . '.php';
			echo ob_get_clean();
			return TRUE;
		}
	}

	/**
	 * Helper
	 *
	 * Loads a helper and makes it accessible within the framework. 
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function helper($filename)
	{
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['helpersFolder'];

		if ($this->_check_filename($filename, $foldername)) {
			include $foldername . $filename . '.php';
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Check filename
	 *
	 * Checks if the file exists inside a folder.
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	private function _check_filename($filename, $foldername)
	{
		if (file_exists($foldername . $filename . '.php')) {
			return TRUE;
		} else {
			trigger_error('File "' . $foldername . $filename . '.php" not exists!', E_USER_ERROR);
			return FALSE;
		}
	}

	/**
	 * Load filename
	 *
	 * Loads a file in memory, checks if its class name is the same
	 * and returns it as an object.
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	object
	 */
	private function _load_filename($filename, $foldername)
	{
		require_once($foldername . $filename . '.php');
		$classname = ucfirst($filename);

		if (class_exists($classname)) {
			return new $classname();
		} else {
			return FALSE;
		}
	}
}
