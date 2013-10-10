<?php if ( !defined('MVCious')) exit('No direct script access allowed');

abstract class ModelBase
{
	protected $db;
	protected $dbdata;

	public function __construct()
	{
		$inst =& get_instance();

		$this->dbdata = $inst->config->get('database');
		$this->database($this->dbdata);
	}

	function __get($key)
	{
		$inst =& get_instance();
		return $inst->$key;
	}

	protected function database( $connection, $tovar = FALSE )
	{
		try
		{
			$newdb = $this->set_connection($connection);
			if ( $newdb == FALSE )
				load_error(500, 'Unknown database type.');

			$newdb->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 

			if ( $tovar )
				return $newdb;
			else
				$this->db = $newdb;
		}
		catch(PDOException $e)
		{
			load_error(503, 'There was a problem with the database connection: ' . $e->getMessage());
		}
	}


	private function set_connection ( $connection )
	{
		$attribs = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'");

		if ( $this->driver_available($connection['type']) )
		{
			switch ( $connection['type'] )
			{
				case 'mysql':
					$dbcon = new PDO('mysql:host=' . $connection['dbhost'] . ';dbname=' . $connection['dbname'], $connection['dbuser'], $connection['dbpass'], $attribs);
				break;
				case 'sqlite':
					$dbcon = new PDO('sqlite:' . $connection['filename']);
				break;
				case 'mssql':
					$dbcon = new PDO('mssql:host=' . $connection['dbhost'] . ';dbname=' . $connection['dbname'] . ', ' . $connection['dbuser'] . ', ' . $connection['dbpass'] . '');
				break;
				case 'pgsql':
					$dbcon = new PDO('pgsql:dbname=' . $connection['dbname'] . ' host=' . $connection['dbhost'] . '', $connection['dbuser'], $connection['dbpass']);
				break;
				case 'sybase':
					$dbcon = new PDO('sybase:host=' . $connection['dbhost'] . ';dbname=' . $connection['dbname'] . ', ' . $connection['dbuser'] . ', ' . $connection['dbpass'] . '');
				break;
				case 'odbc':
					$dbcon = new PDO('odbc:MSSQLServer', $connection['dbuser'], $connection['dbpass']);
				break;
				case 'firebird':
					$dbcon = new PDO('firebird:dbname=' . $connection['dbhost'] . ':' . $connection['filename'], $connection['dbuser'], $connection['dbpass']);
				break;
				case 'oci':
					$dbcon = new PDO('OCI:dbname=' . $connection['dbname'], $connection['dbuser'], $connection['dbpass']);
				break;
				case 'dblib':
					$dbcon = new PDO ('dblib:host=' . $connection['dbhost'] . ';dbname=' . $connection['dbname'], $connection['dbuser'], $connection['dbpass']);
				break;
				default:
					$dbcon = FALSE;
			}
			return $dbcon;
		}
		else
			load_error(503, '"' . $connection['type'] . '": This extension was not added to your php.ini.');
	}

	protected function driver_available ( $type )
	{
		if ( !$type )
			return FALSE;

		foreach ( PDO::getAvailableDrivers() as $driver )
		{
			if ( $driver == $type )
				return TRUE;
		}

		return FALSE;
	}
}
?>