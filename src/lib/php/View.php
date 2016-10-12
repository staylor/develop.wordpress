<?php
namespace WP;

/**
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_get
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_post
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_request
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_server
 */
class View {
	use Mustache;

	protected $app;

	protected $actions = [];
	protected $data = [];

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function __set( string $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __unset( string $name ) {
		unset( $this->data[ $name ] );
	}

	public function __isset( string $name ): bool
	{
		return array_key_exists( $name, $this->data );
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

		case '_server':
			return $this->app['request']->server;
		}
	}

	public function setData( $data = [] ) {
		$this->data = $data;
	}

	public function setActions( $actions = [] ) {
		$this->actions = array_merge( $this->actions, $actions );
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