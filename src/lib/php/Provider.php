<?php
namespace WP;

use Pimple\{Container,ServiceProviderInterface};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['wp_version'] = '4.7-alpha-38178-src';
		$app['wp_db_version'] = 38590;
		$app['tinymce_version'] = '4401-20160726';
		$app['required_php_version'] = '7.0';
		$app['required_mysql_version'] = '5.0';

		$app['db'] = function () {
			return $GLOBALS['wpdb'];
		};

		$app['roles'] = function () {
			return new User\Roles();
		};

		$app['scripts.factory'] = $app->factory( function () {
			return new Dependency\Scripts();
		} );
		$app['scripts.global'] = function ( $app ) {
			return $app['scripts.factory'];
		};

		$app['styles.factory'] = $app->factory( function () {
			return new Dependency\Styles();
		} );
		$app['styles.global'] = function ( $app ) {
			return $app['styles.factory'];
		};
	}
}