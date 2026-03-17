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
		$atts = is_array( $atts ) ? $atts : array();

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

		$manual_overrides = array();
		$manual_keys      = array(
			'model_url',
			'poster_url',
			'height',
			'auto_rotate',
			'camera_controls',
			'background',
			'exposure',
			'shadow_intensity',
			'loading',
		);

		foreach ( $manual_keys as $key ) {
			if ( isset( $atts[ $key ] ) && '' !== trim( (string) $atts[ $key ] ) ) {
				$manual_overrides[ $key ] = $atts[ $key ];
			}
		}

		$config = array_merge( $defaults, $item, $manual_overrides );

		$config['height']          = Helpers::sanitize_dimension( $config['height'], $defaults['height'] );
		$config['background']      = sanitize_hex_color( $config['background'] ) ?: $defaults['background'];
		$config['auto_rotate']     = Helpers::bool_attr( $config['auto_rotate'] );
		$config['camera_controls'] = Helpers::bool_attr( $config['camera_controls'] );
		$config['model_url']       = Helpers::normalize_media_url( $config['model_url'] );
		$config['poster_url']      = Helpers::normalize_media_url( $config['poster_url'] );
		$config['loading']         = in_array( $config['loading'], array( 'eager', 'lazy', 'auto' ), true ) ? $config['loading'] : 'eager';

		$config['load_error_type']    = '';
		$config['load_error_message'] = __( 'This 3D model is currently unavailable.', 'simple-3d-showcase' );

		if ( empty( $config['model_url'] ) ) {
			$config['load_error_type'] = 'missing_url';
		} elseif ( ! Helpers::is_supported_model_url( $config['model_url'] ) ) {
			$config['load_error_type'] = 'invalid_file';
		}

		return $this->renderer->render( $config );
	}
}
