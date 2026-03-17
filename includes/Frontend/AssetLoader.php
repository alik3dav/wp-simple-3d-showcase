<?php

namespace S3DS\Frontend;

use S3DS\PostTypes\ShowcasePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AssetLoader {
	private $frontend_needed = false;

	public function register_admin() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function mark_frontend_needed() {
		if ( $this->frontend_needed ) {
			return;
		}

		$this->frontend_needed = true;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	public function enqueue_frontend_assets() {
		$settings = \S3DS\Helpers::get_settings();
		$local_model_viewer_path = S3DS_PLUGIN_PATH . 'assets/vendor/model-viewer.min.js';
		$local_model_viewer_url  = S3DS_PLUGIN_URL . 'assets/vendor/model-viewer.min.js';
		$model_viewer_url        = 'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js';

		if ( 'local' === $settings['model_viewer_source'] ) {
			if ( file_exists( $local_model_viewer_path ) ) {
				$model_viewer_url = $local_model_viewer_url;
			}
		} elseif ( file_exists( $local_model_viewer_path ) ) {
			// Keep a local fallback available in environments where external CDNs are blocked.
			$model_viewer_url = $local_model_viewer_url;
		}

		wp_enqueue_script( 'model-viewer', $model_viewer_url, array(), S3DS_VERSION, true );
		wp_script_add_data( 'model-viewer', 'async', true );
		wp_script_add_data( 'model-viewer', 'type', 'module' );

		wp_enqueue_style( 's3ds-frontend', S3DS_PLUGIN_URL . 'assets/css/frontend.css', array(), S3DS_VERSION );
		wp_enqueue_script( 's3ds-frontend', S3DS_PLUGIN_URL . 'assets/js/frontend.js', array(), S3DS_VERSION, true );

		wp_localize_script(
			's3ds-frontend',
			's3dsFrontend',
			array(
				'debug' => ! empty( $settings['debug_mode'] ),
			)
		);
	}

	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_plugin_screen = 's3d_showcase' === $screen->post_type || 's3d_showcase_page_s3ds-settings' === $screen->id;
		if ( ! $is_plugin_screen ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 's3ds-admin', S3DS_PLUGIN_URL . 'assets/css/admin.css', array(), S3DS_VERSION );
		wp_enqueue_script( 's3ds-admin', S3DS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), S3DS_VERSION, true );
	}
}
