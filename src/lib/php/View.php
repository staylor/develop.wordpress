<?php
namespace WP;

use WP\{App,Mustache};

class View extends Magic {
	use Mustache;

	protected $app;
	public $_get;
	public $_post;
	public $_request;

	public function __construct( App $app ) {
		$this->app = $app;

		$request = $app['request'];
		$this->_get = $request->query;
		$this->_post = $request->request;
		$this->_request = $request->attributes;
	}

	public function setData( $data = [] ) {
		$this->data = $data;
	}

	public function doAction() {
		return function ( $text, \Mustache_LambdaHelper $helper ) {
			if ( false === strpos( $text, '[' ) ) {
				ob_start();
				do_action( $text );
				return ob_get_clean();
			}

			list( $action, $frag ) = explode( '[', $text, 2 );
			$data = $helper->render( '[' . $frag );
			$args = json_decode( $data, true );

			ob_start();
			array_unshift( $args, $action );
			call_user_func_array( 'do_action', $args );
			return ob_get_clean();
		};
	}
}