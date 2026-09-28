<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Update System Controller.
 *
 * @package		RSSReader
 * @subpackage	Controllers
 * @author		Gontzal Goikoetxea
 * @link		https://github.com/mongui/RSSReader
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class Update extends ControllerBase
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		//http://simplepie.org/wiki/reference/start
		$this->load->model('updater');
		$this->load->model('configuration');

		// From the command line (cron) anybody can update; by URL, only the admin.
		if (PHP_SAPI !== 'cli') {
			if (!isset($_SESSION)) {
				ini_set('session.gc_maxlifetime', $this->config->get('session_timeout'));
				session_set_cookie_params($this->config->get('session_timeout'));
				session_start();
			}

			if (!isset($_SESSION['id']) || $this->config->get('admin') != $_SESSION['id']) {
				header('HTTP/1.1 403 Forbidden');
				exit('Only the admin can update the feeds.');
			}
		}
	}

	/**
	 * Feed
	 *
	 * Updates a specific feed.
	 */
	public function feed($feed_id = FALSE)
	{
		error_reporting(E_ERROR);
		if ($feed_id) {
			$this->_report_fatal_errors();
			$updated = $this->_update_one($feed_id);
			echo $updated ? 'OK' : 'ERROR';
			return $updated;
		}
	}

	/**
	 * All
	 *
	 * Updates all feeds ordered in groups.
	 * For this to work, a cronjob is needed to run every 5 minutes.
	 *
	 * Cronjob example:
	 * * /5 * * * * php /home/user/public_html/index.php update all
	 *
	 * By URL, update/all?forced=true updates every active feed, no matter
	 * when it was updated for the last time.
	 */
	public function all()
	{
		if (PHP_SAPI !== 'cli') {
			$this->_prepare_streaming();
		}

		if (PHP_SAPI !== 'cli') {
			echo '<pre>';
		}
		echo 'Starting update.' . PHP_EOL;
		$this->_flush();

		// Set memory limit higher (if possible).
		// The time limit is set for every feed in _update_one().
		error_reporting(E_ERROR);
		ini_set('memory_limit', '256M');
		$this->_report_fatal_errors();

		if ($this->_is_forced()) {
			echo 'Forced: all the active feeds.' . PHP_EOL;
			$feeds = $this->updater->active_feeds();
		} else {
			$seconds_ago = $this->config->get('minutes_between_updates') * 60;
			$max_feeds = $this->config->get('max_feeds_per_update');

			$feeds = $this->updater->feeds_not_updated($seconds_ago, $max_feeds);
		}

		if (sizeof($feeds) > 0) {
			foreach ($feeds as $feed) {
				echo $feed->id_feed . ' - ' . $feed->name . '... ';
				$this->_flush();

				$updated = $this->_update_one($feed->id_feed);

				echo ($updated ? 'OK' : 'ERROR') . PHP_EOL;
				$this->_flush();
			}
		} else {
			echo 'Nothing to update.' . PHP_EOL;
		}
		echo 'Update finished.' . PHP_EOL;
		if (PHP_SAPI !== 'cli') {
			echo '</pre>';
		}
	}

	/**
	 * Is Forced
	 *
	 * Returns TRUE if the URL has forced=true. It's read from the original URL
	 * because the rewrite rule of .htaccess doesn't keep the query string.
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _is_forced()
	{
		if (PHP_SAPI === 'cli' || !isset($_SERVER['REQUEST_URI'])) {
			return FALSE;
		}

		parse_str((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);

		return isset($query['forced']) && $query['forced'] === 'true';
	}

	/**
	 * Prepare Streaming
	 *
	 * Disables every buffer and compression between PHP and the browser,
	 * so the progress is shown live and the gateway doesn't time out (504).
	 *
	 * @access	private
	 * @return	void
	 */
	private function _prepare_streaming()
	{
		// If the gateway closes the connection anyway, the update goes on.
		ignore_user_abort(TRUE);

		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-cache');
		// Nginx: don't buffer this response.
		header('X-Accel-Buffering: no');

		ini_set('zlib.output_compression', '0');
		if (function_exists('apache_setenv')) {
			apache_setenv('no-gzip', '1');
		}

		while (ob_get_level() > 0) {
			ob_end_flush();
		}
		ob_implicit_flush(TRUE);

		// Browsers don't render anything until they receive some data.
		echo str_repeat(' ', 1024) . PHP_EOL;
	}

	/**
	 * Flush
	 *
	 * Sends the output generated so far to the client.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _flush()
	{
		if (ob_get_level() > 0) {
			ob_flush();
		}
		flush();
	}

	/**
	 * Update One
	 *
	 * Updates a single feed isolating its failures, so a slow or
	 * broken feed can't stop the rest of the update.
	 *
	 * @access	private
	 * @param	integer
	 * @return	bool
	 */
	private function _update_one($feed_id)
	{
		// Resets the time counter for every feed.
		set_time_limit(60);

		// Refreshed before downloading the feed, so if it hangs or kills
		// the script, the next update skips it instead of getting stuck on it.
		$this->updater->change_feed_last_update($feed_id);

		try {
			$updated = $this->updater->update_feed($feed_id);
		} catch (Throwable $e) {
			echo $e->getMessage() . ' ';
			$updated = FALSE;
		}

		// If the feed has been successfully updated, activate it.
		if ($updated) {
			$this->updater->active_feed($feed_id, 1);
		}

		gc_collect_cycles();

		return (bool) $updated;
	}

	/**
	 * Report Fatal Errors
	 *
	 * Prints the fatal error (timeout, memory...) that killed the script, if any.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _report_fatal_errors()
	{
		register_shutdown_function(function () {
			$error = error_get_last();
			if ($error && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
				echo PHP_EOL . 'FATAL: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL;
			}
		});
	}
}
