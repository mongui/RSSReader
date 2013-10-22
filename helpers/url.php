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
 * @return	void
 */
if (!function_exists('redirect')) {
	function redirect($uri = NULL)
	{
		if (!isset($uri)) {
			global $config;
			$uri = $config['protocol'] . '://' . $config['server_host'] . $config['index_path'];
		}

		header('Location: ' . $uri);
		exit();
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
		global $config;
		if (isset($relative)) {
			return $config['index_path'] . $uri;
		} else {
			return $config['protocol'] . '://' . $config['server_host'] . $config['index_path'] . $uri;
		}
	}
}
