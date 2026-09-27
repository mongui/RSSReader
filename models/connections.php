<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Connections Model.
 *
 * @package		RSSReader
 * @subpackage	Models
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Connections extends ModelBase
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
	}

	/**
	 * User Has Feed?
	 *
	 * Returns TRUE if the user has an specific feed.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	bool
	 */
	function user_has_feed($user_id, $feed_id)
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

	/**
	 * Feeds per User
	 *
	 * Returns an array with the ordered feeds of an user.
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @param	bool
	 * @return	array
	 */
	function feeds_per_user($user_id, $favicon = TRUE, $unread = TRUE)
	{
		if (!isset($user_id)) {
			return FALSE;
		}

		$get_fav = ($favicon) ? 'f.favicon,' : '';
		if ($unread) {
			$sql = "
				SELECT u.id_feed, o.name AS foldername, o.id_folder, o.position AS folder_position, $get_fav u.name, count(distinct p.id_post)-count( IF(r.id_user=$user_id, 1, NULL) ) AS count, f.site, f.url, f.last_update, u.position, export_set(active, '1', '0', '', 1) AS active
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

			// We have feeds and folders. Now it's time to sort them.
			foreach ($feedsraw as $i => $feeeed) {
				$feedsraw[$i] = (object)array_filter((array)$feedsraw[$i], 'strlen');
				if (isset($feedsraw[$i]->id_folder)) {
					if (!isset($folders[$feedsraw[$i]->folder_position])) {
						$folders[$feedsraw[$i]->folder_position] = array(
																		'folder'	=> $feedsraw[$i]->id_folder,
																		'name'		=> $feedsraw[$i]->foldername,
																		'position'	=> $feedsraw[$i]->folder_position,
																		'feeds'		=> array()
																	);
					}

					array_push($folders[$feedsraw[$i]->folder_position]['feeds'], $feedsraw[$i]);
				} else {
					$feeds[$feedsraw[$i]->position] = $feedsraw[$i];
				}
			}

			foreach ($folders as $pos => $fldr) {
				if (!isset($feeds[$pos])) {
					$feeds[$pos] = (object)$fldr;
					unset($folders[$pos]);
				}
			}

			if (count($folders) > 0) {
				$feeds = array_merge($feeds, $folders);
			}

			ksort($feeds);

			return $feeds;
		} else {
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

	/**
	 * User Feed From ID
	 *
	 * Get the feed data from its ID.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	object
	 */
	function user_feed_from_id($feed_id, $user)
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

	/**
	 * Posts From Feed
	 *
	 * Returns an object array the required posts that differs
	 * depending of the parameters inserted:
	 * - If feed param is 'unreaded'.
	 * - If feed param is 'starred'.
	 * - If there is just a feed ID and user ID.
	 * - If there is just a feed ID.
	 *
	 * @access	public
	 * @param	integer/string
	 * @param	integer
	 * @return	object
	 */
	function posts_from_feed($feed_id, $next = 0, $user_id = NULL)
	{
		$this->load->model('configuration');

		$max = $this->config->get('max_posts_to_show');
		if ($user_id && $feed_id == 'unreaded') {
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
		} elseif ($user_id && $feed_id == 'starred') {
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
		} elseif ($user_id && $feed_id == 'lastreaded') {
			$sql = "
				SET @user=$user_id;
				SET @numrows=$max;
				SET @next=(SELECT count(*) FROM readed_posts WHERE id_user=@user)-@numrows-$next;
				PREPARE LSTREADED FROM 'SELECT id_post FROM readed_posts WHERE id_user=? LIMIT  ?, ?';
			";
			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			$sql = "EXECUTE LSTREADED USING @user, @next, @numrows;";
			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			$data = $dbdata->fetchAll(PDO::FETCH_OBJ);

			foreach ($data as $dt) {
				$readedposts[] = $dt->id_post;
			}

			$posts2 = implode(' OR p.id_post=', $readedposts);
			$sql = "
					SELECT
						p.*,
						1 AS readed,
						(select id_post from starred_posts where id_post = p.id_post AND id_user = r.id_user) AS starred
					FROM readed_posts r
					LEFT JOIN posts p ON p.id_post = r.id_post
					WHERE
						r.id_user = $user_id AND
						(p.id_post=$posts2)
			";
			unset($data, $dbdata, $posts2);
		} elseif ($user_id) {
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
		} else {
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

		// Reorder the last readed posts and return them.
		if (isset($readedposts) && !empty($data)) {
			$data2 = array();

			for ($i=count($readedposts)-1; $i >= 0; $i--) {
				foreach ($data as $dtk => $dt) {
					if ($dt->id_post == $readedposts[$i]) {
						$data2[$dtk] = $dt;
					}
				}
			}
			return (object)$data2;
		} elseif (!empty($data)) {
			return $data;
		} else {
			return FALSE;
		}
	}

	/**
	 * Set Readed Post
	 *
	 * Set a post as readed/not readed in the database.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @param	bool
	 * @return	void
	 */
	function set_readed_post($post, $user, $readed = FALSE)
	{
		if ($readed) {
			$sql = "
				SELECT *
				FROM readed_posts
				WHERE id_post = $post AND id_user = $user
			";
			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			if ($dbdata->rowCount() == 0) {
				$sql = "INSERT INTO readed_posts (id_post, id_user) VALUES ($post, $user)";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();
			}
		} else {
			$sql = "DELETE FROM readed_posts WHERE id_post = $post AND id_user = $user";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	/**
	 * Set Starred Post
	 *
	 * Set a post as starred/not starred in the database.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @param	bool
	 * @return	void
	 */
	function set_starred_post($post, $user, $starred = FALSE)
	{
		if ($starred) {
			$sql = "
				SELECT *
				FROM starred_posts
				WHERE id_post = $post AND id_user = $user
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();

			if ($dbdata->rowCount() == 0) {
				$sql = "INSERT INTO starred_posts (id_post, id_user) VALUES ($post, $user)";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();
			}
		} else {
			$sql = "DELETE FROM starred_posts WHERE id_post = $post AND id_user = $user";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	/**
	 * Set Readed Feed
	 *
	 * Sets every post of a feed readed for an user.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	void
	 */
	function set_readed_feed($feed, $user)
	{
		$sql = "
			SELECT id_post
			FROM posts p
			WHERE id_feed = $feed AND id_post NOT IN (SELECT id_post FROM readed_posts WHERE id_post = p.id_post AND id_user = $user)
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
		$obj = $dbdata->fetchAll(PDO::FETCH_OBJ);

		foreach ($obj as $unreaded) {
			$data[] = "(" . $unreaded->id_post . ", $user)";
		}
		if (!empty($data)) {
			$insert = implode(', ', $data);
			$sql = "INSERT INTO readed_posts (id_post, id_user) VALUES $insert";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
		}
	}

	/**
	 * Update Feed Name
	 *
	 * Changes the feed name in the user_feed table
	 * and returns the ID of the affected feed.
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @param	integer
	 * @return	integer
	 */
	function update_feed_name($feed, $newname, $user)
	{
		$sql = "UPDATE user_feed SET name = '$newname' WHERE id_feed = $feed AND id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->lastInsertId();
	}

	/**
	 * New Folder
	 *
	 * Creates a new folder and puts a feed inside of
	 * it and returns the ID o the new folder.
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @param	integer
	 * @return	integer
	 */
	function new_folder($user, $foldername, $idfeed)
	{
		$feed = $this->user_feed_from_id($idfeed, $user);

		$sql = "INSERT INTO folders (id_user, position, name) VALUES ($user, $feed->position, '$foldername')";

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		$last_id = $this->conn->lastInsertId('id_folder');
		if (is_numeric($last_id)) {
			$sql = "UPDATE user_feed SET position = 1, id_folder = $last_id WHERE id_user = $user AND id_feed = $idfeed";
			$dbdata = $this->conn->prepare($sql);

			try {
				$dbdata->execute();
				return $last_id;
			} catch (PDOException $err) {
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * Remove Folder
	 *
	 * Deletes a specific folder.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	bool
	 */
	function remove_folder($user, $folder)
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

	/**
	 * Get Folders
	 *
	 * Returns the list of folders of an user.
	 *
	 * @access	public
	 * @param	integer
	 * @return	object
	 */
	function get_folders($user)
	{
		$sql = "SELECT * FROM folders WHERE id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
		$list = $dbdata->fetchAll(PDO::FETCH_OBJ);

		if (!empty($list)) {
			return $list;
		} else {
			return FALSE;
		}
	}

	/**
	 * Move Feed
	 *
	 * Changes the position of a feed in the user's list.
	 *
	 * @access	public
	 * @param	integer
	 * @param	array
	 * @return	bool
	 */
	function move_feed($user, $feed_data)
	{
		if (empty($feed_data)) {
			return FALSE;
		}

		// Get the feeds.
		$feeds = $this->feeds_per_user($user, FALSE, FALSE);
		foreach ($feeds as $f1 => $f2) {
			$feedsdb[] = $f2->id_feed;
		}

		// Get the folders.
		$folders = $this->get_folders($user);
		if (is_array($folders)) {
			foreach ($folders as $f1 => $f2) {
				$fldrsdb[] = $f2->id_folder;
			}
		}

		$up_feeds = array();
		$up_folders = array();

		// Sort them in arrays.
		foreach ($feed_data as $pos => $id) {
			if (is_array($id)) {
				$pos2 = 1;
				$fldrlist[] = $id['folder'];
				$up_folders[] = array(
									'position'	=> ($pos+1),
									'folder'	=> $id['folder']
								);

				foreach ($id['value'] as $id2) {
					if (in_array($id2, $feedsdb)) {
						$feedlist[] = $id2;
						$up_feeds[] = array(
											'position'	=> $pos2,
											'feed'		=> $id2,
											'folder'	=> $id['folder']
										);
						$pos2++;
					}
				}
			} else {
				if (in_array($id, $feedsdb)) {
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
		if (!empty($remainingfromdb)) {
			$pos = end($up_feeds);
			$pos = $pos['position'];
			foreach  ($remainingfromdb as $remain) {
				$pos++;
				$feedlist[] = $id;
				$up_feeds[] = array(
									'position'	=> $pos,
									'feed'		=> $remain,
									'folder'	=> 0
								);
			}
		}

		if (isset($fldrsdb) && isset($fldrlist)) {
			$remainingfromdb = array_diff($fldrsdb, $fldrlist);
			if (!empty($remainingfromdb)) {
				foreach  ($remainingfromdb as $remain) {
					$this->remove_folder($user, $remain);
				}
			}
		}

		// Update their positions in the database.
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql_feed = "UPDATE user_feed SET position = CASE id_feed ";
		foreach ($up_feeds as $fd) {
			$sql_feed .= "WHEN " . $fd['feed'] . " THEN " . $fd['position'] . " ";
		}
		$sql_feed .= "ELSE position END, id_folder = CASE id_feed ";
		foreach ($up_feeds as $fd) {
			$sql_feed .= "WHEN " . $fd['feed'] . " THEN " . $fd['folder'] . " ";
		}
		$sql_feed .= "ELSE id_folder END WHERE id_user = " . $user . " AND id_feed IN (" . implode(',', $feedlist) .")";

		$dbdata = $this->conn->prepare($sql_feed);
		try {
			$dbdata->execute();

			if (is_array($up_folders) && isset($fldrlist)) {
				$sql_fldr = "UPDATE folders SET position = CASE id_folder ";
				foreach ($up_folders as $fld) {
					$sql_fldr .= "WHEN " . $fld['folder'] . " THEN " . $fld['position'] . " ";
				}
				$sql_fldr .= "ELSE position END WHERE id_user = " . $user . " AND id_folder IN (" . implode(',', $fldrlist) .")";
				$dbdata = $this->conn->prepare($sql_fldr);

				try {
					$dbdata->execute();
					return TRUE;
				} catch (PDOException $err) {
					return FALSE;
				}
			} else {
				return TRUE;
			}
		} catch (PDOException $err) {
			return FALSE;
		}
	}

	/**
	 * Feed To User
	 *
	 * Links a feed to a specific user making it available for him.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	bool
	 */
	function feed_to_user($feed_id = NULL, $user_id = NULL) {
		if (!isset($feed_id) || !isset($user_id)) {
			return FALSE;
		} elseif (
			   isset($user_id)
			&& isset($feed_id)
			&& $this->user_has_feed($user_id, $feed_id)
		) {
			return FALSE;
		} elseif ($user_id && isset($feed_id)) {
			// Gets the position that it's going to be in the user's feedlist.
			$sql = "
				SELECT MAX(position) as max
				FROM user_feed
				WHERE id_user = $user_id
			";

			$dbdata = $this->conn->prepare($sql);
			$dbdata->execute();
			$lastpos = $dbdata->fetchObject();

			// Gets the data from the Feeds table and adds it to the User_feed table.
			$this->load->model('updater');
			$feed = $this->updater->feed_data_from_id($feed_id);

			$dbid_feed				= $feed_id;
			$dbname					= preg_replace('/<[^>]*>/', '', $feed->name);
			$dbid_user				= $user_id;
			$dbposition				= ($lastpos->max + 1);

			$sql = "
				INSERT INTO user_feed (id_feed, name, id_user, position)
				VALUES ('$dbid_feed', '$dbname', $dbid_user, $dbposition)
			";

			$dbdata = $this->conn->prepare($sql);
			return $dbdata->execute();
		}

		return FALSE;
	}

	/**
	 * Unsubscribe Feed
	 *
	 * Removes a feed from the user feedlist.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	bool
	 */
	function unsubscribe_feed($feed, $user)
	{
		$sql = "DELETE FROM user_feed WHERE id_feed = $feed AND id_user = $user";

		$dbdata = $this->conn->prepare($sql);
		return $dbdata->execute();
	}

	/**
	 * Get All Feeds
	 *
	 * Recovers the main data of each feed stored in the database.
	 *
	 * @access	public
	 * @return	object
	 */
	function get_all_feeds()
	{
		$sql = "SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active FROM feeds";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Get Feed
	 *
	 * Recovers the main data of a feed stored in the database.
	 *
	 * @access	public
	 * @param	integer
	 * @return	object
	 */
	function get_feed($feed)
	{
		$sql = "SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active FROM feeds WHERE id_feed = ?";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute(array((int) $feed));

		return $dbdata->fetchObject();
	}

	/**
	 * Modify Feed
	 *
	 * Updates the name, site, url, favicon and active fields of a feed.
	 *
	 * @access	public
	 * @param	integer
	 * @param	array
	 * @return	bool
	 */
	function modify_feed($feed, $data)
	{
		$set = array();
		$params = array();

		foreach (array('name', 'site', 'url', 'favicon') as $field) {
			if (isset($data[$field])) {
				$set[] = "$field = ?";
				$params[] = trim($data[$field]);
			}
		}

		// Inserted as a number, like in Updater::active_feed(), so it works with the BIT column.
		if (isset($data['active'])) {
			$set[] = 'active = ' . (($data['active'] == 1) ? 1 : 0);
		}

		if (empty($set)) {
			return FALSE;
		}

		$params[] = (int) $feed;

		$sql = 'UPDATE feeds SET ' . implode(', ', $set) . ' WHERE id_feed = ?';

		$dbdata = $this->conn->prepare($sql);
		return $dbdata->execute($params);
	}

	/**
	 * Delete Feed
	 *
	 * Deletes a feed and everything related to it: the read and starred
	 * marks of its posts, its posts and its subscriptions.
	 * The tables are MyISAM, so there are no foreign keys doing it.
	 *
	 * @access	public
	 * @param	integer
	 * @return	bool
	 */
	function delete_feed($feed)
	{
		$feed = array((int) $feed);

		$queries = array(
			'DELETE r FROM readed_posts r INNER JOIN posts p ON p.id_post = r.id_post WHERE p.id_feed = ?',
			'DELETE s FROM starred_posts s INNER JOIN posts p ON p.id_post = s.id_post WHERE p.id_feed = ?',
			'DELETE FROM posts WHERE id_feed = ?',
			'DELETE FROM user_feed WHERE id_feed = ?',
			'DELETE FROM feeds WHERE id_feed = ?'
		);

		try {
			foreach ($queries as $sql) {
				$dbdata = $this->conn->prepare($sql);
				if (!$dbdata->execute($feed)) {
					return FALSE;
				}
			}
		} catch (PDOException $err) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Search Posts
	 *
	 * Searches the posts of the user's feeds, the newest first. All the terms
	 * must appear in the title or in the visible text of the content:
	 * - Words of 4 or more characters match the beginning of a word (cena: cenas).
	 * - Shorter words must match whole words (IA doesn't match iatrogenia).
	 * - "Quoted phrases" and words with symbols (wi-fi) must appear as they are.
	 * - -word or -"phrase" excludes the posts that contain it.
	 * Case and accents don't matter, except the ñ.
	 *
	 * It works in two steps: MySQL finds the candidates with the full-text index
	 * (when it exists) and LIKE, and PHP checks the terms in the visible text,
	 * so the HTML code of the posts (tags, URLs, embedded videos) isn't searched.
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @param	string	Cursor returned by the previous page ("next").
	 * @return	array	'posts' (objects) and 'next' (cursor of the next page or NULL).
	 */
	function search_posts($user_id, $query, $cursor = NULL)
	{
		$result = array('posts' => array(), 'next' => NULL);

		$terms = $this->_search_parse($query);
		if (empty($terms['include'])) {
			return $result;
		}

		$this->load->model('configuration');
		$max = (int) $this->config->get('max_posts_to_show');
		if ($max < 1) {
			$max = 50;
		}

		// Candidates checked in PHP on every request, at most.
		$window = 1000;

		// Cursor: "mode|timestamp|id_post" of the last candidate checked.
		// Mode: 'f' = full-text index, 'l' = LIKE only.
		$mode = NULL;
		$after = NULL;
		if ($cursor !== NULL && preg_match('/^([fl])\|(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\|(\d+)$/', $cursor, $m)) {
			$mode = $m[1];
			$after = array($m[2], (int) $m[3]);
		}

		$min_length = $this->_search_fulltext_min_length();
		$match_words = array();
		if ($min_length) {
			foreach ($terms['words'] as $word) {
				$length = mb_strlen($word, 'UTF-8');
				if ($length >= $min_length && $length <= 84) {
					$match_words[] = $word;
				}
			}
		}

		if ($mode === NULL) {
			$mode = empty($match_words) ? 'l' : 'f';
		}

		$candidates = $this->_search_candidates($user_id, $terms['words'], ($mode == 'f') ? $match_words : array(), $after, $window);

		// The full-text index ignores some words (e.g. its stopwords), so if it
		// doesn't find anything, the search is done again with LIKE only.
		if (empty($candidates) && $mode == 'f' && $after === NULL) {
			$mode = 'l';
			$candidates = $this->_search_candidates($user_id, $terms['words'], array(), NULL, $window);
		}

		$last = NULL;
		foreach (array_chunk($candidates, 50) as $chunk) {
			$rows = $this->_search_rows($user_id, $chunk);

			foreach ($chunk as $candidate) {
				$last = $candidate;

				if (isset($rows[$candidate->id_post]) && $this->_search_matches($rows[$candidate->id_post], $terms)) {
					$result['posts'][] = $rows[$candidate->id_post];

					if (count($result['posts']) >= $max) {
						break 2;
					}
				}
			}
		}

		// There can be more results if the page is full or all the candidates were checked.
		if ($last !== NULL && (count($result['posts']) >= $max || count($candidates) >= $window)) {
			$result['next'] = $mode . '|' . $last->timestamp . '|' . $last->id_post;
		}

		return $result;
	}

	/**
	 * Search Parse
	 *
	 * Splits the search string in terms. Every term is a list of normalized
	 * words that must appear one after the other.
	 *
	 * @access	private
	 * @param	string
	 * @return	array	'include' and 'exclude' terms, and 'words' of the included terms.
	 */
	private function _search_parse($query)
	{
		$terms = array('include' => array(), 'exclude' => array(), 'words' => array());

		$query = mb_substr(trim((string) $query), 0, 200, 'UTF-8');
		preg_match_all('/(-?)"([^"]*)"|(\S+)/u', $query, $tokens, PREG_SET_ORDER);

		foreach ($tokens as $token) {
			if (isset($token[3]) && $token[3] !== '') {
				$text = $token[3];
				$exclude = (strlen($text) > 1 && $text[0] == '-');
				if ($exclude) {
					$text = substr($text, 1);
				}
				$quoted = FALSE;
			} else {
				$text = $token[2];
				$exclude = ($token[1] == '-');
				$quoted = TRUE;
			}

			$words = preg_split('/[^\p{L}\p{N}]+/u', $this->_search_normalize($text), -1, PREG_SPLIT_NO_EMPTY);
			if (empty($words)) {
				continue;
			}

			// A single word of 4 or more characters matches the beginning of words.
			$prefix = (!$quoted && count($words) == 1 && mb_strlen($words[0], 'UTF-8') >= 4);

			$regex = '/(?<![\p{L}\p{N}])' . implode('[^\p{L}\p{N}]+', array_map(function ($word) {
				return preg_quote($word, '/');
			}, $words)) . ($prefix ? '' : '(?![\p{L}\p{N}])') . '/u';

			if ($exclude) {
				$terms['exclude'][] = $regex;
			} else {
				$terms['include'][] = $regex;
				foreach ($words as $word) {
					$terms['words'][$word] = $word;
				}
			}

			// Too many terms make the queries slow.
			if (count($terms['include']) + count($terms['exclude']) >= 10) {
				break;
			}
		}

		$terms['words'] = array_values($terms['words']);

		return $terms;
	}

	/**
	 * Search Normalize
	 *
	 * Lower case and without accents (the ñ is kept), so it can be compared.
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _search_normalize($text)
	{
		if (function_exists('mb_scrub')) {
			$text = mb_scrub($text, 'UTF-8');
		}

		return strtr(mb_strtolower($text, 'UTF-8'), array(
			'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
			'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
			'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
			'ç' => 'c', 'ý' => 'y', 'ÿ' => 'y'
		));
	}

	/**
	 * Search Fulltext Min Length
	 *
	 * Returns the minimum length of the words of the full-text index on
	 * posts (title, content), or 0 if the index doesn't exist.
	 *
	 * @access	private
	 * @return	integer
	 */
	private function _search_fulltext_min_length()
	{
		static $min_length = NULL;

		if ($min_length !== NULL) {
			return $min_length;
		}

		$min_length = 0;

		try {
			$indexes = $this->conn->query("SHOW INDEX FROM posts WHERE Index_type = 'FULLTEXT'");
			if (!$indexes) {
				return $min_length;
			}

			$columns = array();
			foreach ($indexes->fetchAll(PDO::FETCH_OBJ) as $index) {
				$columns[$index->Key_name][] = strtolower($index->Column_name);
			}

			foreach ($columns as $key_columns) {
				sort($key_columns);
				if ($key_columns != array('content', 'title')) {
					continue;
				}

				$status = $this->conn->query("SHOW TABLE STATUS LIKE 'posts'");
				$engine = $status ? strtolower($status->fetchObject()->Engine) : 'myisam';
				$variable = ($engine == 'innodb') ? 'innodb_ft_min_token_size' : 'ft_min_word_len';

				$value = $this->conn->query("SHOW VARIABLES LIKE '$variable'");
				$value = $value ? $value->fetchObject() : FALSE;
				$min_length = ($value && (int) $value->Value > 0) ? (int) $value->Value : 4;
			}
		} catch (PDOException $err) {
			$min_length = 0;
		}

		return $min_length;
	}

	/**
	 * Search Candidates
	 *
	 * Returns id_post and timestamp of the posts that may match the search,
	 * the newest first. The words are searched with the full-text index
	 * ($match_words) or with LIKE (the rest).
	 *
	 * @access	private
	 * @param	integer
	 * @param	array
	 * @param	array
	 * @param	array	Timestamp and id_post of the last candidate of the previous page.
	 * @param	integer
	 * @return	array
	 */
	private function _search_candidates($user_id, $words, $match_words, $after, $limit)
	{
		$where = array('u.id_user = ?');
		$params = array((int) $user_id);

		if (!empty($match_words)) {
			$where[] = 'MATCH (p.title, p.content) AGAINST (? IN BOOLEAN MODE)';
			$params[] = '+' . implode('* +', $match_words) . '*';
		}

		foreach ($words as $word) {
			if (in_array($word, $match_words)) {
				continue;
			}

			$pattern = '%' . addcslashes($word, '%_\\') . '%';
			$where[] = '(p.title LIKE ? OR p.content LIKE ?)';
			$params[] = $pattern;
			$params[] = $pattern;
		}

		if ($after !== NULL) {
			$where[] = '(p.timestamp < ? OR (p.timestamp = ? AND p.id_post < ?))';
			$params[] = $after[0];
			$params[] = $after[0];
			$params[] = $after[1];
		}

		$sql = '
			SELECT p.id_post, p.timestamp
			FROM posts p
			INNER JOIN user_feed u ON u.id_feed = p.id_feed
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY p.timestamp DESC, p.id_post DESC
			LIMIT ' . (int) $limit;

		// If the query fails (e.g. the full-text index can't be used), there are no
		// candidates, and search_posts() tries again with LIKE only.
		try {
			$dbdata = $this->conn->prepare($sql);
			if (!$dbdata || !$dbdata->execute($params)) {
				return array();
			}
		} catch (PDOException $err) {
			return array();
		}

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Search Rows
	 *
	 * Returns the full data of some candidates, indexed by id_post.
	 *
	 * @access	private
	 * @param	integer
	 * @param	array
	 * @return	array
	 */
	private function _search_rows($user_id, $candidates)
	{
		$ids = array();
		foreach ($candidates as $candidate) {
			$ids[] = (int) $candidate->id_post;
		}

		$sql = '
			SELECT
				p.*, f.site,
				(SELECT id_post FROM readed_posts WHERE id_post = p.id_post AND id_user = ? LIMIT 1) AS readed,
				(SELECT id_post FROM starred_posts WHERE id_post = p.id_post AND id_user = ? LIMIT 1) AS starred
			FROM posts p
			INNER JOIN feeds f ON f.id_feed = p.id_feed
			WHERE p.id_post IN (' . implode(', ', $ids) . ')
		';

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute(array((int) $user_id, (int) $user_id));

		$rows = array();
		foreach ($dbdata->fetchAll(PDO::FETCH_OBJ) as $row) {
			$rows[$row->id_post] = $row;
		}

		return $rows;
	}

	/**
	 * Search Matches
	 *
	 * Checks the terms in the visible text of the post (title and content without HTML).
	 *
	 * @access	private
	 * @param	object
	 * @param	array
	 * @return	bool
	 */
	private function _search_matches($post, $terms)
	{
		$html = $post->title . "\n" . $post->content;
		$html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
		$html = preg_replace('/<[^>]*>/', ' ', $html);
		$text = $this->_search_normalize(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		foreach ($terms['include'] as $regex) {
			if (!preg_match($regex, $text)) {
				return FALSE;
			}
		}

		foreach ($terms['exclude'] as $regex) {
			if (preg_match($regex, $text)) {
				return FALSE;
			}
		}

		return TRUE;
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
