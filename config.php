<?php if ( !defined('MVCious')) exit('No direct script access allowed');

$default_controller	= 'rssreader';
$protocol			= 'http';
$server_host		= 'localhost';
$index_file			= 'index.php';
$document_root		= '/var/www/html';
$index_path			= str_replace($index_file, '', $_SERVER['SCRIPT_NAME']);

$folders = array(
				'controllersFolder'	=> 'controllers/',
				'modelsFolder'		=> 'models/',
				'viewsFolder'		=> 'views/',
				'librariesFolder'	=> 'libraries/',
				'helpersFolder'		=> 'helpers/'
			);

$database = array(
				'dbhost'			=> 'localhost',
				'dbname'			=> 'rssreader',
				'dbuser'			=> 'myuser',
				'dbpass'			=> 'mypass'
			);
?>