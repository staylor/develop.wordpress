<?php
namespace WP\Media\Admin;

use WP\MagicData;

class L10N extends MagicData {
	public function __construct() {
		$this->data = [
			'add_new' => _x( 'Add New', 'file' ),
		];
	}
}