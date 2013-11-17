<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * URL helpers
 *
 * @package		MVCious
 * @subpackage	Helpers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

// ---------------------------------------------------------------------------

/**
 * Redirect
 *
 * Changes the header of the sent page and redirects where we want.
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	void
 */
if (!function_exists('redirect')) {
	function redirect($uri = '',  $relative = NULL)
	{
		if (strpos($uri,'://') !== false) {
				header('Location: ' . $uri);
				exit();
		} else {
			global $config;
			if (isset($relative)) {
				$route = $config['server_relative_path'] . $uri;
			} else {
				$route = $config['server_path'] . $uri;
			}

			header('Location: ' . $route);
			exit();
		}
	}
}

/**
 * Site URL
 *
 * Returns the absolute or relative site URL on the Internet.
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	string
 */
if (!function_exists('site_url')) {
	function site_url($uri = '',  $relative = NULL)
	{
		if (strpos($uri,'://') !== false) {
				header('Location: ' . $uri);
				exit();
		} else {
			global $config;
			if (isset($relative)) {
				return $config['server_relative_path'] . $uri;
			} else {
				return $config['server_path'] . $uri;
			}
		}
	}
}
