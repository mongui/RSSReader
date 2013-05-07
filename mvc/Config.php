<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Config
{
	private $vars;

	function __construct()
	{
		$this->vars = array();
	}

	public function set( $name, $value )
	{
		$this->vars[$name] = $value;
	}

	public function get( $name )
	{
		if ( isset($this->vars[$name]) )
		{
			return $this->vars[$name];
		}
	}

	public function set_array( $arr )
	{
		foreach ( $arr as $key => $value )
			$this->set ($key, $value);
	}
}
?>
