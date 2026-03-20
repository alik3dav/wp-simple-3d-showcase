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
		wp_script_add_data( 'wp3ds-admin', 'type', 'module' );

		wp_localize_script(
			'wp3ds-admin',
			'wp3dsAdminConfig',
			array(
				'i18n' => array(
					'selectFile'         => __( 'Select file', '3d-model-viewer' ),
					'useFile'            => __( 'Use this file', '3d-model-viewer' ),
					'invalidFileType'    => __( 'Please select a file with the required extension.', '3d-model-viewer' ),
					'noPartsDetected'    => __( 'No mesh parts were detected in this GLB file.', '3d-model-viewer' ),
					'detectingParts'     => __( 'Detecting mesh parts from the GLB file…', '3d-model-viewer' ),
					/* translators: %d: number of detected mesh parts. */
					'partsDetected'      => __( 'Detected %d parts automatically.', '3d-model-viewer' ),
					'selectGlbPrompt'    => __( 'Select a GLB file to detect model parts.', '3d-model-viewer' ),
					'loadGlbError'       => __( 'Unable to inspect the selected GLB file.', '3d-model-viewer' ),
					'part'               => __( 'Part', '3d-model-viewer' ),
					'displayName'        => __( 'Display name', '3d-model-viewer' ),
					'description'        => __( 'Description', '3d-model-viewer' ),
					'characteristics'    => __( 'Characteristics', '3d-model-viewer' ),
					'onePerLine'         => __( 'One characteristic per line', '3d-model-viewer' ),
					'partColumn'         => __( 'Part', '3d-model-viewer' ),
					'descriptionColumn'  => __( 'Description', '3d-model-viewer' ),
					'characteristicsCol' => __( 'Characteristics', '3d-model-viewer' ),
					'shortSummary'       => __( 'Short summary shown in the viewer', '3d-model-viewer' ),
				),
			)
		);
	}
}
