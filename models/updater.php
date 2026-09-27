<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Feed Updating system.
 *
 * @package		RSSReader
 * @subpackage	Models
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Updater extends ModelBase
{
	/**
	 * Database connection object.
	 *
	 * @var		object
	 * @access	private
	 */
	private $conn;

	/**
	 * HTTP code of the last feed downloaded again with cURL (NULL if it wasn't needed).
	 *
	 * @var		integer
	 * @access	private
	 */
	private $last_http_code = NULL;

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
	 * Feed In Database
	 *
	 * Checks if a feed URL exists in the database.
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	function feed_in_database($feed_url)
	{
		$sql = "
			SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active
			FROM feeds
			WHERE url = '$feed_url'
			LIMIT 1
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		if ($dbdata->rowCount() > 0) {
			return $dbdata->fetchObject();
		} else {
			return NULL;
		}
	}

	/**
	 * Change Feed Last Update
	 *
	 * Refreshes the last_update column of a feed.
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	function change_feed_last_update($feed_id)
	{
		$sql = "
			UPDATE feeds
			SET last_update = NOW()
			WHERE id_feed = $feed_id
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();
	}

	/**
	 * Feeds Not Updated
	 *
	 * Returns the list of feeds not updated for a period.
	 *
	 * @access	public
	 * @param	integer
	 * @param	integer
	 * @return	object
	 */
	function feeds_not_updated($seconds, $max_feeds = 0)
	{
		$seconds = time() - $seconds;

		$limit = '';
		if ($max_feeds > 0) {
			$limit = 'LIMIT ' . $max_feeds;
		}

		$sql = "
			SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active
			FROM feeds
			WHERE last_update < FROM_UNIXTIME($seconds)
			AND active = 1
			ORDER BY last_update ASC
			$limit
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Active Feeds
	 *
	 * Returns every active feed, the oldest updated first.
	 *
	 * @access	public
	 * @return	object
	 */
	function active_feeds()
	{
		$sql = "
			SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active
			FROM feeds
			WHERE active = 1
			ORDER BY last_update ASC
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Insert Feed
	 *
	 * Adds a feed to the Feeds table and to the User_feed table.
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	integer
	 */
	function insert_feed($feed_url, $user_id = NULL)
	{
		// Is the feed already in the database?
		$feed = $this->feed_in_database($feed_url);

		if (!isset($feed->id_feed)) {
			$feed_data = $this->get_feed_by_url($feed_url, TRUE);

			if (isset($feed_data)) {
				// Adds to the feeds table.
				$dbname				= preg_replace('/<[^>]*>/', '', $feed_data->get_title());
				//$dbfavicon			= 'http://g.etfv.co/' . urlencode($feed_data->get_link());
				$dbfavicon			= 'http://www.google.com/s2/favicons?domain_url=' . urlencode($feed_data->get_link());
				$dbsite				= $feed_data->get_link();
				$dburl				= $feed_url;

				$sql = "
					INSERT INTO feeds (name, favicon, site, url)
					VALUES ('$dbname', '$dbfavicon', '$dbsite', '$dburl')
				";

				$dbdata = $this->conn->prepare($sql);
				$dbdata->execute();

				$feed = $this->feed_in_database($feed_url);
			} else {
				return 0;
			}
		}

		return isset($feed->id_feed) ? $feed->id_feed : 0;
	}

	/**
	 * Get Feed By URL
	 *
	 * Downloads the feed from its URL and saves the new posts
	 * the other data into the database.
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	object
	 */
	function get_feed_by_url($url, $fast = FALSE)
	{
		error_reporting(E_ALL);
		$this->last_http_code = NULL;

		// Only loads the class. A new instance is used on every call so no
		// state (or memory) is shared between feeds.
		$this->load->library('simplepie');
		$feed = new SimplePie();

		$feed->set_feed_url($url);
		$feed->set_timeout(15);
		$feed->force_feed(true);

		if ($fast) {
			$feed->set_stupidly_fast(TRUE);
		}

		// This allows Youtube videos.
		$strip_htmltags = $feed->strip_htmltags;
		unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
		$feed->strip_htmltags($strip_htmltags);

		$feed->set_output_encoding('UTF-8');
		$feed->init();
		$feed->handle_content_type();

		// If RSS is malformed.
		if ($feed->error()) {
			$feed->__destruct();
			$feed = new SimplePie();
			$feed->force_feed(true);

			// Some servers block feed readers and others block browsers (e.g. WordPress.com
			// answers 403 to an old browser user agent), so both are tried.
			$user_agents = array(
				'RSSReader (+https://github.com/mongui/RSSReader)',
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0'
			);

			foreach ($user_agents as $user_agent) {
				$c = curl_init($url);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($c, CURLOPT_TIMEOUT, 20);
				curl_setopt($c, CURLOPT_USERAGENT, $user_agent);

				$content = curl_exec($c);
				$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
				$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

				if ($content === FALSE) {
					$feed->error = 'cURL error: ' . curl_error($c);
					curl_close($c);
					echo $feed->error . ' ';
					return $feed;
				}
				curl_close($c);

				if ($http_code >= 200 && $http_code < 300) {
					break;
				}
			}

			$this->last_http_code = $http_code;

			// The server didn't send the feed (e.g. a 403 page or an anti-bot check).
			if ($http_code < 200 || $http_code >= 300) {
				$feed->error = 'HTTP ' . $http_code . ' (' . $content_type . '): the server didn\'t send the feed.';
				echo $feed->error . ' ';
				return $feed;
			}

			// Adjust the downloaded posts characters.
			$patterns = array('&aacute;', '&eacute;', '&iacute;', '&oacute;', '&uacute;', '&Aacute;', '&Eacute;', '&Iacute;', '&Ooacute;', '&Uacute;', '&ntilde;', '&Ntilde;');
			$replacements = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');

			$content = str_replace($patterns, $replacements, $content);

			$content = preg_replace('/(<script.+?>)(<\/script>)/i', '', $content);
			$content = preg_replace('/<script.+?\/>/i', '', $content);

			$feed->set_raw_data($content);

			// This allows Youtube videos.
			$strip_htmltags = $feed->strip_htmltags;
			unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
			$feed->strip_htmltags($strip_htmltags);
			$feed->set_output_encoding('UTF-8');
			$feed->init();
			$feed->handle_content_type();
		}

		if ($feed->error()) {
			echo $feed->error() . ' ';
		}

		return $feed;
	}

	/**
	 * Feed Data From ID
	 *
	 * Gets the feed data stored in the database.
	 *
	 * @access	public
	 * @param	integer
	 * @return	object
	 */
	function feed_data_from_id($feed_id)
	{
		$sql = "
				SELECT id_feed, site, url, name, last_update, favicon, export_set(active, '1', '0', '', 1) AS active
				FROM feeds
				WHERE id_feed = $feed_id
				LIMIT 1
			";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchObject();
	}

	/**
	 * Posts From Feed
	 *
	 * Returns the last posts from a feed.
	 *
	 * @access	public
	 * @param	integer
	 * @return	object
	 */
	function posts_from_feed($feed_id)
	{
		$sql = "
				SELECT *
				FROM posts
				WHERE
					posts.id_feed = $feed_id
				ORDER BY timestamp desc
				LIMIT 0, 10
		";

		$dbdata = $this->conn->prepare($sql);
		$dbdata->execute();

		return $dbdata->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Update Feed
	 *
	 * Gets the downloaded posts object, checks which of
	 * them aren't in the database and adds them.
	 *
	 * @access	public
	 * @param	integer
	 * @return	bool
	 */
	function update_feed($feed_id)
	{
		// Get the needed data to download the feed.
		$last_posts = $this->posts_from_feed($feed_id);

		if ($last_posts) {
			$lp_timestamp	= strtotime($last_posts[0]->timestamp);
			$lp_title		= $last_posts[0]->title;
		} else {
			$lp_timestamp	= 0;
			$lp_title		= '';
		}

		$feed = $this->feed_data_from_id($feed_id);

		$feed_data = $this->get_feed_by_url($feed->url);
		if (!isset($feed_data) || $feed_data == FALSE) {
			$this->active_feed($feed_id, 0);
			return FALSE;
		}

		// The feed doesn't exist any more: it's deactivated, so it isn't updated again.
		// It can be activated again from the global feed list of the preferences.
		if ($this->last_http_code == 404) {
			$feed_data->__destruct();
			$this->active_feed($feed_id, 0);
			echo 'Feed deactivated. ';
			return FALSE;
		}

		// Check if the parsed feed has a list of items.
		if (sizeof($feed_data->get_items()) == 0) {
			$feed_data->__destruct();
			unset($feed_data);
			return FALSE;
		}

		// Prepare the downloaded posts and add them to the database.
		foreach ($feed_data->get_items() as $item) {
			if ($item->get_authors()) {
				foreach ($item->get_authors() as $auth) {
					if ($auth->get_name()) {
						$authors[] = $auth->get_name();
					} else if ($auth->get_email()) {
						$authors[] = $auth->get_email();
					}
				}

				$authors = array_filter($authors);
			}

			// Multimedia files attached.
			$media_content = '';
			if ($enclosure = $item->get_enclosure()) {
				if ($enclosure->description != '' || $enclosure->length != null) {
					$enclosure->description = nl2br($enclosure->description);

					$media_content = '<div style="border:1px solid #aaa;padding:1em;margin:1em auto;background:#eee;">
						<strong>Multimedia:</strong> ' . $enclosure->description . '<br />
						<a href="' . $enclosure->link . '">' . $enclosure->link . '</a> (' . ucfirst($enclosure->type) . ' format, ' . round($enclosure->length/1024/1024, 2) . ' MB)
					</div>';
				}
			}

			$url = str_replace('\'', '', $item->get_link());

			$data[] = array(
				'id_feed'			=> $feed_id,
				'timestamp'			=> date('Y-m-d H:i:s', strtotime($item->get_date())),
				'author'			=> ( isset($authors) && count($authors) > 0 ) ? implode(', ', $authors) : '',
				// NULL instead of '', so posts without link don't collide in the unique url key.
				'url'				=> ($url != '') ? $url : NULL,
				'title'				=> $item->get_title(),
				'content'			=> (($item->get_content() != '') ? $item->get_content() : '<i>No content.</i>') . $media_content
			);
			unset($authors);
		}

		// SimplePie has circular references, so it must be freed manually.
		$feed_data->__destruct();
		unset($feed_data, $item);

		// Posts already in the database: by url (unique in the whole table)
		// or, for posts without url, by timestamp within this feed.
		$urls = array();
		$timestamps = array();
		foreach ($data as $item) {
			if ($item['url'] !== NULL) {
				$urls[] = $item['url'];
			} else {
				$timestamps[] = $item['timestamp'];
			}
		}

		$existing_urls = array();
		if (!empty($urls)) {
			$sql = 'SELECT url FROM posts WHERE url IN (' . implode(', ', array_fill(0, count($urls), '?')) . ')';
			$rtrn = $this->conn->prepare($sql);
			$rtrn->execute($urls);
			// MySQL compares urls case insensitively.
			$existing_urls = array_map('strtolower', $rtrn->fetchAll(PDO::FETCH_COLUMN));
		}

		$existing_timestamps = array();
		if (!empty($timestamps)) {
			$sql = 'SELECT timestamp FROM posts WHERE id_feed = ? AND timestamp IN (' . implode(', ', array_fill(0, count($timestamps), '?')) . ')';
			$rtrn = $this->conn->prepare($sql);
			$rtrn->execute(array_merge(array($feed_id), $timestamps));
			$existing_timestamps = $rtrn->fetchAll(PDO::FETCH_COLUMN);
		}

		// Removes the posts already stored and the ones repeated in the feed itself.
		foreach ($data as $key => $item) {
			if ($item['url'] !== NULL) {
				$exists = in_array(strtolower($item['url']), $existing_urls);
				$existing_urls[] = strtolower($item['url']);
			} else {
				$exists = in_array($item['timestamp'], $existing_timestamps);
				$existing_timestamps[] = $item['timestamp'];
			}

			if ($exists) {
				unset($data[$key]);
			}
		}

		if (!empty($data)) {
			try {
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				// IGNORE: a duplicated post is skipped instead of discarding the whole batch.
				$sql = '
					INSERT IGNORE INTO posts
					(id_feed, timestamp, author, url, title, content)
					VALUES
				';
				$total = array_fill(0, count($data), '(?, ?, ?, ?, ?, ?)');
				$sql .= implode(', ', $total);

				$dbdata = $this->conn->prepare($sql);
				$i = 1;

				foreach($data as $item) {
					$dbdata->bindValue($i++, $item['id_feed']	);
					$dbdata->bindValue($i++, $item['timestamp']	);
					$dbdata->bindValue($i++, $item['author']	);
					$dbdata->bindValue($i++, $item['url']		);
					$dbdata->bindValue($i++, $item['title']		);
					$dbdata->bindValue($i++, $item['content']	);
				}

				return $dbdata->execute();
			} catch (PDOException $err) {
				echo $err->getMessage() . ' ';
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Change Feed URL
	 *
	 * Nothing to explain here.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function change_feed_url($oldurl, $newurl = NULL)
	{
		if (!isset($oldurl) || !isset($newurl)) {
			return FALSE;
		}

		try {
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$sql = "
				UPDATE feeds
				SET url = '$newurl'
				WHERE url LIKE '$oldurl'
			";

			$dbdata = $this->conn->prepare($sql);
			return $dbdata->execute();
		} catch (PDOException $err) {
			echo '\n\n\nError: ' . $err . '\n\n\n';
		}
	}

	/**
	 * Active Feed.
	 *
	 * Allows a feed to be updatable or not.
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	bool
	 */
	function active_feed($feed, $active = TRUE)
	{
		if (!isset($feed)) {
			return FALSE;
		}

		if (!isset($active) || $active == FALSE) {
			$active = 0;
		} elseif ($active == TRUE || $active == 1) {
			$active = 1;
		} else {
			$active = 0;
		}

		try {
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$sql = '
				UPDATE feeds
				SET active = ' . $active . '
				WHERE id_feed = ' . $feed . '
			';

			$dbdata = $this->conn->prepare($sql);
			return $dbdata->execute();
		}
		catch (PDOException $err) {
			echo '\n\n\nError: ' . $err . '\n\n\n';
		}
	}
}
