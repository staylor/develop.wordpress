<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Widget\Admin\{FormHandler,Help,L10N};

class Widget extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->handler = new FormHandler( $app );
		$this->help = new Help();
		$this->setL10n( new L10N() );
	}
}