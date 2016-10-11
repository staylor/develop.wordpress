<?php
/**
 * Press This Display and Handler.
 *
 * @package WordPress
 * @subpackage Press_This
 */

const IFRAME_REQUEST = true;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( get_post_type_object( 'post' )->cap->create_posts ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to create posts as this user.' ) . '</p>',
		403
	);
}

$wp_press_this = new WP_Press_This();
$wp_press_this->html();
