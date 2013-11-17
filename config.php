<?php if (!defined('MVCious')) exit('No direct script access allowed');

$config['default_controller']		= 'rssreader';
$config['protocol']					= 'http';
$config['server_host']				= 'localhost';
$config['index_file']				= 'index.php';
$config['app_path']					= APP_PATH . '/'; // 'C:/wamp/www/rssreader/'
$config['relative_path']			= str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\','/', $config['app_path'])); // 'MVCious/'
$config['server_root']				= $_SERVER['DOCUMENT_ROOT']; // 'C:/wamp/www/'
$config['server_relative_path']		= str_replace($config['index_file'], '', $_SERVER['SCRIPT_NAME']);  // '/MVCious/'
$config['server_path']				= $config['protocol'] . '://' . $_SERVER['SERVER_NAME'] . str_replace($config['index_file'], '', $_SERVER['SCRIPT_NAME']); //  'http://localhost/MVCious/'


$config['folders'] = array(
				'controllersFolder'	=> 'controllers/',
				'modelsFolder'		=> 'models/',
				'viewsFolder'		=> 'views/',
				'librariesFolder'	=> 'libraries/',
				'helpersFolder'		=> 'helpers/'
			);

$config['database'] = array(
				'type'				=> 'mysql',
				'dbhost'			=> 'localhost',
				'dbname'			=> 'rssreader',
				'dbuser'			=> 'myuser',
				'dbpass'			=> 'mypass'
			);

$config['debug']					= FALSE;
