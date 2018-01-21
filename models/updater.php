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
			SELECT *
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
			SELECT *
			FROM feeds
			WHERE last_update < FROM_UNIXTIME($seconds)
			AND active = 1
			$limit
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
		$this->load->library('simplepie');

		$this->simplepie->set_feed_url($url);

		if ($fast) {
			$this->simplepie->set_stupidly_fast(TRUE);
		}

		error_reporting(E_ERROR);

		// This allows Youtube videos.
		$strip_htmltags = $this->simplepie->strip_htmltags;
		unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
		$this->simplepie->strip_htmltags($strip_htmltags);

		$this->simplepie->set_output_encoding('UTF-8');
		$this->simplepie->init();
		$this->simplepie->handle_content_type();

		// If RSS is malformed.
		if ($this->simplepie->error()) {
			unset ($this->simplepie);
			$this->simplepie = new SimplePie();
			$file = fopen($url, 'r');

			if (!isset($http_response_header)) {
				$http_response_header = get_headers($url);
			}

			$return_code = @explode(' ', $http_response_header[0]);
			$return_code = (int)$return_code[1];

			if ($return_code >= 300 && $return_code <= 399) {
				foreach ($http_response_header as $response) {
					if (strpos($response, 'Location: ') !== FALSE) {
						$response = str_replace('Location: ', '', $response);
						$newurldata = $this->get_feed_by_url($response);
						if (is_object($newurldata)) {
							$this->change_feed_url($url, $response);
							return $newurldata;
						} else {
							return FALSE;
						}
					}
				}
			}
			elseif ($return_code >= 400 && $return_code < 500) {
				return FALSE;
			}

			$content = stream_get_contents($file);

			// Adjust the downloaded posts characters.
			$patterns = array('&aacute;', '&eacute;', '&iacute;', '&oacute;', '&uacute;', '&Aacute;', '&Eacute;', '&Iacute;', '&Ooacute;', '&Uacute;', '&ntilde;', '&Ntilde;');
			$replacements = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');

			$content = str_replace($patterns, $replacements, $content);

			$content = preg_replace('/(<script.+?>)(<\/script>)/i', '', $content);
			$content = preg_replace('/<script.+?\/>/i', '', $content);

			$this->simplepie->set_raw_data($content);

			// This allows Youtube videos.
			$strip_htmltags = $this->simplepie->strip_htmltags;
			unset($strip_htmltags[array_search('iframe', $strip_htmltags)]);
			$this->simplepie->set_output_encoding('UTF-8');
			$this->simplepie->init();
			$this->simplepie->handle_content_type();
		}

		return $this->simplepie;
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
				SELECT *
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
			if ($enclosure = $item->get_enclosure()) {
				$multimedia_size = round($enclosure->length/1024/1024, 2);
				if ($multimedia_size > 0) {
					$media_content = '<div style="border:1px solid #aaa;padding:1em;margin:1em auto;background:#eee;">
						<strong>Multimedia:</strong> ' . $enclosure->description . '<br />
						<a href="' . $enclosure->link . '">' . $enclosure->link . '</a> (' . ucfirst($enclosure->type) . ' format, ' . round($enclosure->length/1024/1024, 2) . ' MB)
					</div>';
				}
			}

			$data[] = array(
				'id_feed'			=> $feed_id,
				'timestamp'			=> date('Y-m-d H:i:s', strtotime($item->get_date())),
				'author'			=> ( isset($authors) && count($authors) > 0 ) ? implode(', ', $authors) : '',
				'url'				=> str_replace('\'', '', $item->get_link()),
				'title'				=> $item->get_title(),
				'content'			=> ((count($item->get_content()) > 0 ) ? $item->get_content() : '<i>No content.</i>') . (($item->get_enclosure()->link) ? $media_content : '')
			);
			unset($authors);


			if ($item->get_link()) {
				$or_where[] = "url = '" . str_replace('\'', '', $item->get_link()) . "'";
			}
		}

		if (!empty($or_where)) {
			$or_where = 'WHERE ' . implode(" OR \n", $or_where);
		} else {
			$or_where = '';
		}

		// Filter posts by url.
		$sql = "
			SELECT url
			FROM posts
			$or_where
		";

		$rtrn = $this->conn->prepare($sql);
		$rtrn->execute();
		foreach ($rtrn->fetchAll(PDO::FETCH_OBJ) as $result) {
			$rslt[] = $result->url;
		}

		$rslt = array_filter($rslt);
		if (!empty($data)) {

			if (!empty($rslt)) {
				foreach ($data as $key => $item) {
					if (isset($rslt) && in_array($item['url'], $rslt)) {
						unset($data[$key]);
					}
				}
			}

			// Filter posts by timestamp.
			$sql = "
				SELECT timestamp
				FROM posts
				WHERE id_feed = " . $feed_id . "
				ORDER BY timestamp DESC
				LIMIT 1
			";

			$rtrn = $this->conn->prepare($sql);
			$rtrn->execute();

			$newest = strtotime($rtrn->fetchObject()->timestamp);

			foreach ($data as $key => $item) {
				if (isset($newest) && strtotime($item['timestamp']) <= $newest) {
					unset($data[$key]);
				}
			}
		}

		if (!empty($data)) {
			try {
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				$sql = '
					INSERT INTO posts
					(id_feed, timestamp, author, url, title, content)
					VALUES
				';
				$total = array_fill(0, count($data), '(?, ?, ?, ?, ?, ?)');
				$sql .= implode(', ', $total);

				$dbdata = $this->conn->prepare($sql);
				$i = 1;

				foreach($data as $item) {
					$dbdata->bindParam($i++, $item['id_feed']	);
					$dbdata->bindParam($i++, $item['timestamp']	);
					$dbdata->bindParam($i++, $item['author']	);
					$dbdata->bindParam($i++, $item['url']		);
					$dbdata->bindParam($i++, $item['title']		);
					$dbdata->bindParam($i++, $item['content']	);
				}

				return $dbdata->execute();
			} catch (PDOException $err) {
				echo '\n\n\nError: ' . $err . '\n\n\n';
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
