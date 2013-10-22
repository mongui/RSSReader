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
			session_start();
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
		$feed_id = filter_var($_POST['feed'], FILTER_SANITIZE_STRING);
		$feed_next = (isset($_POST['next'])) ? filter_var($_POST['next'], FILTER_SANITIZE_NUMBER_INT) : 0;

		// Do you want a feed or is it something else?
		if (
			   !is_numeric($feed_id)
			&& $feed_id <> 'unreaded'
			&& $feed_id <> 'starred'
		) {
			$search_string = $feed_id;
			$feed_id = 'search';
		} else {
			$search_string = NULL;
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

			if ($feed_id == 'unreaded' || $feed_id == 'starred') {
				$data->name		= ucfirst($feed_id) . ' posts';
			} elseif ($feed_id == 'search') {
				$data->name		= 'Search for &quot;<i>' . $search_string . '</i>&quot;';
			}
		}

		// We can find the posts.
		if (!empty($data)) {
			$posts = $this->connections->posts_from_feed($feed_id, $feed_next, $_SESSION['id'], $search_string);

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
					$data->last_update = 'Yesterday';
				} else {
					$data->last_update = timestamp_to_user_defined($data->last_update, $_SESSION['timeformat']);
				}
			}

			foreach ($data->posts as $id => $val) {
				$ti = date_info($val->timestamp);
				if ($ti['today']) {
					$data->posts[$id]->timestamp = timestamp_to_user_defined($val->timestamp, 'H:i');
				} elseif ($ti['yesterday']) {
					$data->posts[$id]->timestamp = 'Yesterday';
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
