<?php
namespace WP\Media\Admin;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			'add_new' => _x( 'Add New', 'file' ),
		];
	}
}