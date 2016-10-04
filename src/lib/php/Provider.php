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

		$app['scripts.factory'] = $app->factory( function () {
			return new Dependency\Scripts();
		} );
		$app['scripts.global'] = $app['scripts.factory'];

		$app['styles.factory'] = function () {
			return new Dependency\Styles();
		};
		$app['styles.global'] = $app['styles.factory'];
	}
}