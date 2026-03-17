<?php

namespace S3DS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {
	public static function activate() {
		$defaults = Helpers::default_settings();
		if ( false === get_option( S3DS_OPTION_KEY, false ) ) {
			add_option( S3DS_OPTION_KEY, $defaults );
		}

		$post_type = new PostTypes\ShowcasePostType();
		$post_type->register();
		flush_rewrite_rules();
	}
}
