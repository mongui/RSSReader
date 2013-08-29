<?php if ( !defined('MVCious')) exit('No direct script access allowed');

$config['default_controller']	= 'rssreader';
$config['protocol']				= 'http';
$config['server_host']			= 'localhost';
$config['index_file']			= 'index.php';
$config['document_root']		= '/var/www/html';
$config['index_path']			= str_replace($index_file, '', $_SERVER['SCRIPT_NAME']);

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
?>

