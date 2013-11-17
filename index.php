<?php
define('MVCious', TRUE);
define('APP_PATH', dirname(__FILE__));
require 'config.php';

// Debug mode for a development environment.
if ($config['debug'] == TRUE) {
	error_reporting(E_ALL | E_STRICT | E_DEPRECATED | E_RECOVERABLE_ERROR);
} else {
	error_reporting(0);
}

require 'mvc/Load.php';
require 'mvc/Config.php';
require 'mvc/ModelBase.php';
require 'mvc/ControllerBase.php';

/* ---------------------------------------------------------------
 *  Get instance from MVCious.
 * ---------------------------------------------------------------
 */
function &get_instance()
{
	return Load::get_instance();
}

/* ---------------------------------------------------------------
 *  Send to the client a specific error depending on the debug
 *  variable.
 * ---------------------------------------------------------------
 */
function load_error($errnum = 404, $text)
{
	global $config;
	header(':', TRUE, $errnum);
	
	if (isset($config['debug']) && $config['debug'] == TRUE) {
		trigger_error($text, E_USER_ERROR);
	} else {
		ob_start();
		if (file_exists($config['folders']['errorsFolder'] . $errnum . '.php')) {
			include $config['folders']['errorsFolder'] . $errnum . '.php';
		} else {
			include $config['folders']['errorsFolder'] . 'generic.php';
		}
		echo ob_get_clean();
		die();
	}
}

// Is it a CLI or a web request?
if (isset($argv[1])) {
	$path = $argv;
	array_shift($path);
	$path = array_map('strtolower', $path);
	$uri  = array_diff($path, array(''));
} else {
	// Gets the URI and splits it.
	$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
	$path = trim($path, '/');
	if (empty($path)) {
		$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		$path = trim($path, '/');
		if (empty($path) && count($_GET) > 0) {
			$path = $_GET['/'];
		}
	}
	$path = trim(strtolower($path), '/');

	// Is activated the controller extension in the config file?
	// Please, remove it from the URI.
	if (   isset($path)
		&& $path != ''
		&& isset($config['controller_extension'])
		&& $config['controller_extension'] != ''
	) {
		$pos = strpos($path, '.' . $config['controller_extension']);
		
		if ($pos === false) {
			load_error(404, 'Request controller not exists!');
		} else {
			$path = str_replace('.' . $config['controller_extension'], '', $path);
		}
	}

	$uri = array_diff(explode('/', $path), array(''));
}

// Now it loads the controller.
$loader = new Load();
$loader->controller($uri);
