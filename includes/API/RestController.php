<?php

namespace S3DS\API;

use S3DS\Domain\ShowcaseRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestController {
	private $repository;

	public function __construct( ShowcaseRepository $repository ) {
		$this->repository = $repository;
	}

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			's3ds/v1',
			'/showcase/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_showcase' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_showcase( $request ) {
		$model = $this->repository->find( (int) $request['id'] );
		if ( ! $model ) {
			return new \WP_Error( 'not_found', __( 'Showcase not found or inactive.', 'simple-3d-showcase' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $model->to_array() );
	}
}
