<?php
/**
 * Install theme network administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */

require_once( dirname( __DIR__ ) . '/vendor/autoload.php' );

$app = \WP\getApp();
$_get = $app['request']->query;

if ( $_get->get( 'tab' ) && 'theme-information' === $_get->get( 'tab' ) ) {
	define( 'IFRAME_REQUEST', true );
}

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

require( ABSPATH . 'wp-admin/theme-install.php' );
