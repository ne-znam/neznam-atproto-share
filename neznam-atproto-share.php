<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://nezn.am
 * @since             1.0.0
 * @package           Neznam_Atproto_Share
 *
 * @wordpress-plugin
 * Plugin Name:       Neznam Atproto Share
 * Plugin URI:        https://nezn.am/plugins/neznam-atproto-share
 * Description:       Automatically share to Authenticated Transfer Protocol networks like BlueSky
 * Version:           1.3.0
 * Author:            Marko Banušić
 * Author URI:        https://nezn.am
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       neznam-atproto-share
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NEZNAM_ATPROTO_SHARE_VERSION', '1.2.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-neznam-atproto-share-activator.php
 */
function neznam_atproto_share_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-neznam-atproto-share-activator.php';
	Neznam_Atproto_Share_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-neznam-atproto-share-deactivator.php
 */
function neznam_atproto_share_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-neznam-atproto-share-deactivator.php';
	Neznam_Atproto_Share_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'neznam_atproto_share_activate' );
register_deactivation_hook( __FILE__, 'neznam_atproto_share_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-neznam-atproto-share.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function neznam_atproto_share_run() {

	$plugin = new Neznam_Atproto_Share();
	$plugin->run();
}

neznam_atproto_share_run();
