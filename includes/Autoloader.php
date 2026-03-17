<?php

namespace S3DS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader {
	private $prefix;
	private $base_dir;

	public function __construct( $prefix, $base_dir ) {
		$this->prefix   = $prefix;
		$this->base_dir = $base_dir;
	}

	public function register() {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	public function autoload( $class ) {
		$len = strlen( $this->prefix );

		if ( 0 !== strncmp( $this->prefix, $class, $len ) ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $this->base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
