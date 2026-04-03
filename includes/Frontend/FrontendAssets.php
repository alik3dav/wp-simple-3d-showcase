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
		add_filter( 'script_loader_tag', array( $this, 'set_module_script_type' ), 10, 3 );
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
					'missingModel'     => __( 'No model file is assigned to this viewer.', '3d-model-viewer' ),
					'failedModel'      => __( 'Failed to load the selected 3D model.', '3d-model-viewer' ),
					'loadingLabel'     => __( 'Loading 3D model…', '3d-model-viewer' ),
					'startLabel'       => __( 'Load 3D model', '3d-model-viewer' ),
					'startDescription' => __( 'Click to start loading this 3D item only when you are ready.', '3d-model-viewer' ),
				),
			)
		);
	}

	/**
	 * Ensure the frontend bundle is always loaded as an ES module.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public function set_module_script_type( string $tag, string $handle, string $src ): string {
		if ( 'wp3ds-frontend' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>' . "\n",
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
