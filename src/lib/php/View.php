<?php
namespace WP;

use WP\{App,Mustache};

class View extends Magic {
	use Mustache;

	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function setData( $data = [] ) {
		$this->data = $data;
	}

	public function doAction() {
		return function ( $text, \Mustache_LambdaHelper $helper ) {
			$parsed = $helper->render( $text );
			ob_start();
			do_action( $parsed );
			return ob_get_clean();
		};
	}
}