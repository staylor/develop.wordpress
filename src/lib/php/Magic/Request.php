<?php
namespace WP\Magic;

use WP\App;
use Symfony\Component\HttpFoundation\{ParameterBag,ServerBag};
use function WP\getApp;
/**
 * @property-read App $app
 * @property-read ParameterBag $_get
 * @property-read ParameterBag $_post
 * @property-read ParameterBag $_request
 * @property-read ServerBag    $_server
 */
trait Request {
	/**
	 * @return App|ParameterBag|ServerBag|void
	 */
	public function __get( string $name ) {
		$app = getApp();

		switch ( $name ) {
		case 'app':
			return $app;

		case '_get':
			return $app['request']->query;

		case '_post':
			return $app['request']->request;

		case '_request':
			return $app['request']->attributes;

		case '_server':
			return $app['request']->server;
		}
	}
}
