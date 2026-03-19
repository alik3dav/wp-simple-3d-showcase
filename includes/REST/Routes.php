<?php
/**
 * REST API routes.
 *
 * @package WP3DS
 */

namespace WP3DS\REST;

use WP3DS\Admin\SettingsPage;
use WP3DS\Helpers;
use WP3DS\PostTypes\ShowcasePostType;

defined( 'ABSPATH' ) || exit;

class Routes {
	public function register(): void {
		register_rest_route(
			'wp3ds/v1',
			'/item/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'can_read_item' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ): bool {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	public function can_read_item( \WP_REST_Request $request ): bool {
		$post_id = absint( $request['id'] );

		if ( ShowcasePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}

		return 'publish' === get_post_status( $post_id ) || current_user_can( 'read_post', $post_id );
	}

	public function get_item( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = absint( $request['id'] );

		if ( ShowcasePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Item not found.', 'three-d-showcase' ) ), 404 );
		}

		$settings_page      = new SettingsPage();
		$model_attachment_id = absint( get_post_meta( $post_id, '_wp3ds_model_attachment_id', true ) );
		$model_url           = Helpers::get_attachment_url( $model_attachment_id, 'glb' );

		$data = array(
			'id'           => $post_id,
			'title'        => get_the_title( $post_id ),
			'model_url'    => $model_url,
			'bg_color'     => sanitize_hex_color( (string) get_post_meta( $post_id, '_wp3ds_bg_color', true ) ) ?: '#f5f5f5',
			'auto_rotate'  => '1' === get_post_meta( $post_id, '_wp3ds_auto_rotate', true ),
			'explode_step' => (float) get_post_meta( $post_id, '_wp3ds_explode_step', true ),
			'explode_parts'=> Helpers::normalize_explode_parts( get_post_meta( $post_id, '_wp3ds_explode_parts', true ) ),
			'hdri_map_url' => $settings_page->get_hdri_map_url(),
		);

		return new \WP_REST_Response( $data, 200 );
	}
}
