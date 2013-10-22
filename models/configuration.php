<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Configuration Model.
 *
 * @package		RSSReader
 * @subpackage	Models
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Configuration extends ModelBase
{
	/**
	 * Database connection object.
	 *
	 * @var		object
	 * @access	private
	 */
	private $conn;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();

		$this->conn = $this->db;
		$this->load_config();

		if ($this->config->get('timezone')) {
			date_default_timezone_set($this->config->get('timezone'));
		}
	}

	/**
	 * Load Config
	 * 
	 * Get the configuration from the database
	 * and loads it into the system.
	 *
	 * @access	public
	 * @return	void
	 */
	function load_config()
	{
		$sql = "SELECT * FROM config";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		foreach ($dbdata->fetchAll(PDO::FETCH_OBJ) as $conf) {
			if ($conf->value == 'true') {
				$conf->value = true;
			} elseif ($conf->value == 'false') {
				$conf->value = false;
			}

			$this->config->set($conf->param, $conf->value);
		}
	}

	/**
	 * Save Config
	 * 
	 * Saves an array to the database.
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function save_config($data)
	{
		$sql = "UPDATE config SET value = CASE ";

		foreach ($data as $key => $value) {
			$this->config->set($key, $value);
			$keys[] = "'$key'";
			$sql .= "WHEN param = '$key' THEN '$value' ";
		}

		$keys = implode(',', $keys);
		$sql .= "END WHERE param IN ($keys)";

		try {
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		} catch (PDOException $err) {
			//print_r('Error: ' . $err . '');
			echo 'error';
			die();
		}
	}
}
