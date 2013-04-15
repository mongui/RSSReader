<?php if ( !defined('MVCious')) exit('No direct script access allowed');

if ( !function_exists('sql_timestamp_to_user_defined'))
{
	function sql_timestamp_to_user_defined ( $timestamp, $time_format = 'Y-m-d' )
	{
		$timestamp = strtotime($timestamp);
		$compare = date('Y-m-d', $timestamp);

		if ( $compare == date('Y-m-d', time()) )
			return date('H:i', $timestamp);
		elseif ( $compare == date('Y-m-d', time()-60*60*24) )
			return 'Yesterday';
		else
			return date($time_format, $timestamp);
	}
}