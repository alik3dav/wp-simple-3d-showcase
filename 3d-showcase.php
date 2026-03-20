<?php
/**
 * Plugin Name: 3D Model Viewer
 * Description: Showcase self-hosted GLB 3D models with orbit controls, fullscreen mode, explode view, and part inspection.
 * Version: 1.1.0
 * Author: Palaplast
 * Text Domain: three-d-showcase
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WP3DS
 */

defined( 'ABSPATH' ) || exit;

define( 'WP3DS_VERSION', '1.1.0' );
define( 'WP3DS_FILE', __FILE__ );
define( 'WP3DS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP3DS_URL', plugin_dir_url( __FILE__ ) );

require_once WP3DS_PATH . 'includes/Autoloader.php';

WP3DS\Autoloader::register();

register_activation_hook( WP3DS_FILE, array( 'WP3DS\\Plugin', 'activate' ) );
register_deactivation_hook( WP3DS_FILE, array( 'WP3DS\\Plugin', 'deactivate' ) );

function wp3ds_boot_plugin(): void {
	$plugin = new WP3DS\Plugin();
	$plugin->boot();
}

wp3ds_boot_plugin();
