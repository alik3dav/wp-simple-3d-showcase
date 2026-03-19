<?php
/**
 * Front-end asset loader.
 *
 * @package WP3DS
 */

namespace WP3DS\Frontend;

use WP3DS\Helpers;

defined( 'ABSPATH' ) || exit;

class FrontendAssets {
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
	}

	public function register(): void {
		wp_register_style(
			'wp3ds-frontend',
			Helpers::get_asset_url( 'assets/dist/frontend.css' ),
			array(),
			Helpers::get_asset_version( 'assets/dist/frontend.css' )
		);

		wp_register_script(
			'wp3ds-frontend',
			Helpers::get_asset_url( 'assets/dist/frontend.js' ),
			array(),
			Helpers::get_asset_version( 'assets/dist/frontend.js' ),
			true
		);
		wp_script_add_data( 'wp3ds-frontend', 'type', 'module' );

		wp_localize_script(
			'wp3ds-frontend',
			'wp3dsFrontendConfig',
			array(
				'i18n' => array(
					'missingModel' => __( 'No model file is assigned to this viewer.', 'wp-3d-showcase' ),
					'failedModel'  => __( 'Failed to load the selected 3D model.', 'wp-3d-showcase' ),
					'loadingLabel' => __( 'Loading 3D model…', 'wp-3d-showcase' ),
				),
			)
		);
	}
}
