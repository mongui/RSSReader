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
			return $this->updater->update_feed($feed_id);
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
	 */
	public function all()
	{
		echo 'Updating... ';

		// Set time and memory limits higher (if possible).
		error_reporting(E_ERROR);
		set_time_limit(300);
		ini_set('memory_limit', '256M');

		$seconds_ago = $this->config->get('minutes_between_updates') * 60;
		$max_feeds = $this->config->get('max_feeds_per_update');

		$feeds = $this->updater->feeds_not_updated($seconds_ago, $max_feeds);

		foreach ($feeds as $feed) {
			echo $feed->id_feed . ', ';
			$updated = $this->updater->update_feed($feed->id_feed);

			// If the feed has been successfully updated, activate it.
			if ($updated) {
				$this->updater->change_feed_last_update($feed->id_feed);
				$this->updater->active_feed($feed->id_feed, 1);
			}
		}
	}
}
