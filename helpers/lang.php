<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Language helpers
 *
 * The texts are written in English in the views and the scripts, and they
 * are used as keys in the language files (languages/<code>.php). A text
 * without translation is shown in English.
 *
 * @package		RSSReader
 * @subpackage	Helpers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

// ---------------------------------------------------------------------------

if (!function_exists('lang_code')) {
	/**
	 * Language Code
	 *
	 * Returns the language code of the logged user.
	 *
	 * @access	public
	 * @return	string
	 */
	function lang_code()
	{
		if (isset($_SESSION['language']) && preg_match('/^[a-z]{2}$/', $_SESSION['language'])) {
			return $_SESSION['language'];
		}

		return 'en';
	}

	/**
	 * Language Strings
	 *
	 * Returns the translations of the user's language.
	 *
	 * @access	public
	 * @return	array
	 */
	function lang_strings()
	{
		static $strings = NULL;

		if ($strings === NULL) {
			$strings = array();
			$file = APP_PATH . '/languages/' . lang_code() . '.php';

			if (file_exists($file)) {
				$strings = include $file;
			}
		}

		return $strings;
	}

	/**
	 * Translate
	 *
	 * Returns the translation of an English text. The context allows
	 * different translations of the same text ('context|text' key).
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function t($text, $context = NULL)
	{
		$strings = lang_strings();

		if ($context !== NULL && isset($strings[$context . '|' . $text])) {
			return $strings[$context . '|' . $text];
		}

		return isset($strings[$text]) ? $strings[$text] : $text;
	}
}
