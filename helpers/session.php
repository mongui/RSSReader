<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Session helpers
 *
 * @package		RSSReader
 * @subpackage	Helpers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

// ---------------------------------------------------------------------------

/**
 * Start session
 *
 * Starts a long-lived session. The session files are kept in a folder of
 * their own, so the garbage collector of other PHP apps on the same server
 * (with a shorter gc_maxlifetime) doesn't delete them. The cookie expiration
 * is renewed on every request.
 *
 * @access	public
 * @param	int		Lifetime in seconds.
 * @param	string	Folder for the session files.
 */
if (!function_exists('start_session')) {
	function start_session($timeout, $path)
	{
		if (!is_dir($path)) {
			@mkdir($path, 0700, TRUE);
		}
		if (is_dir($path) && is_writable($path)) {
			session_save_path($path);
		}

		ini_set('session.gc_maxlifetime', $timeout);
		session_set_cookie_params($timeout, '/');
		session_start();

		// Renew the cookie, or it expires $timeout seconds after the login.
		setcookie(session_name(), session_id(), time() + $timeout, '/', '', FALSE, TRUE);
	}
}
