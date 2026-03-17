<?php

namespace S3DS\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShowcaseModel {
	private $id;
	private $title;
	private $meta;

	public function __construct( $id, $title, array $meta ) {
		$this->id    = (int) $id;
		$this->title = (string) $title;
		$this->meta  = $meta;
	}

	public function to_array() {
		return array(
			'id'    => $this->id,
			'title' => $this->title,
			'meta'  => $this->meta,
		);
	}
}
