<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/includes
 * @author     Marko Banušić <mbanusic@gmail.com>
 */
class Neznam_Atproto_Share_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'neznam-atproto-share_cron' );
		wp_unschedule_event( $timestamp, 'neznam-atproto-share_cron' );
	}
}
