<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Load
{
	function __construct()
	{
	}

	function __get($key)
	{
		$inst =& get_instance();
		return $inst->$key;
	}

	function model( $filename )
	{
		$inst =& get_instance();
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['modelsFolder'];

		if ( $this->check_filename($filename, $foldername) )
			$inst->$filename =& $this->load_filename($filename, $foldername);
	}

	function library( $filename )
	{
		$inst =& get_instance();
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['librariesFolder'];

		if ( $this->check_filename($filename, $foldername) )
			$inst->$filename =& $this->load_filename($filename, $foldername);
	}

	function view( $filename, $vars = NULL, $return = FALSE )
	{
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['viewsFolder'];

		if ( !$this->check_filename($filename, $foldername) )
			return FALSE;

		if( is_array($vars) )
			foreach ($vars as $key => $value)
				$$key = $value;

		if ( $return )
		{
			ob_start();
			include $foldername . $filename . '.php';
			return ob_get_clean();
		}
		else
		{
			ob_start();
			include $foldername . $filename . '.php';
			echo ob_get_clean();
		}
	}

	function helper( $filename )
	{
		$filename = strtolower($filename);
		$folders = $this->config->get('folders');
		$foldername = $folders['helpersFolder'];

		if ( $this->check_filename($filename, $foldername) )
		{
			include $foldername . $filename . '.php';
			return TRUE;
		}
		else
			return FALSE;
	}

	private function check_filename( $filename, $foldername )
	{
		if ( file_exists( $foldername . $filename . '.php') )
			return TRUE;
		else
			trigger_error('File "' . $foldername . $filename . '.php" not exists!', E_USER_ERROR);
	}

	private function load_filename( $filename, $foldername )
	{
		require_once($foldername . $filename . '.php');
		$classname = ucfirst($filename);

		if ( class_exists($classname) )
		{
			return new $classname();
		}
		else
			return FALSE;
	}
}
