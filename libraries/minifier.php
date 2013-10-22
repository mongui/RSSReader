<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Minifier Class
 *
 * Reduces the CSS, JS and HTML code sent to the client.
 *
 * @package		MVCious
 * @subpackage	Libraries
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/MVCious
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Minifier
{
	/**
	 * CSS Files array
	 *
	 * @var		array
	 * @access	private
	 */
	private $_css_files		= array();

	/**
	 * JS Files array
	 *
	 * @var		array
	 * @access	private
	 */
	private $_js_files		= array();

	/**
	 * HTML Files array
	 *
	 * @var		array
	 * @access	private
	 */
	private $_html_files	= array();

	/**
	 * Clean Vars
	 *
	 * Resets the three variables of this class.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function clean_vars($type = NULL)
	{
		if (isset($type) && ($type == 'css' || $type == 'js' || $type == 'html')) {
			$var = '_' . $type . '_files';
			$this->$var = array();
		} else {
			$this->_css_files = array();
			$this->_js_files = array();
			$this->_html_files = array();
		}
	}

	/**
	 * Add File
	 *
	 * Add a file to be minimized.
	 *
	 * @access	public
	 * @param	string
	 * @param 	string
	 * @return	bool
	 */
	public function add_file($type, $file)
	{
		if (isset($file) && isset($type) && ($type == 'css' || $type == 'js' || $type == 'html')) {
			$var = '_' . $type . '_files';
			$comp_files = &$this->$var;
		} else {
			return FALSE;
		}

		if (!in_array($file, $comp_files)) {
			$comp_files[] = $file;
		}
		return TRUE;
	}

	/**
	 * Get Files
	 *
	 * Returns the files that will be minimized.
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	public function get_files($type = NULL)
	{
		if (!isset($type)) {
			return array(
						'css'	=> $this->_css_files,
						'js'	=> $this->_js_files,
						'html'	=> $this->_html_files
						);
		} elseif (isset($type) && ($type == 'css' || $type == 'js' || $type == 'html')) {
			$var = '_' . $type . '_files';
			return $this->$var;
		} else {
			return FALSE;
		}
	}

	/**
	 * Load Files
	 *
	 * Returns the string of the selected files.
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _load_files($type = NULL)
	{
		if (isset($type) && ($type == 'css' || $type == 'js' || $type == 'html')) {
			$text = '';
			$var = '_' . $type . '_files';
			foreach ($this->$var as $file) {
				$text .= file_get_contents($file);
			}
			return $text;
		} else {
			return FALSE;
		}
	}

	/**
	 * Minify HTML
	 *
	 * If the parameter is filled, minifies and returns the text inserted.
	 * Otherwise, loads previously added files content, minimizes and returns it.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function minify_html($text = NULL)
	{
		if (!isset($text)) {
			$text = $this->_load_files('html');
		}

		$regex = '%# Collapse whitespace everywhere but in blacklisted elements.
			(?>             # Match all whitespans other than single space.
			  [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
			| \s{2,}        # or two or more consecutive-any-whitespace.
			) # Note: The remaining regex consumes no text at all...
			(?=             # Ensure we are not in a blacklist tag.
			  [^<]*+        # Either zero or more non-"<" {normal*}
			  (?:           # Begin {(special normal*)*} construct
				<           # or a < starting a non-blacklist tag.
				(?!/?(?:textarea|pre|script)\b)
				[^<]*+      # more non-"<" {normal*}
			  )*+           # Finish "unrolling-the-loop"
			  (?:           # Begin alternation group.
				<           # Either a blacklist start tag.
				(?>textarea|pre|script)\b
			  | \z          # or end of file.
			  )             # End alternation group.
			)  # If we made it here, we are not in a blacklist tag.
			%Six';
		$text = preg_replace($regex, " ", $text);
		$text = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $text);
		$text = str_replace("> <", "><", $text);

		if ($text === null) {
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);
		}

		return $text;
	}

	/**
	 * Minify CSS
	 *
	 * If the parameter is filled, minifies and returns the text inserted.
	 * Otherwise, loads previously added files content, minimizes and returns it.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function minify_css($text = NULL)
	{
		if (!isset($text)) {
			$text = $this->_load_files('css');
		}

		$text = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $text);
		$text = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $text);
		$text = str_replace(array(" {", ": ", "{ ", " }", ", "), array("{", ":", "{", "}", ","), $text);

		if ($text === null) {
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);
		}

		return $text;
	}

	/**
	 * Minify JS
	 *
	 * If the parameter is filled, minifies and returns the text inserted.
	 * Otherwise, loads previously added files content, minimizes and returns it.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function minify_js($text = NULL)
	{
		if (!isset($text)) {
			$text = $this->_load_files('js');
		}

		$text = preg_replace('@//.*@','', $text); //delete comments
		$text = preg_replace('@\s*/>@','>', $text); //delete xhtml tag slash ( />)
		$text = str_replace(array("\n", "\r", "\t"), "", $text); //delete escaped white spaces
		$text = preg_replace("/<\?(.*\[\'(\w+)\'\].*)\?>/", "?>$1<?", $text); //rewrite associated array to object
		$text = preg_replace("/\s*([\{\[\]\}\(\)\|&;]+)\s*/", "$1", $text); //delete white spaces between brackets

		$x = 65;
		$y = 64;

		$count = preg_match_all("/(\Wvar (\w{3,})[ =])/", $text, $matches); //find var names

		for ($i = 0; $i < $count; $i++) {
			if ( $y+1 > 90 ) { //count upper case alphabetic ascii code
				$y = 65;
				$x++;
			} else {
				$y++;
			}

			if (isset($matches[$i])) {
				$text = preg_replace("/(\W)(" . $matches[$i] . "=" . $matches[$i] . "\+)(\W)/", "$1" . chr($x) . chr($y) . "+=$3", $text); //replace 'longvar=longvar+'blabla' to AA+='blabla' 
				$text = preg_replace("/(\W)(" . $matches[$i] . ")(\W)/", "$1" . chr($x) . chr($y) . "$3", $text); //replace all other vars
			}
		}

		if ($text === null) {
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);
		}

		return $text;
	}
}
