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

/**
 * Fired during plugin deactivation.
 *
 * @link       https://nezn.am
 * @since      1.0.0
 *
 * @package    Neznam_Atproto_Share
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
		wp_clear_scheduled_hook( 'neznam-atproto-share_cron' );
	}
}
