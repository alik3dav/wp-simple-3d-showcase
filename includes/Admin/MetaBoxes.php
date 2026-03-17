<?php

namespace S3DS\Admin;

use S3DS\Helpers;
use S3DS\PostTypes\ShowcasePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MetaBoxes {
	const META_KEY = '_s3ds_showcase';

	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . ShowcasePostType::POST_TYPE, array( $this, 'save' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			's3ds_showcase_options',
			__( 'Showcase Configuration', 'simple-3d-showcase' ),
			array( $this, 'render' ),
			ShowcasePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render( $post ) {
		$data = get_post_meta( $post->ID, self::META_KEY, true );
		$data = is_array( $data ) ? $data : array();
		wp_nonce_field( 's3ds_showcase_save', 's3ds_showcase_nonce' );
		include S3DS_PLUGIN_PATH . 'templates/meta-box-showcase.php';
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['s3ds_showcase_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['s3ds_showcase_nonce'] ) ), 's3ds_showcase_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$input = isset( $_POST['s3ds'] ) ? (array) wp_unslash( $_POST['s3ds'] ) : array();

		$clean = array(
			'model_url'        => Helpers::normalize_media_url( $input['model_url'] ?? '' ),
			'poster_url'       => Helpers::normalize_media_url( $input['poster_url'] ?? '' ),
			'height'           => Helpers::sanitize_dimension( $input['height'] ?? '', '' ),
			'background'       => sanitize_hex_color( $input['background'] ?? '' ) ?: '',
			'auto_rotate'      => isset( $input['auto_rotate'] ) ? (string) Helpers::bool_attr( $input['auto_rotate'] ) : '',
			'camera_controls'  => isset( $input['camera_controls'] ) ? (string) Helpers::bool_attr( $input['camera_controls'] ) : '',
			'exposure'         => ( isset( $input['exposure'] ) && is_numeric( $input['exposure'] ) ) ? (string) $input['exposure'] : '',
			'shadow_intensity' => ( isset( $input['shadow_intensity'] ) && is_numeric( $input['shadow_intensity'] ) ) ? (string) $input['shadow_intensity'] : '',
			'hint_text'        => sanitize_text_field( $input['hint_text'] ?? '' ),
			'reset_label'      => sanitize_text_field( $input['reset_label'] ?? '' ),
			'rotate_label'     => sanitize_text_field( $input['rotate_label'] ?? '' ),
			'pause_label'      => sanitize_text_field( $input['pause_label'] ?? '' ),
			'fullscreen_label' => sanitize_text_field( $input['fullscreen_label'] ?? '' ),
			'status'           => ( isset( $input['status'] ) && 'inactive' === $input['status'] ) ? 'inactive' : 'active',
		);

		update_post_meta( $post_id, self::META_KEY, $clean );
	}
}
