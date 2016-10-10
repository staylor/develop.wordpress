<?php
namespace WP;

/**
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_get
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_post
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_request
 */
class View extends MagicData {
	use Mustache;

	protected $app;

	protected $actions = [];

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function __get( string $name ) {
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}

		switch ( $name ) {
		case '_get':
			return $this->app['request']->query;

		case '_post':
			return $this->app['request']->request;

		case '_request':
			return $this->app['request']->attributes;
		}
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