<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Connections extends ModelBase
{
	private $conn;

	function __construct ()
	{
		parent::__construct();

		$this->conn = $this->db;
	}

	function feed_in_database ( $feed_url )
	{
		$sql = "
			SELECT *
			FROM feeds
			WHERE url = '$feed_url'
			LIMIT 1
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		if ( $dbdata->rowCount() > 0 )
			return $dbdata->fetchObject();
		else
			return NULL;
	}

	function change_feed_last_update ( $feed_id )
	{
		$sql = "
			UPDATE feeds
			SET last_update = NOW()
			WHERE id_feed = $feed_id
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
	}

	function feeds_not_uptated ( $seconds, $max_feeds = 0 )
	{
		$seconds = time() - $seconds;

		$limit = '';
		if ( $max_feeds > 0 )
			$limit = 'LIMIT ' . $max_feeds;

		$sql = "
			SELECT *
			FROM feeds
			WHERE last_update < FROM_UNIXTIME($seconds)
			$limit
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	function user_has_feed ( $user_id, $feed_id )
	{
		$sql = "
			SELECT *
			FROM user_feed
			WHERE id_feed = $feed_id AND id_user = $user_id
			LIMIT 1
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->rowCount();
	}

	function insert_feed ( $feed_url, $user_id = NULL )
	{
		// Is the feed already in the database?
		$feed = $this->feed_in_database($feed_url);

		if ( !isset($feed->id_feed) )
		{
			$feed_data = $this->get_feed_by_url($feed_url, TRUE);

			if ( isset($feed_data) )
			{
				// Adds to the feeds table.
				$dbname				= preg_replace('/<[^>]*>/', '', $feed_data->get_title());
				$dbfavicon			= 'http://g.etfv.co/' . urlencode($feed_data->get_link());
				$dbsite				= $feed_data->get_link();
				$dburl				= $feed_url;

				$sql = "
					INSERT INTO feeds (name, favicon, site, url)
					VALUES ('$dbname', '$dbfavicon', '$dbsite', '$dburl')
				";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();

				$feed = $this->feed_in_database($feed_url);
			}
			else
			{
				return 0;
			}
		}

		// If user_id exists, adds to the user_feed table.
		if ( isset($user_id) && isset($feed->id_feed) && $this->user_has_feed($user_id, $feed->id_feed) )
		{
			return 0;
		}
		elseif ( $user_id && isset($feed->id_feed) )
		{
			$dbid_feed				= $feed->id_feed;
			$dbname					= preg_replace('/<[^>]*>/', '', $feed->name);
			$dbid_user				= $user_id;
			$dbposition				= 0;

			$sql = "
				INSERT INTO user_feed (id_feed, name, id_user, position)
				VALUES ('$dbid_feed', '$dbname', $dbid_user, $dbposition)
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}

		return isset($feed->id_feed) ? $feed->id_feed : 0;
	}

	function feeds_per_user ( $user_id, $favicon = TRUE, $unread = TRUE )
	{
		$get_fav = ( $favicon ) ? 'f.favicon,' : '';
		if ( $unread )
		{
			$sql = "
				SELECT u.id_feed, o.name AS foldername, o.id_folder, o.position AS folder_position, $get_fav u.name, count(distinct p.id_post)-count( IF(r.id_user=$user_id, 1, NULL) ) AS count, f.site, f.url, f.last_update, u.position
				FROM feeds f
				LEFT JOIN posts p ON p.id_feed = f.id_feed
				LEFT JOIN readed_posts r ON p.id_post = r.id_post
				LEFT JOIN user_feed u ON f.id_feed = u.id_feed
				LEFT JOIN folders o ON o.id_folder = u.id_folder AND u.id_user = o.id_user
				WHERE u.id_user = $user_id
				GROUP BY u.id_feed
				ORDER BY o.id_folder, position ASC
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
			$feedsraw = $dbdata->fetchAll(PDO::FETCH_OBJ);
			$feeds = array();
			$folders = array();

			foreach ( $feedsraw as $i => $feeeed )
			{
				$feedsraw[$i] = (object)array_filter((array)$feedsraw[$i], 'strlen');
				if ( isset($feedsraw[$i]->id_folder) )
				{
					if ( !isset($folders[$feedsraw[$i]->folder_position]) )
					{
						$folders[$feedsraw[$i]->folder_position] = array(
																'folder'	=> $feedsraw[$i]->id_folder,
																'name'		=> $feedsraw[$i]->foldername,
																'position'	=> $feedsraw[$i]->folder_position,
																'feeds'		=> array()
															);
					}

					array_push($folders[$feedsraw[$i]->folder_position]['feeds'], $feedsraw[$i]);
				}
				else
					$feeds[$feedsraw[$i]->position] = $feedsraw[$i];
			}

			foreach ( $folders as $pos => $fldr )
			{
				if ( !isset($feeds[$pos]) )
				{
					$feeds[$pos] = (object)$fldr;
					unset($folders[$pos]);
				}
			}

			if ( count($folders) > 0 )
				$feeds = array_merge($feeds, $folders);

			ksort($feeds);

			return $feeds;
		}
		else
		{
			$sql = "
				SELECT *
				FROM user_feed
				WHERE id_user = $user_id
				ORDER BY position
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			return $dbdata->fetchAll(PDO::FETCH_OBJ);
		}
	}

	function feed_data_from_id ( $feed_id )
	{
		$sql = "
				SELECT *
				FROM feeds
				WHERE id_feed = $feed_id
				LIMIT 1
			";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	function user_feed_from_id ( $feed_id, $user )
	{
		$sql = "
				SELECT *
				FROM user_feed
				WHERE id_feed = $feed_id AND id_user = $user
				LIMIT 1
			";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}


	function posts_from_feed ( $feed_id, $next = 0, $user_id = NULL, $search = NULL )
	{
		$max = $this->config->get('max_posts_to_show');
		if ( $user_id && $feed_id == 'unreaded' )
		{
			$sql = "
					SELECT
						p.*,
						NULL AS readed,
						(select id_post from starred_posts where id_post = p.id_post AND id_user = u.id_user) AS starred
					FROM user_feed u, posts p
					WHERE
						p.id_feed = u.id_feed AND
						u.id_user = $user_id AND
						p.id_post NOT IN (select id_post from readed_posts where id_post = p.id_post AND id_user = u.id_user)
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
		}
		elseif ( $user_id && $feed_id == 'starred' )
		{
			$sql = "
					SELECT
						p.*,
						(select id_post from readed_posts where id_post = p.id_post AND id_user = u.id_user) AS readed,
						'1' AS starred
					FROM user_feed u, posts p
					WHERE
						p.id_feed = u.id_feed AND
						u.id_user = $user_id AND
						p.id_post IN (select id_post from starred_posts where id_post = p.id_post AND id_user = u.id_user)
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
		}
		elseif ( $user_id && $feed_id == 'search' && isset($search) )
		{
			$sql = "
					SELECT
						p.*, f.site,
						(select id_post from readed_posts where id_post = p.id_post AND id_user = u.id_user) AS readed,
						(select id_post from starred_posts where id_post = p.id_post AND id_user = u.id_user) AS starred
					FROM posts p, user_feed u, feeds f
					WHERE
						p.id_feed = u.id_feed AND
						p.id_feed = f.id_feed AND
						u.id_user = $user_id AND
						(
							p.content LIKE '%$search%' OR
							p.title LIKE '%$search%'
						)
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
		}
		elseif ( $user_id )
		{
			$sql = "
					SELECT
						p.*, f.site,
						(select id_post from readed_posts where id_post = p.id_post AND id_user = u.id_user) AS readed,
						(select id_post from starred_posts where id_post = p.id_post AND id_user = u.id_user) AS starred
					FROM posts p, user_feed u, feeds f
					WHERE
						p.id_feed = u.id_feed AND
						p.id_feed = f.id_feed AND
						u.id_user = $user_id AND
						p.id_feed = $feed_id
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
		}
		else
		{
			$sql = "
					SELECT *
					FROM posts
					WHERE
						posts.id_feed = $feed_id
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
		}

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		$data = $dbdata->fetchAll(PDO::FETCH_OBJ);

		if ( !empty($data) )
			return $data;
		else
			return FALSE;
	}

	function get_feed_by_url ( $url, $fast = FALSE )
	{
		$this->load->library('simplepie');

		$this->simplepie->set_feed_url($url);

		if ( $fast )
			$this->simplepie->set_stupidly_fast(true);

		error_reporting(E_WARNING);
		// This allows Youtube videos.
		$strip_htmltags = $this->simplepie->strip_htmltags;
		unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
		$this->simplepie->strip_htmltags($strip_htmltags);

		$this->simplepie->init();
		$this->simplepie->handle_content_type();

		// If RSS is malformed.
		if ( $this->simplepie->error() )
		{
			unset ($this->simplepie);
			$this->simplepie = new SimplePie();
			$file = fopen($url, 'r');
			$content = stream_get_contents($file);

			$patterns = array('&aacute;', '&eacute;', '&iacute;', '&oacute;', '&uacute;', '&Aacute;', '&Eacute;', '&Iacute;', '&Ooacute;', '&Uacute;', '&ntilde;', '&Ntilde;');
			$replacements = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');

			$convmap = array(0x0, 0x10000, 0, 0xfffff);
			$content = mb_decode_numericentity($content, $convmap, 'UTF-8');

			$content = preg_replace('/(?!&[a-z]{2,6};)(&)/', '&amp;', $content);
			$content = str_replace($patterns, $replacements, $content);

			$content = preg_replace('/(<script.+?>)(<\/script>)/i', '', $content);
			$content = preg_replace('/<script.+?\/>/i', '', $content);

			$this->simplepie->set_raw_data($content);

			// This allows Youtube videos.
			$strip_htmltags = $this->simplepie->strip_htmltags;
			unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
			$this->simplepie->set_input_encoding('UTF-8');
			$this->simplepie->init();
			$this->simplepie->handle_content_type();
		}

		return $this->simplepie;
	}

	function update_feed ( $feed_id )
	{
		$last_posts = $this->posts_from_feed($feed_id);

		if ( $last_posts )
		{
			$lp_timestamp	= strtotime( $last_posts[0]->timestamp );
			$lp_title		= $last_posts[0]->title;
		}
		else
		{
			$lp_timestamp	= 0;
			$lp_title		= '';
		}

		$feed = $this->feed_data_from_id ($feed_id);

		$feed_data = $this->get_feed_by_url($feed->url);

		foreach ( $feed_data->get_items() as $item )
		{
			if ( $item->get_authors() )
				foreach ( $item->get_authors() as $auth )
					$authors[] = $auth->get_name();

			$data[] = array(
				'id_feed'			=> $feed_id,
				'timestamp'			=> date('Y-m-d H:i:s', strtotime( $item->get_date() )),
				'author'			=> ( isset($authors) && count($authors) > 0 ) ? implode (',', $authors) : '',
				'url'				=> $item->get_link(),
				'title'				=> addslashes(preg_replace('/<[^>]*>/', '', $item->get_title())),
				'content'			=> str_replace("'", "&#39;", $item->get_content())
			);
			unset($authors);
			$or_where[] = "url = '" . $item->get_link() . "'";
		}

		if ( !empty($or_where) )
			$or_where = 'WHERE ' . implode(' OR ', $or_where);
		else
			$or_where = '';

		$sql = "
			SELECT url
			FROM posts
			$or_where
		";
		$rtrn = $this->conn->prepare($sql);
		$rtrn->execute();

		foreach ( $rtrn->fetchAll(PDO::FETCH_OBJ) as $result )
			$rslt[] = $result->url;

		if ( !empty($data) )
		{
			foreach ( $data as $key => $item )
			{
				if ( isset($rslt) && in_array($item['url'], $rslt) )
					unset($data[$key]);
			}
		}

		if ( !empty($data) )
		{
			foreach ( $data as $item )
				$insert[] = "(".$item['id_feed'].",	'".$item['timestamp']."', '".$item['author']."', '".$item['url']."', '".$item['title']."', '".$item['content']."')";

			$insert = implode(', ', $insert);

			$sql = "
				INSERT INTO posts
				(id_feed, timestamp, author, url, title, content)
				VALUES $insert
			";

			try {
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();
			}
			catch (PDOException $err) {
				echo '\n\n\nError: ' . $err . '\n\n\n';
			}
		}

		return TRUE;
	}

	function set_readed_post ( $post, $user, $readed = FALSE )
	{
		if ( $readed )
		{
			$sql = "
				SELECT *
				FROM readed_posts
				WHERE id_post = $post AND id_user = $user
			";
			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			if ( $dbdata->rowCount() == 0 )
			{
				$sql = "INSERT INTO readed_posts (id_post, id_user) VALUES ($post, $user)";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();
			}
		}
		else
		{
			$sql = "DELETE FROM readed_posts WHERE id_post = $post AND id_user = $user";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	function set_starred_post ( $post, $user, $starred = FALSE )
	{
		if ( $starred )
		{
			$sql = "
				SELECT *
				FROM starred_posts
				WHERE id_post = $post AND id_user = $user
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			if ( $dbdata->rowCount() == 0 )
			{
				$sql = "INSERT INTO starred_posts (id_post, id_user) VALUES ($post, $user)";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();
			}
		}
		else
		{
			$sql = "DELETE FROM starred_posts WHERE id_post = $post AND id_user = $user";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	function set_readed_feed ( $feed, $user )
	{
		$sql = "
			SELECT id_post
			FROM posts p
			WHERE id_feed = $feed AND id_post NOT IN (SELECT id_post FROM readed_posts WHERE id_post = p.id_post AND id_user = $user)
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
		$obj = $dbdata->fetchAll(PDO::FETCH_OBJ);

		foreach ( $obj as $unreaded )
		{
			$data[] = "(" . $unreaded->id_post . ", $user)";
		}
		if ( !empty($data) )
		{
			$insert = implode(', ', $data);
			$sql = "INSERT INTO readed_posts (id_post, id_user) VALUES $insert";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	function update_feed_name ( $feed, $newname, $user )
	{
		$sql = "UPDATE user_feed SET name = '$newname' WHERE id_feed = $feed AND id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->lastInsertId();
	}

	function new_folder ( $user, $foldername, $idfeed )
	{
		$feed = $this->user_feed_from_id($idfeed, $user);

		$sql = "INSERT INTO folders (id_user, position, name) VALUES ($user, $feed->position, '$foldername')";

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		$last_id = $this->conn->lastInsertId('id_folder');
		if ( is_numeric($last_id) )
		{
			$sql = "UPDATE user_feed SET position = 1, id_folder = $last_id WHERE id_user = $user AND id_feed = $idfeed";
			$dbdata = $this->conn->prepare($sql);

			try {
				$dbdata->execute();
				return $last_id;
			}
			catch (PDOException $err) {
				return FALSE;
			}
		}

		return FALSE;
	}

	function remove_folder ( $user, $folder )
	{
		$sql = "DELETE FROM folders WHERE id_folder = $folder AND id_user = $user";

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbdata = $this->conn->prepare($sql);

		try {
			$dbdata->execute();
			return TRUE;
		}
		catch (PDOException $err) {
			return FALSE;
		}
	}

	function get_folders ( $user )
	{
		$sql = "SELECT * FROM folders WHERE id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
		$list = $dbdata->fetchAll(PDO::FETCH_OBJ);

		if ( !empty($list) )
			return $list;
		else
			return FALSE;
	}

	function move_feed ( $user, $feed_data )
	{
		if ( empty($feed_data) )
			return FALSE;

		$feeds = $this->feeds_per_user($user, FALSE, FALSE);
		foreach ( $feeds as $f1 => $f2 )
			$feedsdb[] = $f2->id_feed;

		$folders = $this->get_folders($user);
		if ( is_array($folders) )
			foreach ( $folders as $f1 => $f2 )
				$fldrsdb[] = $f2->id_folder;

		$up_feeds = array();
		$up_folders = array();

		foreach ( $feed_data as $pos => $id )
		{
			if ( is_array($id) )
			{
				$pos2 = 1;
				$fldrlist[] = $id['folder'];
				$up_folders[] = array(
									'position'	=> ($pos+1),
									'folder'	=> $id['folder']
									);

				foreach ( $id['value'] as $id2 )
				{
					if ( in_array($id2, $feedsdb) )
					{
						$feedlist[] = $id2;
						$up_feeds[] = array(
											'position'	=> $pos2,
											'feed'		=> $id2,
											'folder'	=> $id['folder']
											);
						$pos2++;
					}
				}
			}
			else
			{
				if ( in_array($id, $feedsdb) )
				{
					$feedlist[] = $id;
					$up_feeds[] = array(
										'position'	=> ($pos+1),
										'feed'		=> $id,
										'folder'	=> 0
										);
				}
			}
		}

		$remainingfromdb = array_diff($feedsdb, $feedlist);
		if ( !empty( $remainingfromdb ) )
		{
			$pos = end($up_feeds);
			$pos = $pos['position'];
			foreach  ( $remainingfromdb as $remain )
			{
				$pos++;
				$feedlist[] = $id;
				$up_feeds[] = array(
									'position'	=> $pos,
									'feed'		=> $remain,
									'folder'	=> 0
									);
			}
		}

		if ( isset($fldrsdb) && isset($fldrlist) )
		{
			$remainingfromdb = array_diff($fldrsdb, $fldrlist);
			if ( !empty( $remainingfromdb ) )
				foreach  ( $remainingfromdb as $remain )
					$this->remove_folder($user, $remain);
		}

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql_feed = "UPDATE user_feed SET position = CASE id_feed ";
		foreach ( $up_feeds as $fd )
		{
			$sql_feed .= "WHEN " . $fd['feed'] . " THEN " . $fd['position'] . " ";
		}
		$sql_feed .= "ELSE position END, id_folder = CASE id_feed ";
		foreach ( $up_feeds as $fd )
		{
			$sql_feed .= "WHEN " . $fd['feed'] . " THEN " . $fd['folder'] . " ";
		}
		$sql_feed .= "ELSE id_folder END WHERE id_user = " . $user . " AND id_feed IN (" . implode(',', $feedlist) .")";

		$dbdata = $this->conn->prepare($sql_feed);
		try {
			$dbdata->execute();

			if ( is_array($up_folders) && isset($fldrlist) )
			{
				$sql_fldr = "UPDATE folders SET position = CASE id_folder ";
				foreach ( $up_folders as $fld )
				{
					$sql_fldr .= "WHEN " . $fld['folder'] . " THEN " . $fld['position'] . " ";
				}
				$sql_fldr .= "ELSE position END WHERE id_user = " . $user . " AND id_folder IN (" . implode(',', $fldrlist) .")";
				$dbdata = $this->conn->prepare($sql_fldr);

				try {
					$dbdata->execute();
					return TRUE;
				}
				catch (PDOException $err) {
					return FALSE;
				}
			}
			else
				return TRUE;
		}
		catch (PDOException $err) {
			return FALSE;
		}
	}

	function unsubscribe_feed ( $feed, $user )
	{
		$sql = "DELETE FROM user_feed WHERE id_feed = $feed AND id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		return $dbdata->execute();
	}

	function load_config ()
	{
		$sql = "SELECT * FROM config";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		foreach ( $dbdata->fetchAll(PDO::FETCH_OBJ) as $conf )
		{
			if ($conf->value == 'true') $conf->value = true;
			elseif ($conf->value == 'false') $conf->value = false;

			$this->config->set($conf->param, $conf->value);
		}
	}

	function save_config ( $data )
	{
		$sql = "UPDATE config SET value = CASE ";

		foreach ( $data as $key => $value )
		{
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
		}
		catch (PDOException $err) {
			//print_r('Error: ' . $err . '');
			echo 'error';
			die();
		}
	}
}

/*
truncate feeds;
truncate folders;
truncate posts;
truncate readed_posts;
truncate starred_posts;
truncate user_feed;
*/
