<?php
namespace WP;

use Pimple\{Container,ServiceProviderInterface};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['wp_version'] = function () {
			$version = '4.7-alpha-38178-src';
			// themes!
			$GLOBALS['wp_version'] = $version;
			return $version;
		};

		$app['wp_db_version'] = 38590;

		$app['wp_current_db_version'] = $app->factory( function () {
			if ( ! function_exists( '__get_option' ) ) {
				return 0;
			}
			return __get_option( 'db_version' );
		} );

		$app['tinymce_version'] = '4401-20160726';

		$app['required_php_version'] = '7.0';

		$app['required_mysql_version'] = '5.0';

		// for non-US English locales
		$app['wp_local_package'] = null;

		$app['locale.factory'] = $app->factory( function () {
			return new I18N\Locale();
		} );

		$app['locale'] = function ( $app ) {
			return $app['locale.factory'];
		};

		$app['rewrite.factory'] = $app->factory( function () {
			return new Rewrite\Rewrite();
		} );
		$app['rewrite'] = function ( $app ) {
			return $app['rewrite.factory'];
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

		$app['password.hasher'] = function () {
			return new \PasswordHash( 8, true );
		};
	}
}