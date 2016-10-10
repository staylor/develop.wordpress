<?php
/**
 * Edit Comments Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Comment as CommentView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit comments.' ) . '</p>',
		403
	);
}

$view = new CommentView( $app );

$wp_list_table = _get_list_table( 'WP_Comments_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

$doaction = $wp_list_table->current_action();

if ( $doaction ) {

	$view->handler->doBulkComments( $doaction, $pagenum );

} elseif ( $view->_get->get( '_wp_http_referer' ) ) {
	$location = remove_query_arg(
		[ '_wp_http_referer', '_wpnonce' ],
		wp_unslash( $app['request.uri'] )
	);
	 wp_redirect( $location );
	 exit;
}

$wp_list_table->prepare_items();

wp_enqueue_script( 'admin-comments' );
enqueue_comment_hotkeys_js();

if ( $post_id ) {
	$comments_count = wp_count_comments( $post_id );
	$draft_or_post_title = wp_html_excerpt( _draft_or_post_title( $post_id ), 50, '&hellip;' );
	if ( $comments_count->moderated > 0 ) {
		/* translators: 1: comments count 2: post title */
		$title = sprintf( __( 'Comments (%1$s) on &#8220;%2$s&#8221;' ),
			number_format_i18n( $comments_count->moderated ),
			$draft_or_post_title
		);
	} else {
		/* translators: %s: post title */
		$title = sprintf( __( 'Comments on &#8220;%s&#8221;' ),
			$draft_or_post_title
		);
	}
} else {
	$comments_count = wp_count_comments();
	if ( $comments_count->moderated > 0 ) {
		/* translators: %s: comments count */
		$title = sprintf( __( 'Comments (%s)' ),
			number_format_i18n( $comments_count->moderated )
		);
	} else {
		$title = __( 'Comments' );
	}
}

add_screen_option( 'per_page' );

$view->help->addEditComments();

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1><?php
if ( $post_id ) {
	/* translators: %s: link to post */
	printf( __( 'Comments on &#8220;%s&#8221;' ),
		sprintf( '<a href="%1$s">%2$s</a>',
			get_edit_post_link( $post_id ),
			wp_html_excerpt( _draft_or_post_title( $post_id ), 50, '&hellip;' )
		)
	);
} else {
	_e( 'Comments' );
}

if ( strlen( $view->_request->get( 's' ) ) ) {
	echo '<span class="subtitle">';
	/* translators: %s: search keywords */
	printf( __( 'Search results for &#8220;%s&#8221;' ),
		wp_html_excerpt( esc_html( wp_unslash( $view->_request->get( 's' ) ) ), 50, '&hellip;' )
	);
	echo '</span>';
}
?></h1>

<?php
$error = $view->_request->getInt( 'error' );
if ( $error ) {
	$error_msg = '';
	switch ( $error ) {
	case 1 :
		$error_msg = __( 'Invalid comment ID.' );
		break;

	case 2 :
		$error_msg = __( 'Sorry, you are not allowed to edit comments on this post.' );
		break;
	}
	if ( $error_msg )
		echo '<div id="moderated" class="error"><p>' . $error_msg . '</p></div>';
}

$messages = $view->getEditMessages();

echo '<div id="moderated" class="updated notice is-dismissible"><p>' . implode( "<br/>\n", $messages ) . '</p></div>';
?>

<?php $wp_list_table->views(); ?>

<form id="comments-form" method="get">

<?php $wp_list_table->search_box( __( 'Search Comments' ), 'comment' ); ?>

<?php if ( $post_id ) : ?>
<input type="hidden" name="p" value="<?php echo esc_attr( intval( $post_id ) ); ?>" />
<?php endif; ?>
<input type="hidden" name="comment_status" value="<?php echo esc_attr($comment_status); ?>" />
<input type="hidden" name="pagegen_timestamp" value="<?php echo esc_attr(current_time( 'mysql', 1)); ?>" />

<input type="hidden" name="_total" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'total_items' ) ); ?>" />
<input type="hidden" name="_per_page" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'per_page' ) ); ?>" />
<input type="hidden" name="_page" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'page' ) ); ?>" />

<?php
$paged = $view->_request->getInt( 'paged' );
if ( $paged ) { ?>
	<input type="hidden" name="paged" value="<?php echo esc_attr( $paged ); ?>" />
<?php } ?>

<?php $wp_list_table->display(); ?>
</form>
</div>

<div id="ajax-response"></div>

<?php
wp_comment_reply( '-1', true, 'detail' );
wp_comment_trashnotice();

include( ABSPATH . 'wp-admin/admin-footer.php' );
