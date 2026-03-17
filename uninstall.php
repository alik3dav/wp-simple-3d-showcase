<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 's3ds_settings' );

$cleanup_showcases = get_option( 's3ds_cleanup_showcases', 0 );
if ( ! $cleanup_showcases ) {
	return;
}

$posts = get_posts(
	array(
		'post_type'      => 's3d_showcase',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}
