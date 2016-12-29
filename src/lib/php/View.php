<?php
namespace WP;

use WP\Template\MustacheTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
/**
 * @property-read ParameterBag $_get
 * @property-read ParameterBag $_post
 * @property-read ParameterBag $_request
 * @property-read ParameterBag $_server
 */
class View {
	use MustacheTrait;

	protected $app;

	protected $actions = [];
	protected $data = [];

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __unset( $name ) {
		unset( $this->data[ $name ] );
	}

	public function __isset( $name )
	{
		return array_key_exists( $name, $this->data );
	}

	/**
	 * @param string $name
	 * @return ParameterBag|void
	 */
	public function __get( $name ) {
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
		$this->data = array_merge( $this->data, $data );
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
				$output = ob_get_clean();
				return trim( $output );
			}

			$args = $this->actions[ $action ];
			array_unshift( $args, $action );

			ob_start();
			call_user_func_array( 'do_action', $args );
			$output = ob_get_clean();
			return trim( $output );
		};
	}
}
