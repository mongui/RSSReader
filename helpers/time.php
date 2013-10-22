<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Time helpers
 *
 * @package		MVCious
 * @subpackage	Helpers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

// ---------------------------------------------------------------------------

/**
 * Timestamp To User Defined Timestamp
 *
 * Easily changes the tricky SQL/UNIX timestamps to another format.
 * Take a look to these pages:
 *  - http://php.net/manual/es/function.date.php
 *  - http://en.wikipedia.org/wiki/Date_format_by_country
 *
 * @access	public
 * @param	string
 * @param	string
 * @return	string
 */
if (!function_exists('timestamp_to_user_defined')) {
	function timestamp_to_user_defined($timestamp, $time_format = 'Y-m-d')
	{
		$timestamp = strtotime($timestamp);
		return date($time_format, $timestamp);
	}
}

/**
 * NOW
 *
 * Returns the timestamp at the moment of the call.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (!function_exists('now')) {
	function now($time_format = 'Y-m-d H:i:s')
	{
		return date($time_format);
	}
}

/**
 * Date Info
 *
 * Returns a few useful data taken from the timestamp.
 *
 * @access	public
 * @param	string
 * @return	array
 */
if (!function_exists('date_info')) {
	function date_info($timestamp = NULL)
	{
		$now = time();
		if (!isset($timestamp)) {
			$timestamp = $now;
		} else {
			$timestamp = strtotime($timestamp);
		}

		$days_ago = (int)floor((time() - $timestamp) / (60 * 60 * 24));
		$day_of_week = (int)date('N', $timestamp);
		$day_of_week_now = (int)date('N', $now);
		$year = (int)date('Y', $timestamp);

		return array (
				'today'			=>	($days_ago == 0)? TRUE : FALSE,
				'yesterday'		=>	($days_ago == 1)? TRUE : FALSE,
				'days_ago'		=>	$days_ago,
				'this_week'		=>	($day_of_week_now - $days_ago > 0)? TRUE : FALSE,
				'day_of_week'	=>	$day_of_week,
				'is_weekend'	=>	($day_of_week > 5)? TRUE : FALSE,
				'days_of_month'	=>	(int)date('t', $timestamp),
				'leap_year'		=>	((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)))
			);
	}
}


