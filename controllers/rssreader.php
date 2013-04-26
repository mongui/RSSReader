<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Rssreader extends ControllerBase
{
	function __construct()
	{
		parent::__construct();

		session_start ();
		//http://simplepie.org/wiki/reference/start
		$this->load->helper('url');
		$this->load->model('connections');
		$this->load->model('manage_users');

		$this->connections->load_config();
		date_default_timezone_set( $this->config->get('timezone') );
	}

	public function index()
	{
		// Is the user logged?
		if ( isset($_SESSION['id']) )
		{
			$data = array();

			$this->load->helper('phone');
			if ( is_phone() )
				$data['is_phone'] = TRUE;

			$this->load->view('main', $data);
		}
		// Send him to login form.
		else
			redirect( site_url('login') );
	}

	public function update( $feed_id = FALSE )
	{
		error_reporting(E_ERROR);
		if ( $feed_id )
				return $this->connections->update_feed($feed_id);
	}

	public function update_all()
	{
		echo 'Updating... ';

		set_time_limit(300);
		ini_set('memory_limit', '256M');

		$seconds_ago = $this->config->get('minutes_between_updates') * 60;
		$max_feeds = $this->config->get('max_feeds_per_update');

		$feeds = $this->connections->feeds_not_uptated( $seconds_ago, $max_feeds );

		foreach ( $feeds as $feed )
		{
			echo $feed->id_feed . ', ';
			$updated = $this->connections->update_feed($feed->id_feed);

			if ( $updated )
				$this->connections->change_feed_last_update ($feed->id_feed);
		}
	}

	public function add()
	{
		if( isset($_POST["feed_url"]) )
		{
			$feed_url = filter_var($_POST["feed_url"], FILTER_SANITIZE_STRING);
			$feed_url = str_replace("https://", "http://", $feed_url);

			if( !empty($feed_url) )
			{
				$feed_id = $this->add_feed($feed_url);

				if ( $feed_id > 0 )
					echo 'success';
				else
					echo 'error';
			}
		}
		else
		{
			echo $this->load->view('add', NULL, TRUE);
		}
	}

	public function importfile()
	{
		if ( isset($_FILES['file']) )
		{
			$allowedExts = array('xml', 'opml');
			$extension = end(explode('.', $_FILES['file']['name']));
			if (
				$_FILES['file']['type'] == 'text/xml'
				&& $_FILES['file']['size'] < 100000 // 100Kb
				&& in_array($extension, $allowedExts)
			)
			{
				if ($_FILES['file']['error'] > 0)
				{
					echo 'failure';
				}
				else
				{
					if (file_exists($_FILES["file"]["tmp_name"])) {
						error_reporting(E_ERROR);
						$xml = simplexml_load_file($_FILES["file"]["tmp_name"]);

						foreach ( $xml->body->outline as $feed )
						{
							$this->add_feed(filter_var($feed['xmlUrl'], FILTER_SANITIZE_STRING));
						}

						echo 'success';
					}
					else
					{
						exit('failure');
					}

				}
			}
			else
			{
				echo 'failure';
			}
		}
		else
			echo $this->load->view('importfile', NULL, TRUE);
	}

	private function add_feed( $url )
	{
		$feed_id = $this->connections->insert_feed($url, $_SESSION['id']);

		// If it wasn't in the database.
		if ( $feed_id > 0 )
			$this->connections->update_feed($feed_id);

		return $feed_id;
	}

	public function preferences()
	{
		if( isset($_POST['timeformat']) && isset($_POST['language']) )
		{
			$userdata = array (
							'time_format'	=> filter_var($_POST['timeformat'], FILTER_SANITIZE_STRING),
							'language'		=> filter_var($_POST['language'], FILTER_SANITIZE_STRING)
							);

			if ( $_POST['curPassword'] && $_POST['newPassword'] )
			{
				$userdata['password']	= $_POST['curPassword'];
				$userdata['newpassword']= $_POST['newPassword'];

				$rtrn = $this->manage_users->update_user ( $userdata );
			}

			if ( $_POST['timezone'] )
			{
				$serverdata = array (
								'timezone'					=> filter_var($_POST['timezone'], FILTER_SANITIZE_STRING),
								'minutes_between_updates'	=> filter_var($_POST['mins_updates'], FILTER_VALIDATE_INT),
								'max_feeds_per_update'		=> filter_var($_POST['max_feeds'], FILTER_VALIDATE_INT),
								'feed_updatable'			=> ( $_POST['feed_updatable'] == 1 ) ? 'true' : 'false'
							);

				$this->connections->save_config($serverdata);
			}

			if ( isset($rtrn) || (!$_POST['curPassword'] && !$_POST['newPassword']) )
			{
				$_SESSION['timeformat'] = $userdata['time_format'];
				$_SESSION['language'] = $userdata['language'];
				echo 'success';
			}
			else
				echo 'curPass';
		}
		else
		{
			$data = NULL;
			if ( $this->config->get('admin') == $_SESSION['id'] )
			{
				$data['is_admin'] = TRUE;
				$data['timezones'] = file($this->config->get('document_root') . $this->config->get('index_path') . '/timezones.txt');
				$data['timezones'] = array_map('trim', $data['timezones']);
			}

			echo $this->load->view('preferences', $data, TRUE);
		}
	}

	public function feeds()
	{
		$feedlist = $this->connections->feeds_per_user($_SESSION['id']);
		echo json_encode($feedlist);
	}

	public function posts()
	{
		$feed_id = filter_var($_POST["feed"], FILTER_SANITIZE_STRING);

		if ( !is_numeric($feed_id) && $feed_id <> 'unreaded' && $feed_id <> 'starred' )
		{
			$search_string = $feed_id;
			$feed_id = 'search';
		}
		else
			$search_string = NULL;

		if ( is_numeric($feed_id) )
			$data = $this->connections->feed_data_from_id($feed_id);
		else
		{
			$data->id_feed		= 0;
			$data->site			= '';
			$data->url			= '';
			$data->last_update	= '';
			$data->favicon		= NULL;

			if ( $feed_id == 'unreaded' || $feed_id == 'starred' )
				$data->name		= ucfirst($feed_id) . ' posts';

			elseif ( $feed_id == 'search' )
				$data->name		= 'Search for &quot;<i>' . $search_string . '</i>&quot;';
		}

		if ( !empty($data) )
		{
			$posts = $this->connections->posts_from_feed($feed_id, 50, $_SESSION['id'], $search_string);

			if ( $posts )
			{
				foreach ($posts as $post)
					$data->posts['post-' . $post->id_post] = $post;
			}
			else
			{
				echo json_encode($data);
				return FALSE;
			}

			$this->load->helper('time');
			if ( isset($data->last_update) && $data->last_update <> '' )
				$data->last_update = sql_timestamp_to_user_defined ($data->last_update, $_SESSION['timeformat']);

			foreach ($data->posts as $id => $val)
				$data->posts[$id]->timestamp = sql_timestamp_to_user_defined ($val->timestamp, $_SESSION['timeformat']);

			echo json_encode($data);
		}
		else
		{
			echo "Feed not found!";
		}
	}

	public function managefeed()
	{
		if ( isset($_POST["feed"])		)	$feed	= filter_var($_POST["feed"], FILTER_SANITIZE_STRING);
		if ( isset($_POST["action"])	)	$action	= filter_var($_POST["action"], FILTER_SANITIZE_STRING);
		if ( isset($_POST["value"])		)	$value	= filter_var(trim($_POST["value"]), FILTER_SANITIZE_STRING);

		if ( isset($feed)		&& $action == 'readed'						)
		{
			$this->connections->set_readed_feed($feed, $_SESSION['id'], 1);
			echo 'success';
		}
		elseif ( isset($feed)	&& $action == 'update'						)
		{
			if ( $this->update($feed) )
				echo 'success';
			else
				echo 'failure';
		}
		elseif ( isset($feed)	&& $action == 'name'	&& !empty($value)	)
		{
			if ($this->connections->update_feed_name($feed, $value, $_SESSION['id']) )
				echo 'success';
			else
				echo 'failure';
		}
		elseif ( isset($feed)	&& $action == 'unsubscribe'					)
		{
			if ($this->connections->unsubscribe_feed($feed, $_SESSION['id']) )
				echo 'success';
			else
				echo 'failure';
		}
		else
			echo 'failure';
	}

	public function managepost()
	{
		if ( isset($_POST["post"])		)	$post	= filter_var($_POST["post"], FILTER_SANITIZE_STRING);
		if ( isset($_POST["action"])	)	$action	= filter_var($_POST["action"], FILTER_SANITIZE_STRING);
		if ( isset($_POST["state"])		)	$state	= ( isset($_POST["state"]) ) ? filter_var($_POST["state"], FILTER_SANITIZE_STRING) : FALSE;

		if ( isset($post)		&& $action == 'readed'	)
		{
			$this->connections->set_readed_post($post, $_SESSION['id'], $state);
			echo 'success';
		}
		elseif ( isset($post)	&& $action == 'starred'	)
		{
			$this->connections->set_starred_post($post, $_SESSION['id'], $state);
			echo 'success';
		}
		else
			echo 'failure';
	}

}
