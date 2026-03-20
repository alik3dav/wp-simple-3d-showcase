<?php
/**
 * Shared plugin helpers.
 *
 * @package WP3DS
 */

namespace WP3DS;

defined( 'ABSPATH' ) || exit;

class Helpers {
	public static function get_asset_path( string $relative_path ): string {
		return WP3DS_PATH . ltrim( $relative_path, '/' );
	}

	public static function get_asset_url( string $relative_path ): string {
		return WP3DS_URL . ltrim( $relative_path, '/' );
	}

	public static function get_asset_version( string $relative_path ): string {
		$absolute_path = self::get_asset_path( $relative_path );

		if ( ! file_exists( $absolute_path ) ) {
			return WP3DS_VERSION;
		}

		$mtime = filemtime( $absolute_path );

		return $mtime ? (string) $mtime : WP3DS_VERSION;
	}

	public static function sanitize_dimension( $value, string $default = '600px' ): string {
		$dimension = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( '' === $dimension ) {
			return $default;
		}

		if ( preg_match( '/^\d+(?:\.\d+)?(?:px|rem|em|vh|vw|%)$/', $dimension ) ) {
			return $dimension;
		}

		return $default;
	}

	public static function get_attachment_url( int $attachment_id, string $expected_extension = '' ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$attachment_url = wp_get_attachment_url( $attachment_id );

		if ( ! $attachment_url || ! self::is_allowed_attachment_extension( $attachment_url, $expected_extension ) ) {
			return '';
		}

		return $attachment_url;
	}

	public static function resolve_local_attachment_id( $value, string $expected_extension = '' ): int {
		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );
			$attachment    = get_post( $attachment_id );

			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return 0;
			}

			$attachment_url = wp_get_attachment_url( $attachment_id );

			return self::is_allowed_attachment_extension( $attachment_url ?: '', $expected_extension ) ? $attachment_id : 0;
		}

		$url = esc_url_raw( (string) $value );

		if ( '' === $url || ! self::is_local_url( $url ) || ! self::is_allowed_attachment_extension( $url, $expected_extension ) ) {
			return 0;
		}

		return absint( attachment_url_to_postid( $url ) );
	}

	public static function is_local_url( string $url ): bool {
		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['host'] ) ) {
			return true;
		}

		$site_url = wp_parse_url( home_url() );

		return isset( $site_url['host'] ) && strtolower( $parsed_url['host'] ) === strtolower( $site_url['host'] );
	}

	public static function is_allowed_attachment_extension( string $url, string $expected_extension = '' ): bool {
		if ( '' === $url ) {
			return false;
		}

		$path      = wp_parse_url( $url, PHP_URL_PATH );
		$extension = is_string( $path ) ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';

		if ( '' === $expected_extension ) {
			return '' !== $extension;
		}

		return $expected_extension === $extension;
	}

	/**
	 * @param mixed $raw_json Raw JSON payload.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_explode_parts( $raw_json ): array {
		$decoded = json_decode( is_string( $raw_json ) ? $raw_json : '[]', true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $decoded as $part ) {
			if ( ! is_array( $part ) || empty( $part['key'] ) ) {
				continue;
			}

			$sanitized[] = array(
				'key'             => sanitize_text_field( (string) $part['key'] ),
				'name'            => sanitize_text_field( (string) ( $part['name'] ?? __( 'Part', '3D-Model-Viewer' ) ) ),
				'description'     => sanitize_textarea_field( (string) ( $part['description'] ?? '' ) ),
				'characteristics' => sanitize_textarea_field( (string) ( $part['characteristics'] ?? '' ) ),
				'x'               => round( (float) ( $part['x'] ?? 0 ), 4 ),
				'y'               => round( (float) ( $part['y'] ?? 0 ), 4 ),
				'z'               => round( (float) ( $part['z'] ?? 0 ), 4 ),
			);
		}

		return $sanitized;
	}

	/**
	 * @param array<string, mixed> $context Template variables.
	 */
	public static function render_template( string $template, array $context = array() ): string {
		$template_path = WP3DS_PATH . 'templates/' . ltrim( $template, '/' );

		if ( ! is_readable( $template_path ) ) {
			return '';
		}

		extract( $context, EXTR_SKIP );

		ob_start();
		include $template_path;

		return (string) ob_get_clean();
	}
}
