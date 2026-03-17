<?php
/**
 * Plugin Name: Simple 3D Showcase
 * Plugin URI: https://example.com
 * Description: Production-ready 3D showcase viewer plugin powered by model-viewer.
 * Version: 2.0.0
 * Author: Davtyan Alik
 * License: GPL-2.0-or-later
 * Text Domain: simple-3d-showcase
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'S3DS_VERSION', '2.0.0' );
define( 'S3DS_PLUGIN_FILE', __FILE__ );
define( 'S3DS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'S3DS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'S3DS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'S3DS_OPTION_KEY', 's3ds_settings' );

require_once S3DS_PLUGIN_PATH . 'includes/Autoloader.php';

$autoloader = new S3DS\Autoloader( 'S3DS\\', S3DS_PLUGIN_PATH . 'includes/' );
$autoloader->register();

register_activation_hook( S3DS_PLUGIN_FILE, array( 'S3DS\\Activator', 'activate' ) );
register_deactivation_hook( S3DS_PLUGIN_FILE, array( 'S3DS\\Deactivator', 'deactivate' ) );

function s3ds_plugin() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new S3DS\Plugin();
	}

	return $plugin;
}

s3ds_plugin()->boot();
