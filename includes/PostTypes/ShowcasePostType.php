<?php
/**
 * Custom post type registration.
 *
 * @package WP3DS
 */

namespace WP3DS\PostTypes;

defined( 'ABSPATH' ) || exit;

class ShowcasePostType {
	public const POST_TYPE = 'wp3ds_item';

	public function register(): void {
		$labels = array(
			'name'                  => __( '3D Showcase Items', 'wp-3d-showcase' ),
			'singular_name'         => __( '3D Showcase Item', 'wp-3d-showcase' ),
			'add_new'               => __( 'Add New', 'wp-3d-showcase' ),
			'add_new_item'          => __( 'Add New 3D Item', 'wp-3d-showcase' ),
			'edit_item'             => __( 'Edit 3D Item', 'wp-3d-showcase' ),
			'new_item'              => __( 'New 3D Item', 'wp-3d-showcase' ),
			'view_item'             => __( 'View 3D Item', 'wp-3d-showcase' ),
			'view_items'            => __( 'View 3D Items', 'wp-3d-showcase' ),
			'search_items'          => __( 'Search 3D Items', 'wp-3d-showcase' ),
			'not_found'             => __( 'No 3D items found.', 'wp-3d-showcase' ),
			'not_found_in_trash'    => __( 'No 3D items found in Trash.', 'wp-3d-showcase' ),
			'all_items'             => __( 'All 3D Items', 'wp-3d-showcase' ),
			'archives'              => __( '3D Item Archives', 'wp-3d-showcase' ),
			'attributes'            => __( '3D Item Attributes', 'wp-3d-showcase' ),
			'insert_into_item'      => __( 'Insert into 3D item', 'wp-3d-showcase' ),
			'uploaded_to_this_item' => __( 'Uploaded to this 3D item', 'wp-3d-showcase' ),
			'menu_name'             => __( '3D Showcase', 'wp-3d-showcase' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-format-image',
				'supports'           => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'        => false,
				'rewrite'            => array( 'slug' => '3d-showcase' ),
				'map_meta_cap'       => true,
				'menu_position'      => 26,
			)
		);
	}
}
