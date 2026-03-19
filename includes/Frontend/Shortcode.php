<?php
/**
 * Front-end shortcode renderer.
 *
 * @package WP3DS
 */

namespace WP3DS\Frontend;

use WP3DS\Admin\SettingsPage;
use WP3DS\Helpers;
use WP3DS\PostTypes\ShowcasePostType;

defined( 'ABSPATH' ) || exit;

class Shortcode {
	public function register(): void {
		add_shortcode( 'wp3ds_viewer', array( $this, 'render' ) );
	}

	public function render( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'slug'   => '',
				'height' => '600px',
			),
			$atts,
			'wp3ds_viewer'
		);

		$post_id = $this->resolve_post_id( $atts );

		if ( ! $post_id || ShowcasePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return '<p>' . esc_html__( 'Invalid 3D item.', 'three-d-showcase' ) . '</p>';
		}

		if ( 'publish' !== get_post_status( $post_id ) && ! current_user_can( 'read_post', $post_id ) ) {
			return '';
		}

		$model_attachment_id = absint( get_post_meta( $post_id, '_wp3ds_model_attachment_id', true ) );
		$model_url           = Helpers::get_attachment_url( $model_attachment_id, 'glb' );

		if ( '' === $model_url ) {
			$model_url = $this->get_legacy_local_model_url( $post_id );
		}

		if ( '' === $model_url ) {
			return '<p>' . esc_html__( 'No GLB file is assigned to this 3D item.', 'three-d-showcase' ) . '</p>';
		}

		$settings_page         = new SettingsPage();
		$interaction_settings  = $settings_page->get_interaction_settings();
		$context               = array(
			'height'                  => Helpers::sanitize_dimension( $atts['height'] ),
			'model_url'               => $model_url,
			'model_name'              => get_the_title( $post_id ) ?: __( '3D Model', 'three-d-showcase' ),
			'plugin_label'            => sprintf(
				/* translators: 1: plugin name, 2: plugin version */
				__( '%1$s v%2$s', 'three-d-showcase' ),
				'3D Showcase',
				WP3DS_VERSION
			),
			'bg_color'                => sanitize_hex_color( (string) get_post_meta( $post_id, '_wp3ds_bg_color', true ) ) ?: '#f5f5f5',
			'auto_rotate'             => '1' === get_post_meta( $post_id, '_wp3ds_auto_rotate', true ),
			'explode_step'            => (string) max( 0, min( 5, (float) get_post_meta( $post_id, '_wp3ds_explode_step', true ) ?: 0.15 ) ),
			'explode_parts'           => wp_json_encode( Helpers::normalize_explode_parts( get_post_meta( $post_id, '_wp3ds_explode_parts', true ) ) ) ?: '[]',
			'hdri_map_url'            => $settings_page->get_hdri_map_url(),
			'interaction_settings'    => $interaction_settings,
			'controls_aria_label'     => __( '3D viewer controls', 'three-d-showcase' ),
			'reset_label'             => __( 'Reset', 'three-d-showcase' ),
			'rotate_label'            => __( 'Rotate', 'three-d-showcase' ),
			'explode_label'           => __( 'Explode', 'three-d-showcase' ),
			'focus_label'             => __( 'Focus', 'three-d-showcase' ),
			'fullscreen_label'        => __( 'Full', 'three-d-showcase' ),
			'reset_aria_label'        => __( 'Reset view', 'three-d-showcase' ),
			'rotate_aria_label'       => __( 'Toggle auto rotation', 'three-d-showcase' ),
			'explode_aria_label'      => __( 'Toggle explode view', 'three-d-showcase' ),
			'focus_aria_label'        => __( 'Toggle focus mode', 'three-d-showcase' ),
			'fullscreen_aria_label'   => __( 'Toggle fullscreen', 'three-d-showcase' ),
			'model_name_aria_label'   => __( '3D model name', 'three-d-showcase' ),
			'plugin_meta_aria_label'  => __( 'Plugin name and version', 'three-d-showcase' ),
			'start_label'             => __( 'Load 3D model', 'three-d-showcase' ),
			'start_description'       => __( 'Click to start loading this 3D item only when you are ready.', 'three-d-showcase' ),
			'start_aria_label'        => __( 'Start loading the 3D model', 'three-d-showcase' ),
			'loading_label'           => __( 'Loading 3D model…', 'three-d-showcase' ),
			'part_details_eyebrow'    => __( 'Part details', 'three-d-showcase' ),
			'select_part_label'       => __( 'Select a part', 'three-d-showcase' ),
			'part_details_description'=> __( 'Double-click any object in the model to open its details.', 'three-d-showcase' ),
			'characteristics_label'   => __( 'Characteristics', 'three-d-showcase' ),
			'mesh_key_label'          => __( 'Mesh key', 'three-d-showcase' ),
			'close_aria_label'        => __( 'Close part details', 'three-d-showcase' ),
		);

		wp_enqueue_style( 'wp3ds-frontend' );
		wp_enqueue_script( 'wp3ds-frontend' );

		return Helpers::render_template( 'viewer.php', $context );
	}

	private function resolve_post_id( array $atts ): int {
		$post_id = ! empty( $atts['id'] ) ? absint( $atts['id'] ) : 0;

		if ( ! $post_id && ! empty( $atts['slug'] ) ) {
			$post = get_page_by_path( sanitize_title( (string) $atts['slug'] ), OBJECT, ShowcasePostType::POST_TYPE );
			if ( $post instanceof \WP_Post ) {
				$post_id = $post->ID;
			}
		}

		if ( ! $post_id && ShowcasePostType::POST_TYPE === get_post_type() ) {
			$post_id = (int) get_the_ID();
		}

		return $post_id;
	}

	private function get_legacy_local_model_url( int $post_id ): string {
		$legacy_url     = (string) get_post_meta( $post_id, '_wp3ds_model_url', true );
		$attachment_id  = Helpers::resolve_local_attachment_id( $legacy_url, 'glb' );
		$attachment_url = Helpers::get_attachment_url( $attachment_id, 'glb' );

		if ( $attachment_id > 0 && '' !== $attachment_url ) {
			update_post_meta( $post_id, '_wp3ds_model_attachment_id', $attachment_id );
			update_post_meta( $post_id, '_wp3ds_model_url', $attachment_url );
			return $attachment_url;
		}

		return '';
	}
}
