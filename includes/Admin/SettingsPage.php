<?php

namespace S3DS\Admin;

use S3DS\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPage {
	private $registry;

	public function __construct( SettingsRegistry $registry ) {
		$this->registry = $registry;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Helpers::get_settings();
		include S3DS_PLUGIN_PATH . 'templates/admin-settings.php';
	}
}
