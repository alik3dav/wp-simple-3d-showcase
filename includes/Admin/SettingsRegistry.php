<?php

namespace S3DS\Admin;

use S3DS\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsRegistry {
	public function register() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting( 's3ds_settings_group', S3DS_OPTION_KEY, array( $this, 'sanitize' ) );

		add_settings_section( 's3ds_general', __( 'Global Viewer Defaults', 'simple-3d-showcase' ), '__return_false', 's3ds-settings' );
	}

	public function sanitize( $input ) {
		$defaults = Helpers::default_settings();
		$output   = array();

		$output['viewer_height']       = Helpers::sanitize_dimension( $input['viewer_height'] ?? $defaults['viewer_height'], $defaults['viewer_height'] );
		$output['background_color']    = sanitize_hex_color( $input['background_color'] ?? $defaults['background_color'] ) ?: $defaults['background_color'];
		$output['auto_rotate']         = ! empty( $input['auto_rotate'] ) ? 1 : 0;
		$output['camera_controls']     = ! empty( $input['camera_controls'] ) ? 1 : 0;
		$output['exposure']            = (string) ( is_numeric( $input['exposure'] ?? null ) ? $input['exposure'] : $defaults['exposure'] );
		$output['shadow_intensity']    = (string) ( is_numeric( $input['shadow_intensity'] ?? null ) ? $input['shadow_intensity'] : $defaults['shadow_intensity'] );
		$output['loading']             = in_array( $input['loading'] ?? '', array( 'eager', 'lazy', 'auto' ), true ) ? $input['loading'] : $defaults['loading'];
		$output['fullscreen_enabled']  = ! empty( $input['fullscreen_enabled'] ) ? 1 : 0;
		$output['touch_hint_enabled']  = ! empty( $input['touch_hint_enabled'] ) ? 1 : 0;
		$output['kiosk_mode']          = ! empty( $input['kiosk_mode'] ) ? 1 : 0;
		$output['brand_label']         = sanitize_text_field( $input['brand_label'] ?? $defaults['brand_label'] );
		$output['model_viewer_source'] = in_array( $input['model_viewer_source'] ?? '', array( 'cdn', 'local' ), true ) ? $input['model_viewer_source'] : $defaults['model_viewer_source'];
		$output['debug_mode']          = ! empty( $input['debug_mode'] ) ? 1 : 0;

		return $output;
	}
}
