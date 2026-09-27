<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Posts Management Controller.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Posts extends ControllerBase
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (!isset($_SESSION)) {
			ini_set('session.gc_maxlifetime', $this->config->get('session_timeout'));
			session_set_cookie_params($this->config->get('session_timeout'));
			session_start();
		}

		// Session lost: AJAX calls get a 401 and the page sends the user to the login.
		if (!isset($_SESSION['id'])) {
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}

		$this->load->model('connections');
	}

	/**
	 * Get
	 * 
	 * Sends a json array with the posts of a feed.
	 */
	public function get()
	{
		$this->load->helper('lang');

		// A feed id or a list: unreaded, starred, lastreaded or search.
		$feed_id = isset($_POST['feed']) ? (string) $_POST['feed'] : '';
		$feed_next = isset($_POST['next']) ? (string) $_POST['next'] : '';

		if (!ctype_digit($feed_id) && !in_array($feed_id, array('unreaded', 'starred', 'lastreaded', 'search'), TRUE)) {
			echo 'Feed not found!';
			return FALSE;
		}

		if (is_numeric($feed_id)) {
			$this->load->model('updater');
			$data = $this->updater->feed_data_from_id($feed_id);
		} else {
			$data = new stdClass();
			$data->id_feed		= 0;
			$data->site			= '';
			$data->url			= '';
			$data->last_update	= '';
			$data->favicon		= NULL;

			if ($feed_id == 'unreaded') {
				$data->name		= t('Unread posts');
			} elseif ($feed_id == 'starred') {
				$data->name		= t('Starred posts');
			} elseif ($feed_id == 'lastreaded') {
				$data->name		= t('Recently read posts');
			} elseif ($feed_id == 'search') {
				$search			= isset($_POST['search']) ? (string) $_POST['search'] : '';
				$data->name		= sprintf(t('Search for "%s"'), '<i>' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '</i>');
			}
		}

		// We can find the posts.
		if (!empty($data)) {
			if ($feed_id == 'search') {
				// The search pages go on from a cursor instead of a number of posts.
				$found = $this->connections->search_posts($_SESSION['id'], $search, ($feed_next !== '' && $feed_next !== '0') ? $feed_next : NULL);
				$posts = $found['posts'];
				$data->next = $found['next'];
			} else {
				$posts = $this->connections->posts_from_feed($feed_id, (int) $feed_next, $_SESSION['id']);
			}

			if ($posts) {
				foreach ($posts as $post) {
					$data->posts['post-' . $post->id_post] = $post;
				}
			} else {
				echo json_encode($data);
				return FALSE;
			}

			// We've got some posts. What now?
			// Set them the user's date/time format.
			$this->load->helper('time');
			if (isset($data->last_update) && $data->last_update <> '') {
				$ti = date_info($data->last_update);
				if ($ti['today']) {
					$data->last_update = timestamp_to_user_defined($data->last_update, 'H:i');
				} elseif ($ti['yesterday']) {
					$data->last_update = t('Yesterday');
				} else {
					$data->last_update = timestamp_to_user_defined($data->last_update, $_SESSION['timeformat']);
				}
			}

			foreach ($data->posts as $id => $val) {
				$ti = date_info($val->timestamp);
				if ($ti['today']) {
					$data->posts[$id]->timestamp = timestamp_to_user_defined($val->timestamp, 'H:i');
				} elseif ($ti['yesterday']) {
					$data->posts[$id]->timestamp = t('Yesterday');
				} else {
					$data->posts[$id]->timestamp = timestamp_to_user_defined($val->timestamp, $_SESSION['timeformat']);
				}
			}

			echo json_encode($data);
		} else {
			echo 'Feed not found!';
		}
	}

	/**
	 * Manage
	 * 
	 * Sets a post as readed/not readed or starred/not starred.
	 */
	public function manage()
	{
		if (isset($_POST['post'])	) { $post	= filter_var($_POST['post'], FILTER_SANITIZE_STRING);										}
		if (isset($_POST['action'])	) { $action	= filter_var($_POST['action'], FILTER_SANITIZE_STRING);										}
		if (isset($_POST['state'])	) { $state	= (isset($_POST['state'])) ? filter_var($_POST['state'], FILTER_SANITIZE_STRING) : FALSE;	}

		if (isset($post)		&& $action == 'readed'	) {
			$this->connections->set_readed_post($post, $_SESSION['id'], $state);
			echo 'success';
		} elseif (isset($post)	&& $action == 'starred'	) {
			$this->connections->set_starred_post($post, $_SESSION['id'], $state);
			echo 'success';
		} else {
			echo 'failure';
		}
	}
}
