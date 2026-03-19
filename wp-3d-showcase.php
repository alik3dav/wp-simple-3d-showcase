<?php
/**
 * Plugin Name: WP 3D Showcase
 * Plugin URI: https://example.com
 * Description: Showcase GLB 3D models with zoom, rotate, pan, fullscreen, and explode view.
 * Version: 1.0.1
 * Author: Palaplast
 * Text Domain: wp-3d-showcase
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('WP3DS_VERSION', '1.0.1');
define('WP3DS_FILE', __FILE__);
define('WP3DS_PATH', plugin_dir_path(__FILE__));
define('WP3DS_URL', plugin_dir_url(__FILE__));

require_once WP3DS_PATH . 'includes/Autoloader.php';

\WP3DS\Autoloader::register();

function wp3ds_boot_plugin(): void {
    $plugin = new \WP3DS\Plugin();
    $plugin->boot();
}
wp3ds_boot_plugin();