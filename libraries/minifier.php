<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Minifier
{
	private $css_files	= array();
	private $js_files	= array();
	private $html_files	= array();

	public function clean_vars( $type )
	{
		switch ($type) {
			case 'css':
				$this->css_files = array();
			break;
			case 'js':
				$this->js_files = array();
			break;
			case 'html':
				$this->html_files = array();
			break;
		}
	}

	public function add_file( $type, $file )
	{
		switch ($type) {
			case 'css':
				$comp_files = &$this->css_files;
			break;
			case 'js':
				$comp_files = &$this->js_files;
			break;
			case 'html':
				$comp_files = &$this->html_files;
			break;
		}
		if ( !in_array($file, $comp_files) )
			$comp_files[] = $file;
	}

	public function minify_html( $text = FALSE )
	{
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

		if ($text === null)
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);

		return $text;
	}

	public function minify_css( $text = FALSE )
	{
		if ( $text == FALSE )
		{
			if ( empty($this->css_files) )
				return FALSE;

			$text = '';
			foreach ( $this->css_files as $file )
				$text .= file_get_contents($file);
		}

		$text = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $text);
		$text = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $text);
		$text = str_replace(array(" {", ": ", "{ ", " }", ", "), array("{", ":", "{", "}", ","), $text);

		if ($text === null)
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);

		return $text;
	}

	public function minify_js( $text = FALSE )
	{
		if ( $text == FALSE )
		{
			if ( empty($this->js_files) )
				return FALSE;

			$text = '';
			foreach ( $this->js_files as $file )
				$text .= file_get_contents($file);
		}

		//echo 'Antes: ' . strlen($text);

		$text = preg_replace('@//.*@','', $text); //delete comments
		$text = preg_replace('@\s*/>@','>', $text); //delete xhtml tag slash ( />)
		$text = str_replace(array("\n", "\r", "\t"), "", $text); //delete escaped white spaces
		$text = preg_replace("/<\?(.*\[\'(\w+)\'\].*)\?>/", "?>$1<?", $text); //rewrite associated array to object
		$text = preg_replace("/\s*([\{\[\]\}\(\)\|&;]+)\s*/", "$1", $text); //delete white spaces between brackets

		$x = 65;
		$y = 64;

		$count = preg_match_all("/(\Wvar (\w{3,})[ =])/", $text, $matches); //find var names

		for( $i = 0; $i < $count; $i++ ) {
			if ( $y+1 > 90 ) //count upper case alphabetic ascii code
			{
				$y = 65;
				$x++;
			}
			else
				$y++;

			if ( isset($matches[$i]) )
			{
				//echo chr($x) . chr($y) . "=" . $matches[$i] . "\r\n";
				$text = preg_replace("/(\W)(" . $matches[$i] . "=" . $matches[$i] . "\+)(\W)/", "$1" . chr($x) . chr($y) . "+=$3", $text); //replace 'longvar=longvar+'blabla' to AA+='blabla' 
				$text = preg_replace("/(\W)(" . $matches[$i] . ")(\W)/", "$1" . chr($x) . chr($y) . "$3", $text); //replace all other vars
			}
		}

		//$count = preg_match_all("/function (\w{3,})/", $text, $matches); //find function names

		//echo 'Despues: ' . strlen($text);

		if ($text === null)
			trigger_error('Impossible to minify: File too big.', E_USER_ERROR);

		return $text;
	}
}