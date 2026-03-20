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
					'selectFile'         => __( 'Select file', '3D-Model-Viewer' ),
					'useFile'            => __( 'Use this file', '3D-Model-Viewer' ),
					'invalidFileType'    => __( 'Please select a file with the required extension.', '3D-Model-Viewer' ),
					'noPartsDetected'    => __( 'No mesh parts were detected in this GLB file.', '3D-Model-Viewer' ),
					'detectingParts'     => __( 'Detecting mesh parts from the GLB file…', '3D-Model-Viewer' ),
					/* translators: %d: number of detected mesh parts. */
					'partsDetected'      => __( 'Detected %d parts automatically.', '3D-Model-Viewer' ),
					'selectGlbPrompt'    => __( 'Select a GLB file to detect model parts.', '3D-Model-Viewer' ),
					'loadGlbError'       => __( 'Unable to inspect the selected GLB file.', '3D-Model-Viewer' ),
					'part'               => __( 'Part', '3D-Model-Viewer' ),
					'displayName'        => __( 'Display name', '3D-Model-Viewer' ),
					'description'        => __( 'Description', '3D-Model-Viewer' ),
					'characteristics'    => __( 'Characteristics', '3D-Model-Viewer' ),
					'onePerLine'         => __( 'One characteristic per line', '3D-Model-Viewer' ),
					'partColumn'         => __( 'Part', '3D-Model-Viewer' ),
					'descriptionColumn'  => __( 'Description', '3D-Model-Viewer' ),
					'characteristicsCol' => __( 'Characteristics', '3D-Model-Viewer' ),
					'shortSummary'       => __( 'Short summary shown in the viewer', '3D-Model-Viewer' ),
				),
			)
		);
	}
}
