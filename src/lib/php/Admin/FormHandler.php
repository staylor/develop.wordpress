<?php
namespace WP\Admin;

use WP\Magic\Request;

abstract class FormHandler {
	use Request;

	public function redirect( $location ) {
		wp_redirect( $location );
		exit();
	}
}