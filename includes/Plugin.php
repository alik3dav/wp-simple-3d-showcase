<?php

namespace S3DS;

use S3DS\Admin\AdminMenu;
use S3DS\Admin\MetaBoxes;
use S3DS\Admin\SettingsPage;
use S3DS\Admin\SettingsRegistry;
use S3DS\API\RestController;
use S3DS\Domain\ShowcaseRepository;
use S3DS\Frontend\AssetLoader;
use S3DS\Frontend\Shortcode;
use S3DS\Frontend\ViewerRenderer;
use S3DS\PostTypes\ShowcasePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	private $repository;

	public function __construct() {
		$this->repository = new ShowcaseRepository();
	}

	public function boot() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'upload_mimes', array( $this, 'allow_3d_uploads' ) );

		$post_type = new ShowcasePostType();
		$post_type->register();

		$asset_loader = new AssetLoader();
		$renderer     = new ViewerRenderer( $asset_loader );
		$shortcode    = new Shortcode( $this->repository, $renderer, $asset_loader );
		$shortcode->register();

		$settings_registry = new SettingsRegistry();
		$settings_page     = new SettingsPage( $settings_registry );
		$admin_menu        = new AdminMenu( $settings_page );
		$meta_boxes        = new MetaBoxes();
		$rest_controller   = new RestController( $this->repository );

		$settings_registry->register();
		$admin_menu->register();
		$meta_boxes->register();
		$asset_loader->register_admin();
		$rest_controller->register();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'simple-3d-showcase', false, dirname( S3DS_PLUGIN_BASENAME ) . '/languages' );
	}

	public function allow_3d_uploads( $mimes ) {
		$mimes['glb']  = 'model/gltf-binary';
		$mimes['gltf'] = 'model/gltf+json';
		return $mimes;
	}
}
