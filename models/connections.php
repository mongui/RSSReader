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
				}
				else {
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
	 * - If feed param is 'search' and there is a search string.
	 * - If there is just a feed ID and user ID.
	 * - If there is just a feed ID.
	 * 
	 * @access	public
	 * @param	integer/string
	 * @param	integer
	 * @param	integer
	 * @param	string
	 * @return	object
	 */
	function posts_from_feed($feed_id, $next = 0, $user_id = NULL, $search = NULL)
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
		} elseif ($user_id && $feed_id == 'search' && isset($search)) {
			$chunks = explode(" ", $search);
			if (count($chunks) == 1) {
				$where = "(p.content LIKE '%$search%' OR p.title LIKE '%$search%')";
			} else {
				$where = "MATCH (p.title, p.content) AGAINST ('$search')";
			}

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
						$where
					ORDER BY timestamp desc
					LIMIT $next, $max
			";
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

		if (!empty($data)) {
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
			}
			else {
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
}

/*
truncate feeds;
truncate folders;
truncate posts;
truncate readed_posts;
truncate starred_posts;
truncate user_feed;
*/
