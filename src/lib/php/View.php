<?php
namespace WP;

use WP\{App,Mustache};

class View extends MagicData {
	use Mustache;

	protected $app;
	public $_get;
	public $_post;
	public $_request;

	protected $actions = [];

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

	public function setActions( $actions = [] ) {
		$this->actions = $actions;
	}

	public function doAction() {
		return function ( $text, \Mustache_LambdaHelper $helper ) {
			$action = $helper->render( $text );
			if ( empty( $this->actions[ $action ] ) ) {
				ob_start();
				do_action( $action );
				return ob_get_clean();
			}

			$args = $this->actions[ $action ];
			array_unshift( $args, $action );

			ob_start();
			call_user_func_array( 'do_action', $args );
			return ob_get_clean();
		};
	}
}