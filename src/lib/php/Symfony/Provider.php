<?php
namespace WP\Symfony;

use Pimple\{Container,ServiceProviderInterface};
use Symfony\Component\HttpFoundation\Request;

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['request'] = function () {
			return Request::createFromGlobals();
		};
	}
}
