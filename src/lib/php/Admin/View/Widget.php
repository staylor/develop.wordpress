<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Widget\Admin\Help;

class Widget extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help( get_current_screen() );
	}
}