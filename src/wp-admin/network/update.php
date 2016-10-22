<?php
/**
 * Update/Install Plugin/Theme network administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */

require_once( dirname( __DIR__ ) . '/vendor/autoload.php' );

$app = \WP\getApp();
$_get = $app['request']->query;

if (
	$_get->get( 'action' ) &&
	in_array( $_get->get( 'action' ), [
		'update-selected',
		'activate-plugin',
		'update-selected-themes'
	] )
) {
	define( 'IFRAME_REQUEST', true );
}

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

require( ABSPATH . 'wp-admin/update.php' );
