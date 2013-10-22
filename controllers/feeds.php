<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Feeds Management Controller.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Feeds extends ControllerBase
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (!isset($_SESSION)) {
			session_start();
		}

		$this->load->model('connections');
	}

	/**
	 * Get
	 * 
	 * Sends a json array with its feeds to the user.
	 */
	public function get()
	{
		$this->load->model('configuration');
		$feedlist = $this->connections->feeds_per_user($_SESSION['id'], $this->config->get('show_favicons'));
		echo json_encode($feedlist);
	}

	/**
	 * Manage
	 * 
	 * Modifies the feedlist as ordered by the user.
	 */
	public function manage()
	{
		if ( isset($_POST["feed"]	)	) { $feed	= filter_var($_POST["feed"], FILTER_SANITIZE_STRING			); }
		if ( isset($_POST["action"]	)	) { $action	= filter_var($_POST["action"], FILTER_SANITIZE_STRING		); }
		if ( isset($_POST["folder"]	)	) { $folder	= filter_var(trim($_POST["folder"]), FILTER_SANITIZE_STRING	); }
		if ( isset($_POST["value"]	)	) { $value	= filter_var($_POST["value"], FILTER_SANITIZE_STRING		); }

		if (isset($feed)		&& $action == 'readed'												) {
			// Marking feed as readed?
			$this->connections->set_readed_feed($feed, $_SESSION['id'], 1);
			echo 'success';
		} elseif (isset($feed)	&& $action == 'update'												) {
			// Updating feed?
			$this->load->model('updater');
			if ($this->updater->update_feed($feed))
				echo 'success';
			else
				echo 'failure';
		} elseif (isset($feed)	&& $action == 'name'		&& !empty($value)						) {
			// Changing feed name?
			if ($this->connections->update_feed_name($feed, $value, $_SESSION['id'])) {
				echo 'success';
			} else {
				echo 'failure';
			}
		} elseif (isset($_POST['value']) && $action == 'sort'										) {
			// Sorting the feed list?
			$value = filter_var_array($_POST['value'], FILTER_VALIDATE_INT);

			if ($this->connections->move_feed($_SESSION['id'], $value)) {
				echo 'success';
			} else {
				echo 'failure';
			}
		} elseif (isset($feed)	&& $action == 'newfolder'	&& !empty($value)						) {
			// Creating a new folder for feeds?
			if ($folder = $this->connections->new_folder($_SESSION['id'], $value, $feed)) {
				echo 'success';
			} else {
				echo 'failure';
			}
		} elseif (isset($feed)	&& $action == 'unsubscribe'											) {
			// Removing a feed?
			if ($this->connections->unsubscribe_feed($feed, $_SESSION['id'])) {
				echo 'success';
			} else {
				echo 'failure';
			}
		} else {
			echo 'failure';
		}
	}

	/**
	 * Import File
	 * 
	 * Uploads a OPML file and adds its feeds to the users feedlist.
	 */
	public function importfile()
	{
		if (isset($_FILES['file']) && $_SESSION['id']) {
			$allowedExts = array('xml', 'opml');
			$extension = end(explode('.', $_FILES['file']['name']));
			if (
				$_FILES['file']['type'] == 'text/xml'
				&& $_FILES['file']['size'] < 100000 // 100Kb
				&& in_array($extension, $allowedExts)
			) {
				if ($_FILES['file']['error'] > 0) {
					echo 'failure';
				} else {
					if (file_exists($_FILES["file"]["tmp_name"])) {
						error_reporting(E_ERROR);
						$xml = simplexml_load_file($_FILES["file"]["tmp_name"]);

						foreach ($xml->body->outline as $feed) {
							$this->add_new_feed(filter_var($feed['xmlUrl'], FILTER_SANITIZE_STRING));
						}

						echo 'success';
					} else {
						exit('failure');
					}

				}
			} else {
				echo 'failure';
			}
		} else {
			$html = $this->load->view('importfile', NULL, TRUE);
			$this->load->library('minifier');
			echo $this->minifier->minify_html($html);
		}
	}

	/**
	 * Add
	 * 
	 * Add a feed to the users feedlist.
	 */
	public function add()
	{
		if (isset($_POST["feed_url"])) {
			$feed_url = filter_var($_POST["feed_url"], FILTER_SANITIZE_STRING);
			$feed_url = str_replace("https://", "http://", $feed_url);

			if (!empty($feed_url)) {
				$feed_id = $this->add_new_feed($feed_url);

				if ($feed_id > 0) {
					echo 'success';
				} else {
					echo 'error';
				}
			}
		} else {
			$html = $this->load->view('add', NULL, TRUE);
			$this->load->library('minifier');
			echo $this->minifier->minify_html($html);
		}
	}

	/**
	 * Add New Feed
	 */
	private function add_new_feed($url)
	{
		$this->load->model('updater');

		$feed_id = $this->updater->insert_feed($url, $_SESSION['id']);
		$this->connections->feed_to_user($feed_id, $_SESSION['id']);
		$this->updater->update_feed($feed_id);

		return $feed_id;
	}
}
