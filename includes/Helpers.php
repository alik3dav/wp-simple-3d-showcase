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

	public static function normalize_media_url( $value ) {
		$value = trim( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, '//' ) ) {
			$value = ( is_ssl() ? 'https:' : 'http:' ) . $value;
		}

		if ( 0 === strpos( $value, '/' ) ) {
			$value = home_url( $value );
		} elseif ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $value ) ) {
			$value = home_url( '/' . ltrim( $value, '/' ) );
		}

		return esc_url_raw( $value, array( 'http', 'https' ) );
	}

	public static function is_supported_model_url( $url ) {
		$path = wp_parse_url( (string) $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		return (bool) preg_match( '/\.(glb|gltf)$/i', $path );
	}
}
