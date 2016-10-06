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
			return new Dependency\Styles();
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

		$app['password.hasher'] = function () {
			return new \PasswordHash( 8, true );
		};

		$app['widget_factory'] = function () {
			return new Widget\Factory();
		};

		$app['wp'] = function () {
			return new Controller\WP();
		};

		$app['customize'] = function () {
			return new Customize\Manager();
		};

		$app['rest.server'] = function () {
			$wp_rest_server_class = apply_filters( 'wp_rest_server_class', '\WP_REST_Server' );
			$wp_rest_server = new $wp_rest_server_class;

			do_action( 'rest_api_init', $wp_rest_server );
			return $wp_rest_server;
		};

		$app['admin_bar'] = function () {
			$admin_bar_class = apply_filters( 'wp_admin_bar_class', '\WP_Admin_Bar' );
			if ( class_exists( $admin_bar_class ) ) {
				return new $admin_bar_class;
			}
			return false;
		};

		$app['pagenow'] = function ( $app ) {
			$self_matches = null;

			// On which page are we ?
			if ( is_admin() ) {
				// wp-admin pages are checked more carefully
				if ( is_network_admin() ) {
					preg_match( '#/wp-admin/network/?(.*?)$#i', $app['request.php_self'], $self_matches );
				} elseif ( is_user_admin() ) {
					preg_match( '#/wp-admin/user/?(.*?)$#i', $app['request.php_self'], $self_matches );
				} else {
					preg_match( '#/wp-admin/?(.*?)$#i', $app['request.php_self'], $self_matches );
				}

				$pagenow = trim( $self_matches[1], '/' );
				$pagenow = preg_replace( '#\?.*?$#', '', $pagenow );
				if ( '' === $pagenow || 'index' === $pagenow || 'index.php' === $pagenow ) {
					$pagenow = 'index.php';
				} else {
					preg_match( '#(.*?)(/|$)#', $pagenow, $self_matches );
					$pagenow = strtolower( $self_matches[1] );
					if ( '.php' !== substr( $pagenow, -4, 4) ) {
						$pagenow .= '.php'; // for Options +Multiviews: /wp-admin/themes/index.php (themes.php is queried)
					}
				}
			} elseif ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $app['request.php_self'], $self_matches ) ) {
				$pagenow = strtolower( $self_matches[1] );
			} else {
				$pagenow = 'index.php';
			}

			return $pagenow;
		};
	}
}