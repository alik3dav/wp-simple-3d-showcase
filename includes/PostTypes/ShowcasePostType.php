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
			'name'                  => __( '3D Model Viewer Items', '3D-Model-Viewer' ),
			'singular_name'         => __( '3D Model Viewer Item', '3D-Model-Viewer' ),
			'add_new'               => __( 'Add New', '3D-Model-Viewer' ),
			'add_new_item'          => __( 'Add New 3D Item', '3D-Model-Viewer' ),
			'edit_item'             => __( 'Edit 3D Item', '3D-Model-Viewer' ),
			'new_item'              => __( 'New 3D Item', '3D-Model-Viewer' ),
			'view_item'             => __( 'View 3D Item', '3D-Model-Viewer' ),
			'view_items'            => __( 'View 3D Items', '3D-Model-Viewer' ),
			'search_items'          => __( 'Search 3D Items', '3D-Model-Viewer' ),
			'not_found'             => __( 'No 3D items found.', '3D-Model-Viewer' ),
			'not_found_in_trash'    => __( 'No 3D items found in Trash.', '3D-Model-Viewer' ),
			'all_items'             => __( 'All 3D Items', '3D-Model-Viewer' ),
			'archives'              => __( '3D Item Archives', '3D-Model-Viewer' ),
			'attributes'            => __( '3D Item Attributes', '3D-Model-Viewer' ),
			'insert_into_item'      => __( 'Insert into 3D item', '3D-Model-Viewer' ),
			'uploaded_to_this_item' => __( 'Uploaded to this 3D item', '3D-Model-Viewer' ),
			'menu_name'             => __( '3D Model Viewer', '3D-Model-Viewer' ),
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
