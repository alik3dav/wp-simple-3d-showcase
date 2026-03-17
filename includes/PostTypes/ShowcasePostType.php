<?php

namespace S3DS\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShowcasePostType {
	const POST_TYPE = 's3d_showcase';

	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( '3D Showcases', 'simple-3d-showcase' ),
			'singular_name'      => __( '3D Showcase', 'simple-3d-showcase' ),
			'add_new'            => __( 'Add New', 'simple-3d-showcase' ),
			'add_new_item'       => __( 'Add New 3D Showcase', 'simple-3d-showcase' ),
			'edit_item'          => __( 'Edit 3D Showcase', 'simple-3d-showcase' ),
			'new_item'           => __( 'New 3D Showcase', 'simple-3d-showcase' ),
			'view_item'          => __( 'View 3D Showcase', 'simple-3d-showcase' ),
			'search_items'       => __( 'Search 3D Showcases', 'simple-3d-showcase' ),
			'not_found'          => __( 'No showcases found', 'simple-3d-showcase' ),
			'not_found_in_trash' => __( 'No showcases found in trash', 'simple-3d-showcase' ),
			'menu_name'          => __( '3D Showcases', 'simple-3d-showcase' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-format-gallery',
				'supports'        => array( 'title' ),
				'show_in_rest'    => true,
				'capability_type' => 'post',
			)
		);
	}
}
