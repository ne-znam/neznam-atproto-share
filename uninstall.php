<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://nezn.am
 * @since      1.0.0
 *
 * @package    Neznam_Atproto_Share
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$plugin_name = 'neznam-atproto-share';

delete_option( $plugin_name . '-url' );
delete_option( $plugin_name . '-handle' );
delete_option( $plugin_name . '-secret' );
delete_option( $plugin_name . '-default' );
delete_option( $plugin_name . '-access-token' );
delete_option( $plugin_name . '-refresh-token' );
delete_option( $plugin_name . '-use-cron' );
delete_option( $plugin_name . '-debug-level' );
delete_option( $plugin_name . '-post-format' );
delete_option( $plugin_name . '-comment-override' );
delete_option( $plugin_name . '-comment-disable' );
