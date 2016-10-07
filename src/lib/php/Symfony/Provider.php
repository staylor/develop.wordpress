<?php
namespace WP\Symfony;

use Pimple\{Container,ServiceProviderInterface};
use Symfony\Component\HttpFoundation\Request;

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['request'] = function () {
			$request = Request::createFromGlobals();

			// use $attributes for $_REQUEST
			$request->attributes->replace( array_merge(
				$request->query->all(),
				$request->request->all()
			) );

			return $request;
		};

		$app['request.method'] = function ( $app ) {
			return $app['request']->getMethod();
		};

		$app['request.host'] = function ( $app ) {
			return $app['request']->getHttpHost();
		};

		$app['request.uri'] = function ( $app ) {
			return $app['request']->getRequestUri();
		};

		$app['request.useragent'] = function ( $app ) {
			return $app['request']->headers->get( 'User-Agent' );
		};

		$app['request.software'] = function ( $app ) {
			return $app['request']->server->get( 'SERVER_SOFTWARE' );
		};

		$app['request.php_self'] = function ( $app ) {
			return $app['request']->server->get( 'PHP_SELF' );
		};

		$app['request.path_info'] = function ( $app ) {
			return $app['request']->getPathInfo();
		};

		$app['request.server_name'] = function ( $app ) {
			return $app['request']->server->get( 'SERVER_NAME' );
		};

		$app['request.script_filename'] = function ( $app ) {
			return $app['request']->server->get( 'SCRIPT_FILENAME' );
		};

		$app['request.remote_addr'] = function ( $app ) {
			return $app['request']->server->get( 'REMOTE_ADDR' );
		};
	}
}
