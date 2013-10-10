<?php if ( !defined('MVCious')) exit('No direct script access allowed');

if ( !function_exists('redirect'))
{
	function redirect ( $uri = NULL )
	{
		if ( !isset($uri) )
		{
			global $config;
			$uri = $config['protocol'] . '://' . $config['server_host'] . $config['index_path'];
		}

		header('Location: ' . $uri);
		exit();
	}
}

if ( !function_exists('site_url'))
{
	function site_url ( $uri = '',  $relative = NULL )
	{
		global $config;
		if ( isset($relative) )
			return $config['index_path'] . $uri;
		else
			return $config['protocol'] . '://' . $config['server_host'] . $config['index_path'] . $uri;
	}
}