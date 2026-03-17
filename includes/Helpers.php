<?php

namespace S3DS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	public static function default_settings() {
		return array(
			'viewer_height'       => '600px',
			'background_color'    => '#dbdbdb',
			'auto_rotate'         => 1,
			'camera_controls'     => 1,
			'exposure'            => '1',
			'shadow_intensity'    => '1',
			'loading'             => 'eager',
			'fullscreen_enabled'  => 1,
			'touch_hint_enabled'  => 1,
			'kiosk_mode'          => 0,
			'brand_label'         => __( 'Simple 3D Showcase', 'simple-3d-showcase' ),
			'model_viewer_source' => 'cdn',
			'debug_mode'          => 0,
		);
	}

	public static function bool_attr( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	}

	public static function sanitize_dimension( $value, $default = '600px' ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^\d+(px|vh|vw|%)$/', $value ) ) {
			return $value;
		}
		return $default;
	}

	public static function get_settings() {
		return wp_parse_args( get_option( S3DS_OPTION_KEY, array() ), self::default_settings() );
	}
}
