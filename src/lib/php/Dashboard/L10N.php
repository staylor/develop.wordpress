<?php
namespace WP\Dashboard;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			'dashboard' => __( 'Dashboard' ),
			'dismiss_the_welcome_panel' => __( 'Dismiss the welcome panel' ),
			'dismiss' => __( 'Dismiss' ),
		];
	}
}