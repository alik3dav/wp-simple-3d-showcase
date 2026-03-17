<?php

namespace S3DS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu {
	private $settings_page;

	public function __construct( SettingsPage $settings_page ) {
		$this->settings_page = $settings_page;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=s3d_showcase',
			__( 'Showcase Settings', 'simple-3d-showcase' ),
			__( 'Settings', 'simple-3d-showcase' ),
			'manage_options',
			's3ds-settings',
			array( $this->settings_page, 'render' )
		);
	}
}
