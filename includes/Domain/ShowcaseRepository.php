<?php

namespace S3DS\Domain;

use S3DS\Admin\MetaBoxes;
use S3DS\PostTypes\ShowcasePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShowcaseRepository {
	public function find( $id ) {
		$post = get_post( $id );
		if ( ! $post || ShowcasePostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$meta = get_post_meta( $post->ID, MetaBoxes::META_KEY, true );
		$meta = is_array( $meta ) ? $meta : array();

		if ( isset( $meta['status'] ) && 'inactive' === $meta['status'] ) {
			return null;
		}

		return new ShowcaseModel( $post->ID, $post->post_title, $meta );
	}
}
