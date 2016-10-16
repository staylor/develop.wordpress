<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Term\Admin\Help;

class Term extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help();
	}
}
