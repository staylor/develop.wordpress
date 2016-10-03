<?php
namespace WP;

use Pimple\{Container,ServiceProviderInterface};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['db'] = function () {
			return $GLOBALS['wpdb'];
		};

		$app['roles'] = function () {
			return new User\Roles();
		};
	}
}