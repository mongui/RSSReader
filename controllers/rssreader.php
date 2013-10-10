<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Rssreader extends ControllerBase
{
	function __construct()
	{
		parent::__construct();

		if( !isset($_SESSION) )
			session_start();
		
		//http://simplepie.org/wiki/reference/start
		$this->load->helper('url');
		$this->load->model('connections');
		$this->load->model('manage_users');
		$this->load->library('minifier');

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

			$html = $this->load->view('main', $data, TRUE);
			echo $this->minifier->minify_html($html);
		}
		// Send him to login form.
		else
			redirect( site_url('login') );
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
			$html = $this->load->view('add', NULL, TRUE);
			echo $this->minifier->minify_html($html);
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
		{
			$html = $this->load->view('importfile', NULL, TRUE);
			echo $this->minifier->minify_html($html);
		}
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
								'show_favicons'				=> ( $_POST['show_favicons']  ) ? $_POST['show_favicons']  : 'false',
								'feed_updatable'			=> ( $_POST['feed_updatable'] ) ? $_POST['feed_updatable'] : 'false'
							);

				$this->connections->save_config($serverdata);
			}

			if ( isset($rtrn) || (!$_POST['curPassword'] && !$_POST['newPassword']) )
			{
				$_SESSION['timeformat']	= $userdata['time_format'];
				$_SESSION['language']	= $userdata['language'];
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
				$data['is_admin']	= TRUE;
				$data['timezones']	= file($this->config->get('document_root') . $this->config->get('index_path') . '/timezones.txt');
				$data['timezones']	= array_map('trim', $data['timezones']);
			}

			echo $this->load->view('preferences', $data, TRUE);
			//$html = $this->load->view('preferences', $data, TRUE);
			//echo $this->minifier->minify_html($html);
		}
	}

	public function feeds()
	{
		$feedlist = $this->connections->feeds_per_user($_SESSION['id'], $this->config->get('show_favicons'));
		echo json_encode($feedlist);
	}

	public function posts()
	{
		$feed_id = filter_var($_POST["feed"], FILTER_SANITIZE_STRING);
		$feed_next = ( isset($_POST["next"]) ) ? filter_var($_POST["next"], FILTER_SANITIZE_NUMBER_INT) : 0;

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
				$data->name	= 'Search for &quot;<i>' . $search_string . '</i>&quot;';
		}

		if ( !empty($data) )
		{
			$posts = $this->connections->posts_from_feed($feed_id, $feed_next, $_SESSION['id'], $search_string);

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
		if ( isset($_POST["feed"]	)	)	$feed	= filter_var($_POST["feed"], FILTER_SANITIZE_STRING			);
		if ( isset($_POST["action"]	)	)	$action	= filter_var($_POST["action"], FILTER_SANITIZE_STRING		);
		if ( isset($_POST["folder"]	)	)	$folder	= filter_var(trim($_POST["folder"]), FILTER_SANITIZE_STRING	);
		if ( isset($_POST["value"]	)	)	$value	= filter_var($_POST["value"], FILTER_SANITIZE_STRING		);

		// Marking feed as readed?
		if ( isset($feed)		&& $action == 'readed'												)
		{
			$this->connections->set_readed_feed($feed, $_SESSION['id'], 1);
			echo 'success';
		}
		// Updating feed?
		elseif ( isset($feed)	&& $action == 'update'												)
		{
			if ( $this->connections->update_feed($feed) )
				echo 'success';
			else
				echo 'failure';
		}
		// Changing feed name?
		elseif ( isset($feed)	&& $action == 'name'		&& !empty($value)						)
		{
			if ( $this->connections->update_feed_name($feed, $value, $_SESSION['id']) )
				echo 'success';
			else
				echo 'failure';
		}

		// Sorting the feed list?
		elseif ( isset($_POST['value'])	&& $action == 'sort'										)
		{
			$value = filter_var_array($_POST['value'], FILTER_VALIDATE_INT);

			if ( $this->connections->move_feed($_SESSION['id'], $value) )
				echo 'success';
			else
				echo 'failure';
		}

		// Creating a new folder for feeds?
		elseif ( isset($feed)	&& $action == 'newfolder'	&& !empty($value)						)
		{
			if ( $folder = $this->connections->new_folder($_SESSION['id'], $value, $feed) )
				echo 'success';
			else
				echo 'failure';
		}
		// Removing a feed?
		elseif ( isset($feed)	&& $action == 'unsubscribe'											)
		{
			if ( $this->connections->unsubscribe_feed($feed, $_SESSION['id']) )
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
