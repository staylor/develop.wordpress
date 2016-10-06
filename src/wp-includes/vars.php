<?php
/**
 * Creates common globals for the rest of WordPress
 *
 * Sets $pagenow global which is the current page. Checks
 * for the browser to set which one is currently being used.
 *
 * Detects which user environment WordPress is being used on.
 * Only attempts to check for Apache, Nginx and IIS -- three web
 * servers with known pretty permalink capability.
 *
 * Note: Though Nginx is detected, WordPress does not currently
 * generate rewrite rules for it. See https://codex.wordpress.org/Nginx
 *
 * @package WordPress
 */

use function WP\getApp;

$app = getApp();
$ua = $app['request.useragent'];
$software = $app['request.software'];

global $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_edge;

// Simple browser detection
$is_lynx = $is_gecko = $is_winIE = $is_macIE = $is_opera = $is_NS4 = $is_safari = $is_chrome = $is_iphone = $is_edge = false;

if ( isset( $ua ) ) {
	if ( strpos( $ua, 'Lynx' ) !== false ) {
		$is_lynx = true;
	} elseif ( strpos( $ua, 'Edge' ) !== false ) {
		$is_edge = true;
	} elseif ( stripos( $ua, 'chrome' ) !== false ) {
		if ( stripos( $ua, 'chromeframe' ) !== false ) {
			$is_admin = is_admin();
			/**
			 * Filters whether Google Chrome Frame should be used, if available.
			 *
			 * @since 3.2.0
			 *
			 * @param bool $is_admin Whether to use the Google Chrome Frame. Default is the value of is_admin().
			 */
			$is_chrome = apply_filters( 'use_google_chrome_frame', $is_admin );
			if ( $is_chrome ) {
				header( 'X-UA-Compatible: chrome=1' );
			}
			$is_winIE = ! $is_chrome;
		} else {
			$is_chrome = true;
		}
	} elseif ( stripos( $ua, 'safari' ) !== false ) {
		$is_safari = true;
	} elseif ( ( strpos( $ua, 'MSIE' ) !== false || strpos( $ua, 'Trident' ) !== false ) && strpos( $ua, 'Win' ) !== false ) {
		$is_winIE = true;
	} elseif ( strpos( $ua, 'MSIE' ) !== false && strpos( $ua, 'Mac' ) !== false ) {
		$is_macIE = true;
	} elseif ( strpos( $ua, 'Gecko' ) !== false ) {
		$is_gecko = true;
	} elseif ( strpos( $ua, 'Opera' ) !== false ) {
		$is_opera = true;
	} elseif ( strpos( $ua, 'Nav' ) !== false && strpos( $ua, 'Mozilla/4.' ) !== false ) {
		$is_NS4 = true;
	}
}

if ( $is_safari && stripos( $ua, 'mobile' ) !== false ) {
	$is_iphone = true;
}

$app['is_IE'] = ( $is_macIE || $is_winIE );

// Server detection

/**
 * Whether the server software is Apache or something else
 */
$app['is_apache'] = (strpos( $software, 'Apache' ) !== false || strpos( $software, 'LiteSpeed' ) !== false);

/**
 * Whether the server software is Nginx or something else
 */
$app['is_nginx'] = ( strpos( $software, 'nginx' ) !== false );

/**
 * Whether the server software is IIS or something else
 */
$app['is_IIS'] = ! $app['is_apache'] && ( strpos( $software, 'Microsoft-IIS' ) !== false || strpos( $software, 'ExpressionDevServer' ) !== false );

/**
 * Whether the server software is IIS 7.X or greater
 */
$app['is_iis7'] = $app['is_IIS'] && intval( substr( $software, strpos( $software, 'Microsoft-IIS/' ) + 14 ) ) >= 7;

/**
 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
 *
 * @return bool
 */
function wp_is_mobile() {
	$app = getApp();
	$ua = $app['request.useragent'];

	if ( empty( $ua ) ) {
		$is_mobile = false;
	} elseif ( strpos( $ua, 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
		|| strpos( $ua, 'Android' ) !== false
		|| strpos( $ua, 'Silk/' ) !== false
		|| strpos( $ua, 'Kindle' ) !== false
		|| strpos( $ua, 'BlackBerry' ) !== false
		|| strpos( $ua, 'Opera Mini' ) !== false
		|| strpos( $ua, 'Opera Mobi' ) !== false ) {
			$is_mobile = true;
	} else {
		$is_mobile = false;
	}

	return $is_mobile;
}
