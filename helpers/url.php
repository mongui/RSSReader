<?php if ( !defined('MVCious')) exit('No direct script access allowed');

if ( !function_exists('redirect'))
{
	function redirect ( $uri = NULL )
	{
		if ( !isset($uri) )
		{
			global $protocol, $server_host, $index_path;
			$uri = $protocol . '://' . $server_host . $index_path;
		}

		header('Location: ' . $uri);
		exit();
	}
}

if ( !function_exists('site_url'))
{
	function site_url ( $uri = '' )
	{
		global $protocol, $server_host, $index_path;
		return $protocol . '://' . $server_host . $index_path . $uri;
	}
}