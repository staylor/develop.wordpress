<?php
namespace WP\Dependency;

use Pimple\{Container,ServiceProviderInterface};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['scripts.factory'] = $app->factory( function () {
			return new Scripts();
		} );

		$app['scripts.global'] = function ( $app ) {
			return $app['scripts.factory'];
		};

		$app['scripts.compression'] = function () {
			return ( ini_get( 'zlib.output_compression' ) || 'ob_gzhandler' === ini_get( 'output_handler' ) );
		};

		$app['scripts.concat'] = function () {
			$concat = defined( 'CONCATENATE_SCRIPTS' ) ? CONCATENATE_SCRIPTS : true;
			if ( ( ! is_admin() && ! did_action( 'login_init' ) ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$concat = false;
			}

			return $concat;
		};

		$app['scripts.compress'] = function ( $app ) {
			$compress = defined( 'COMPRESS_SCRIPTS' ) ? COMPRESS_SCRIPTS : true;
			if ( $compress && ( ! get_site_option('can_compress_scripts') || $app['scripts.compression'] ) ) {
				$compress = false;
			}
			return $compress;
		};

		$app['styles.factory'] = $app->factory( function () {
			return new Styles();
		} );

		$app['styles.global'] = function ( $app ) {
			return $app['styles.factory'];
		};

		$app['styles.compress'] = function ( $app ) {
			$compress = defined( 'COMPRESS_CSS' ) ? COMPRESS_CSS : true;
			if ( $compress && ( ! get_site_option( 'can_compress_scripts' ) || $app['scripts.compression'] ) ) {
				$compress = false;
			}
			return $compress;
		};
	}
}