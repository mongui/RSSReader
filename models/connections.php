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

	function feeds_not_uptated ( $seconds, $max_feeds = NULL )
	{
		$seconds = time() - $seconds;

		$limit = '';
		if ( isset($max_feeds) )
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
			$newfeeddata = $this->get_feed_by_url($feed_url);

			if ( isset($newfeeddata['name']) )
			{
				// Adds to the feeds table.
				$dbname				= preg_replace('/<[^>]*>/', '', $newfeeddata['name']);
				$dbfavicon			= $newfeeddata['favicon'];
				$dbsite				= $newfeeddata['site'];
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

	function feeds_per_user ( $user_id, $unread = TRUE )
	{
		if ( $unread )
		{
			$sql = "
				SELECT u.id_feed, f.favicon, u.name, count(distinct p.id_post)-count( IF(r.id_user=$user_id, 1, NULL) ) AS count
				FROM feeds f
				LEFT JOIN posts p ON p.id_feed = f.id_feed
				LEFT JOIN readed_posts r ON p.id_post = r.id_post
				LEFT JOIN user_feed u ON f.id_feed = u.id_feed
				WHERE u.id_user = $user_id
				GROUP BY u.id_feed
			";
		}
		else
		{
			$sql = "
				SELECT *
				FROM user_feed
				WHERE id_user = $user_id
				ORDER BY position
			";
		}
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
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

	function posts_from_feed ( $feed_id, $max = 50, $user_id = NULL, $search = NULL )
	{
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
					LIMIT $max
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
					LIMIT $max
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
					LIMIT $max
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
					LIMIT $max
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
					LIMIT $max
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

	function get_feed_by_url ( $url )
	{
		$this->load->library('simplepie');

		$this->simplepie->set_feed_url($url);
		$this->simplepie->set_stupidly_fast(true);
		$this->simplepie->init();
		$this->simplepie->handle_content_type();
		return array(
					'name'			=> $this->simplepie->get_title(),
					'favicon'		=> 'http://g.etfv.co/' . urlencode($this->simplepie->get_link()),
					'site'			=> $this->simplepie->get_link()
					);
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

		$this->load->library('simplepie');
		$feed = $this->feed_data_from_id ($feed_id);

		$this->simplepie->set_feed_url($feed->url);

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
			$file = fopen($feed->url, 'r');
			$content = stream_get_contents($file);

			$patterns = array('&aacute;', '&eacute;', '&iacute;', '&oacute;', '&uacute;', '&Aacute;', '&Eacute;', '&Iacute;', '&Ooacute;', '&Uacute;', '&ntilde;', '&Ntilde;');
			$replacements = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');

			$convmap = array(0x0, 0x10000, 0, 0xfffff);
			$content = mb_decode_numericentity($content, $convmap, 'UTF-8');

			$content = preg_replace('/(?!&[a-z]{2,6};)(&)/', '&amp;', $content);
			$content = str_replace($patterns, $replacements, $content);

			$this->simplepie->set_raw_data($content);

			// This allows Youtube videos.
			$strip_htmltags = $this->simplepie->strip_htmltags;
			unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
			$this->simplepie->set_input_encoding('UTF-8');
			$this->simplepie->init();
			$this->simplepie->handle_content_type();
		}

		foreach ( $this->simplepie->get_items() as $item )
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

	function unsubscribe_feed ( $feed, $user )
	{
		$sql = "DELETE FROM user_feed WHERE id_feed = $feed AND id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		return $dbdata->execute();
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
