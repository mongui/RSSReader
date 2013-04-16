<?php
define('MVCious', TRUE);

require 'config.php';

require 'mvc/Load.php';
require 'mvc/Config.php';
require 'mvc/ModelBase.php';
require 'mvc/ControllerBase.php';

function &get_instance()
{
	return ControllerBase::get_instance();
}

function load_controller( $uri, $pos = 0, $dir = NULL )
{
	if ( !$uri )
		return FALSE;

	if ( !is_array($uri) )
		$uri = array($uri);

	if ( file_exists( 'controllers/' . $dir . $uri[$pos] . '.php') )
	{
		require_once('controllers/' . $dir . $uri[$pos] . '.php');
		if ( class_exists($uri[$pos]) )
			return $pos + 1;
		else
			return FALSE;
	}
	elseif ( is_dir( 'controllers/' . $dir . $uri[$pos]) )
	{
		$pos++;
		$dir = array_slice($uri, 0, $pos);
		$dir = implode('/', $dir) . '/';
		return load_controller($uri, $pos, $dir);
	}
}

// Is it a CLI call?
if ( isset($argv[1]) )
	$path = $argv[1];
// Or is it a web call?
else
{
	$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
	$path = trim($path, '/');
	if ( empty($path) )
	{
		$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		$path = trim($path, '/');
		if ( empty($path) )
			$path = $_GET['/'];
	}
}

$path = trim(strtolower($path), '/');
$uri  = array_diff(explode('/', $path), array(''));

if ( !empty($uri) )
{
	$uripos = load_controller($uri);

	if ( !$uripos )
	{
		array_unshift($uri, $config['default_controller']);
		$uripos = load_controller($uri);

		if ( !$uripos )
			trigger_error('Request controller not exists!', E_USER_ERROR);
	}
}
else
{
	$uri = array($config['default_controller']);
	$uripos = load_controller($uri);
	if ( !$uripos )
		trigger_error('Request controller not exists!', E_USER_ERROR);
}

if ( $uripos )
	$uripos--;

$args = array_slice($uri, $uripos + 1);

$controller = new $uri[$uripos]();

if ( !empty($args) && is_callable(array($controller, $args[0])) )
	call_user_func_array(array($controller, $args[0]), array_slice($args, 1));
elseif ( is_callable(array($controller, 'index')) )
	call_user_func_array(array($controller, 'index'), $args);
else
	trigger_error('Request method not exists!', E_USER_ERROR);

?>