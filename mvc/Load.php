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
	 * Instance var.
	 *
	 * @var		object
	 * @access	private
	 */
	private static $_inst;

	/**
	 * Config Folders.
	 *
	 * @var		array
	 * @access	private
	 */
	private $_folders;

	/**
	 * Checks if the file to load is within a module folder.
	 *
	 * @var		array
	 * @access	private
	 */
	private $_inModule = FALSE;

	/**
	 * If a controller is within a module, save the module name.
	 *
	 * @var		string
	 * @access	private
	 */
	private $_moduleName = '';

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		global $config;

		$this->config = new Config();
		$this->config->set_array($config);
		
		self::$_inst =& $this;
		$this->load =& self::$_inst;

		$this->autoload();
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

	/**
	 * Autoload
	 *
	 * Load the list of models, libraries and helpers stored in
	 * $config['autoload'] array in config.php file.
	 *
	 * @access	public
	 * @return	void
	 */
	function autoload()
	{
		$aload = $this->config->get('autoload');

		if (!isset($aload)) {
			return FALSE;
		}

		$filetypes = array(
							'models'	=> 'model',
							'libraries'	=> 'library',
							'helpers'	=> 'helper'
						);

		foreach ($aload as $type => $files) {
			if (!empty($files)) {
				foreach ($files as $filename) {
					$this->$filetypes[$type]($filename);
				}
			}
		}
		
		return TRUE;
	}

	/**
	 * Controller
	 *
	 * Loads a controller and makes it accessible within the framework. 
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function controller($uri, $args = NULL)
	{
		if (is_string($uri)) {
			$uri = array_diff(explode('/', $uri), array(''));
		}

		$this->_folders = $this->config->get('folders');

		// Do we have an URI?
		// Let's find where the controller is.
		if (!empty($uri)) {
			if (is_array($uri) && is_array($args)) {
				$uri = array_merge($uri, $args);
			}

			// Is it part of a module?
			if ($this->config->get('modules') == TRUE) {
				$tmpContrFldr = $this->_folders['controllersFolder'];
				$tmpUri = $uri;
				$this->_moduleName = array_shift($uri);
				$this->_folders['controllersFolder'] = $this->_folders['modulesFolder'] . $this->_moduleName . '/' . $this->_folders['controllersFolder'];
				$uripos = $this->_load_controller($uri, $this->_folders['controllersFolder']);
			}

			// Maybe it's not a module.
			if (!isset($uripos) || $uripos == FALSE) {
				$uri = (isset($tmpUri)) ? $tmpUri : $uri;
				$this->_moduleName = '';
				$this->_folders['controllersFolder'] = (isset($tmpContrFldr)) ? $tmpContrFldr : $this->_folders['controllersFolder'];
			}
			$uripos = $this->_load_controller($uri, $this->_folders['controllersFolder']);

			//The controller can't be located.
			if (!$uripos) {
				if ($this->config->get('default_controller') && file_exists($this->_folders['controllersFolder'] . $this->config->get('default_controller') . '.php')) {
					array_unshift($uri, $this->config->get('default_controller'));

					$uripos = $this->_load_controller($uri, $this->_folders['controllersFolder']);

					$controller = new $uri[$uripos-1]();
					if (!$uripos) {
						load_error(404, 'Default controller not exists!');
					} elseif (!is_callable(array($controller, $uri[$uripos]))) {
						load_error(404, 'Request controller not exists!');
					}
				} else {
					load_error(404, 'Default controller not exists!');
				}
			}
		}
		else {
			// No, the URI is empty.
			// We'll give an opportunity to the default controller.
			if ($this->config->get('default_controller') && file_exists($this->_folders['controllersFolder'] . $this->config->get('default_controller') . '.php')) {
				$uri = array($this->config->get('default_controller'));
			}

			$uripos = $this->_load_controller($uri, $this->_folders['controllersFolder']);
			if (!$uripos) {
				load_error(404, 'Request controller not exists!');
			}
		}

		if ($uripos) {
			$uripos--;
		}

		$args = array_slice($uri, $uripos + 1);

		// Now we know which is the controller class we are looking for, we load it.
		$controller = new $uri[$uripos]();

		// And then, we check if the method exists or there is a default method (index).
		if (!empty($args) && is_callable(array($controller, $args[0]))) {
			call_user_func_array(array($controller, $args[0]), array_slice($args, 1));
		} elseif (is_callable(array($controller, 'index'))) {
			$refl = new ReflectionMethod($controller, 'index');

			if ($refl->getNumberOfParameters() == 0 && count($args) > 0) {
				load_error(404, 'Request method not exists!');
			} else {
				call_user_func_array(array($controller, 'index'), $args);
			}
		} else {
			load_error(404, 'Request method not exists!');
		}
	}

	/**
	 * Load Controller
	 *
	 * Find and load a controller from an URI string.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	private function _load_controller($uri, $cF = NULL, $pos = 0, $dir = NULL)
	{
		if (!$uri) {
			return FALSE;
		} elseif ($cF) {
			$controllersFolder = $cF;
		} else {
			global $controllersFolder;
		}

		if (!is_array($uri)) {
			$uri = array($uri);
		}
		if (file_exists($controllersFolder . $dir . $uri[$pos] . '.php')) {
			require_once($controllersFolder . $dir . $uri[$pos] . '.php');
			if (class_exists($uri[$pos])) {
				return $pos + 1;
			} else {
				return FALSE;
			}
		}
		elseif (is_dir($controllersFolder . $dir . $uri[$pos])) {
			$pos++;
			
			if (isset($uri[$pos])) {
				$dir = array_slice($uri, 0, $pos);
				$dir = implode('/', $dir) . '/';
				return $this->_load_controller($uri, $controllersFolder, $pos, $dir);
			} else {
				load_error(404, 'Request controller not exists!');
			}
		}
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
		$this->_folders = $this->config->get('folders');

		if ($this->_check_filename($filename, 'modelsFolder')) {
			$inst->$filename = $this->_load_filename($filename, 'modelsFolder');
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
		$this->_folders = $this->config->get('folders');

		if ($this->_check_filename($filename, 'librariesFolder')) {
			$inst->$filename = $this->_load_filename($filename, 'librariesFolder');
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
		$this->_folders = $this->config->get('folders');

		if (!$this->_check_filename($filename, 'viewsFolder')) {
			return FALSE;
		}

		if (is_array($vars)) {
			foreach ($vars as $key => $value) {
				$$key = $value;
			}
		}

		ob_start();

		if ($this->config->get('modules') == TRUE && $this->_inModule == TRUE) {
			include $this->_folders['modulesFolder'] . $this->_moduleName . '/' . $this->_folders['viewsFolder'] . $filename . '.php';
		} else {
			include $this->_folders['viewsFolder'] . $filename . '.php';
		}

		if ($return) {
			return ob_get_clean();
		} else {
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
		$this->_folders = $this->config->get('folders');

		if ($this->_check_filename($filename, 'helpersFolder')) {
			if ($this->config->get('modules') == TRUE && $this->_inModule == TRUE) {
				include $this->_folders['modulesFolder'] . $this->_moduleName . '/' . $this->_folders['helpersFolder'] . $filename . '.php';
			} else {
				include $this->_folders['helpersFolder'] . $filename . '.php';
			}
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
		if ($this->config->get('modules') == TRUE && file_exists($this->_folders['modulesFolder'] . $this->_moduleName . '/' . $this->_folders[$foldername] . $filename . '.php')) {
			$this->_inModule = TRUE;
			return TRUE;
		} elseif (file_exists($this->_folders[$foldername] . $filename . '.php')) {
			$this->_inModule = FALSE;
			return TRUE;
		} else {
			trigger_error('File "' . $filename . '.php" not exists!', E_USER_ERROR);
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
		if ($this->config->get('modules') == TRUE && $this->_inModule == TRUE) {
			require_once($this->_folders['modulesFolder'] . $this->_moduleName . '/' . $this->_folders[$foldername] . $filename . '.php');
		} else {
			require_once($this->_folders[$foldername] . $filename . '.php');
		}
		$classname = ucfirst($filename);

		if (class_exists($classname)) {
			return new $classname();
		} else {
			return FALSE;
		}
	}
}
