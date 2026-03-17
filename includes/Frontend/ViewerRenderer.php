<?php

namespace S3DS\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ViewerRenderer {
	private $asset_loader;

	public function __construct( AssetLoader $asset_loader ) {
		$this->asset_loader = $asset_loader;
	}

	public function render( array $config ) {
		$this->asset_loader->mark_frontend_needed();
		$instance_id = 's3ds-' . wp_generate_uuid4();
		ob_start();
		include S3DS_PLUGIN_PATH . 'templates/viewer.php';
		return ob_get_clean();
	}
}
