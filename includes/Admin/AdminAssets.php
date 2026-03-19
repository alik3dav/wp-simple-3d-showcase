<?php
/**
 * Admin asset loader.
 *
 * @package WP3DS
 */

namespace WP3DS\Admin;

use WP3DS\Helpers;
use WP3DS\PostTypes\ShowcasePostType;

defined( 'ABSPATH' ) || exit;

class AdminAssets {
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( $this, 'mark_as_module' ), 10, 3 );
	}

	public function enqueue( string $hook ): void {
		global $post_type;

		$allowed_hooks = array( 'settings_page_wp3ds-settings', 'post.php', 'post-new.php' );

		if ( ShowcasePostType::POST_TYPE !== $post_type && ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && ShowcasePostType::POST_TYPE !== $post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'wp3ds-admin',
			Helpers::get_asset_url( 'assets/dist/admin.css' ),
			array(),
			Helpers::get_asset_version( 'assets/dist/admin.css' )
		);

		wp_enqueue_script(
			'wp3ds-admin',
			Helpers::get_asset_url( 'assets/dist/admin.js' ),
			array( 'media-editor', 'wp-i18n' ),
			Helpers::get_asset_version( 'assets/dist/admin.js' ),
			true
		);

		wp_localize_script(
			'wp3ds-admin',
			'wp3dsAdminConfig',
			array(
				'i18n' => array(
					'selectFile'         => __( 'Select file', 'wp-3d-showcase' ),
					'useFile'            => __( 'Use this file', 'wp-3d-showcase' ),
					'invalidFileType'    => __( 'Please select a file with the required extension.', 'wp-3d-showcase' ),
					'noPartsDetected'    => __( 'No mesh parts were detected in this GLB file.', 'wp-3d-showcase' ),
					'detectingParts'     => __( 'Detecting mesh parts from the GLB file…', 'wp-3d-showcase' ),
					'partsDetected'      => __( 'Detected %d parts automatically.', 'wp-3d-showcase' ),
					'selectGlbPrompt'    => __( 'Select a GLB file to detect model parts.', 'wp-3d-showcase' ),
					'loadGlbError'       => __( 'Unable to inspect the selected GLB file.', 'wp-3d-showcase' ),
					'part'               => __( 'Part', 'wp-3d-showcase' ),
					'displayName'        => __( 'Display name', 'wp-3d-showcase' ),
					'description'        => __( 'Description', 'wp-3d-showcase' ),
					'characteristics'    => __( 'Characteristics', 'wp-3d-showcase' ),
					'onePerLine'         => __( 'One characteristic per line', 'wp-3d-showcase' ),
					'partColumn'         => __( 'Part', 'wp-3d-showcase' ),
					'descriptionColumn'  => __( 'Description', 'wp-3d-showcase' ),
					'characteristicsCol' => __( 'Characteristics', 'wp-3d-showcase' ),
					'shortSummary'       => __( 'Short summary shown in the viewer', 'wp-3d-showcase' ),
				),
			)
		);
	}

	public function mark_as_module( string $tag, string $handle, string $src ): string {
		if ( 'wp3ds-admin' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%1$s" id="%2$s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
