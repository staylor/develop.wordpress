<?php
namespace WP\Admin;

use WP\App;

/**
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_get
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_post
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_request
 */
abstract class FormHandler {
	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function redirect( $location ) {
		wp_redirect( $location );
		exit();
	}

	public function __get( string $name ) {
		switch ( $name ) {
		case '_get':
			return $this->app['request']->query;

		case '_post':
			return $this->app['request']->request;

		case '_request':
			return $this->app['request']->attributes;
		}
	}
}