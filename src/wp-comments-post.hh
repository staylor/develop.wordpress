<?php
/**
 * Handles Comment Post to WordPress and prevents duplicate comment posting.
 *
 * @package WordPress
 */

require __DIR__ . '/vendor/autoload.php';
$app = WP\getApp();
$_get = $app['request']->query;
$_server = $app['request']->server;

if ( 'POST' !== $app['request.method'] ) {
	$protocol = $_server->get( 'SERVER_PROTOCOL' );
	if ( ! in_array( $protocol, [ 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ] ) ) {
		$protocol = 'HTTP/1.0';
	}

	header( 'Allow: POST' );
	header( $protocol . ' 405 Method Not Allowed' );
	header( 'Content-Type: text/plain' );
	exit;
}

/** Sets up the WordPress Environment. */
require( __DIR__ . '/wp-load.hh' );

nocache_headers();

$comment = wp_handle_comment_submission( wp_unslash( $_post->all() ) );
if ( is_wp_error( $comment ) ) {
	$data = intval( $comment->get_error_data() );
	if ( ! empty( $data ) ) {
		wp_die( '<p>' . $comment->get_error_message() . '</p>', __( 'Comment Submission Failure' ), array( 'response' => $data, 'back_link' => true ) );
	} else {
		exit;
	}
}

$user = wp_get_current_user();

/**
 * Perform other actions when comment cookies are set.
 *
 * @since 3.4.0
 *
 * @param WP_Comment   $comment Comment object.
 * @param WP\User\User $user    User object. The user may not exist.
 */
do_action( 'set_comment_cookies', $comment, $user );

$location = empty( $_post->get( 'redirect_to' ) ) ?
	get_comment_link( $comment ) : $_post->get( 'redirect_to' ) . '#comment-' . $comment->comment_ID;

/**
 * Filters the location URI to send the commenter after posting.
 *
 * @since 2.0.5
 *
 * @param string     $location The 'redirect_to' URI sent via $_POST.
 * @param WP_Comment $comment  Comment object.
 */
$redirect = apply_filters( 'comment_post_redirect', $location, $comment );

wp_safe_redirect( $redirect );
exit;