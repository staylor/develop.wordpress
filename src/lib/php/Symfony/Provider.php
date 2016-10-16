<?php
namespace WP\Symfony;

use Pimple\{Container,ServiceProviderInterface};
use Symfony\Component\HttpFoundation\{Request,Response,RedirectResponse};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['response'] = function () {
			return new Response();
		};

		$app['response.redirect'] = function () {
			return new RedirectResponse();
		};

		$app['request'] = function () {
			$request = Request::createFromGlobals();

			// use attributes prop for "_REQUEST" superglobal
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

		// WordPress awesomely directly alters this at times
		$app['request.uri'] = $app->factory( function ( $app ) {
			return $app['request']->server->get( 'REQUEST_URI' );
		} );

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
