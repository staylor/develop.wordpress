<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Post\Admin\Help;

class Post extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help();
	}
}
