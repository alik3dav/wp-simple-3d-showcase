<?php
/**
 * Plugin bootstrap.
 *
 * @package WP3DS
 */

namespace WP3DS;

use WP3DS\Admin\AdminAssets;
use WP3DS\Admin\MetaBoxes;
use WP3DS\Admin\SettingsPage;
use WP3DS\Frontend\FrontendAssets;
use WP3DS\Frontend\Shortcode;
use WP3DS\PostTypes\ShowcasePostType;
use WP3DS\REST\Routes;

defined( 'ABSPATH' ) || exit;

class Plugin {
	public function boot(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( new ShowcasePostType(), 'register' ) );
		add_action( 'init', array( new Shortcode(), 'register' ) );
		add_action( 'add_meta_boxes', array( new MetaBoxes(), 'register' ) );
		add_action( 'save_post_wp3ds_item', array( new MetaBoxes(), 'save' ) );

		$settings_page = new SettingsPage();
		$settings_page->hooks();

		( new AdminAssets() )->hooks();
		( new FrontendAssets() )->hooks();

		add_action( 'rest_api_init', array( new Routes(), 'register' ) );
	}

	public static function activate(): void {
		( new ShowcasePostType() )->register();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'wp-3d-showcase', false, dirname( plugin_basename( WP3DS_FILE ) ) . '/languages' );
	}
}
