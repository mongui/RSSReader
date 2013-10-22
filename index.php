<?php
define('MVCious', TRUE);

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
	return ControllerBase::get_instance();
}

/* ---------------------------------------------------------------
 *  Find and load a controller from an URI string.
 * ---------------------------------------------------------------
 */
function load_controller($uri, $pos = 0, $dir = NULL)
{
	if (!$uri) {
		return FALSE;
	}

	if (!is_array($uri)) {
		$uri = array($uri);
	}

	if (file_exists('controllers/' . $dir . $uri[$pos] . '.php')) {
		require_once('controllers/' . $dir . $uri[$pos] . '.php');
		if (class_exists($uri[$pos])) {
			return $pos + 1;
		} else {
			return FALSE;
		}
	}
	elseif (is_dir( 'controllers/' . $dir . $uri[$pos])) {
		$pos++;
		
		if (isset($uri[$pos])) {
			$dir = array_slice($uri, 0, $pos);
			$dir = implode('/', $dir) . '/';
			return load_controller($uri, $pos, $dir);
		} else {
			load_error(404, 'Request controller not exists!');
		}
	}
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
		if (file_exists( 'errors/' . $errnum . '.php')) {
			include 'errors/' . $errnum . '.php';
		} else {
			include 'errors/generic.php';
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

// Do we have an URI?
// Let's find where the controller is.
if (!empty($uri)) {
	$uripos = load_controller($uri);
	if (!$uripos) {
		if (isset($config['default_controller']) && file_exists( 'controllers/' . $config['default_controller'] . '.php')) {
			array_unshift($uri, $config['default_controller']);

			$uripos = load_controller($uri);

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
	if (isset($config['default_controller']) && file_exists( 'controllers/' . $config['default_controller'] . '.php')) {
		$uri = array($config['default_controller']);
	}

	$uripos = load_controller($uri);
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
