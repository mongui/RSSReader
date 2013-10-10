<?php if ( !defined('MVCious')) exit('No direct script access allowed');

class Update extends ControllerBase
{
	function __construct()
	{
		parent::__construct();
		//http://simplepie.org/wiki/reference/start
		$this->load->model('connections');

		$this->connections->load_config();
		date_default_timezone_set( $this->config->get('timezone') );
	}

	public function feed( $feed_id = FALSE )
	{
		error_reporting(E_ERROR);
		if ( $feed_id )
			return $this->connections->update_feed($feed_id);
	}

	public function all()
	{
		echo 'Updating... ';

		error_reporting(E_ERROR);
		set_time_limit(300);
		ini_set('memory_limit', '256M');

		$seconds_ago = $this->config->get('minutes_between_updates') * 60;
		$max_feeds = $this->config->get('max_feeds_per_update');

		$feeds = $this->connections->feeds_not_uptated( $seconds_ago, $max_feeds );

		foreach ( $feeds as $feed )
		{
			echo $feed->id_feed . ', ';
			$updated = $this->connections->update_feed($feed->id_feed);

			if ( $updated ) {
				$this->connections->change_feed_last_update ($feed->id_feed);
				$this->connections->active_feed($feed->id_feed, 1);
				$actualizados[] = $feed->id_feed;
			}
		}
	}
}