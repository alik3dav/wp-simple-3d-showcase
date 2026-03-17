<?php

namespace S3DS\Frontend;

use S3DS\Domain\ShowcaseRepository;
use S3DS\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {
	private $repository;
	private $renderer;
	private $asset_loader;

	public function __construct( ShowcaseRepository $repository, ViewerRenderer $renderer, AssetLoader $asset_loader ) {
		$this->repository   = $repository;
		$this->renderer     = $renderer;
		$this->asset_loader = $asset_loader;
	}

	public function register() {
		add_shortcode( 'simple_3d_showcase', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'              => '',
				'model_url'       => '',
				'poster_url'      => '',
				'height'          => '',
				'auto_rotate'     => '',
				'camera_controls' => '',
				'background'      => '',
				'exposure'        => '',
				'shadow_intensity'=> '',
				'loading'         => '',
			),
			$atts,
			'simple_3d_showcase'
		);

		$global = Helpers::get_settings();
		$item   = array();

		if ( ! empty( $atts['id'] ) ) {
			$model = $this->repository->find( (int) $atts['id'] );
			if ( $model ) {
				$item = $model->to_array()['meta'];
			}
		}

		$defaults = array(
			'model_url'         => '',
			'poster_url'        => '',
			'height'            => '600px',
			'background'        => '#dbdbdb',
			'auto_rotate'       => true,
			'camera_controls'   => true,
			'exposure'          => '1',
			'shadow_intensity'  => '1',
			'loading'           => 'eager',
			'hint_text'         => __( 'Drag to rotate', 'simple-3d-showcase' ),
			'fullscreen_enabled'=> (bool) $global['fullscreen_enabled'],
			'touch_hint_enabled'=> (bool) $global['touch_hint_enabled'],
			'brand_label'       => $global['brand_label'],
			'kiosk_mode'        => (bool) $global['kiosk_mode'],
			'reset_label'       => __( 'Reset', 'simple-3d-showcase' ),
			'rotate_label'      => __( 'Rotate', 'simple-3d-showcase' ),
			'pause_label'       => __( 'Pause', 'simple-3d-showcase' ),
			'fullscreen_label'  => __( 'Fullscreen', 'simple-3d-showcase' ),
		);

		$config = array_merge( $defaults, $global, $item, $atts );

		$config['height']          = Helpers::sanitize_dimension( $config['height'], $defaults['height'] );
		$config['background']      = sanitize_hex_color( $config['background'] ) ?: $defaults['background'];
		$config['auto_rotate']     = Helpers::bool_attr( $config['auto_rotate'] );
		$config['camera_controls'] = Helpers::bool_attr( $config['camera_controls'] );
		$config['model_url']       = esc_url_raw( $config['model_url'] );
		$config['poster_url']      = esc_url_raw( $config['poster_url'] );
		$config['loading']         = in_array( $config['loading'], array( 'eager', 'lazy', 'auto' ), true ) ? $config['loading'] : 'eager';

		if ( empty( $config['model_url'] ) ) {
			$this->asset_loader->mark_frontend_needed();
			return '<div class="s3ds-no-model">' . esc_html__( 'No model_url provided.', 'simple-3d-showcase' ) . '</div>';
		}

		return $this->renderer->render( $config );
	}
}
