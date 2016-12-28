<?php
/**
 * Link Management Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Link\Admin\Help as LinkHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'manage_links' ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit the links for this site.' ) );
}

$wp_list_table = _get_list_table( 'WP_Links_List_Table' );

// Handle bulk deletes
$doaction = $wp_list_table->current_action();

if ( $doaction && $_request->get( 'linkcheck' ) ) {
	check_admin_referer( 'bulk-bookmarks' );

	$redirect_to = admin_url( 'link-manager.php' );
	$bulklinks = (array) $_request->get( 'linkcheck' );

	if ( 'delete' == $doaction ) {
		foreach ( $bulklinks as $link_id ) {
			$link_id = (int) $link_id;

			wp_delete_link( $link_id );
		}

		$redirect_to = add_query_arg( 'deleted', count( $bulklinks ), $redirect_to );
	} else {
		/** This action is documented in wp-admin/edit-comments.php */
		$redirect_to = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $redirect_to, $doaction, $bulklinks );
	}
	wp_redirect( $redirect_to );
	exit;
} elseif ( $_get->get( '_wp_http_referer' ) ) {
	 wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $app['request.uri'] ) ) );
	 exit;
}

$wp_list_table->prepare_items();

$app->set( 'title', __( 'Links' ) );
$this_file = 'link-manager.php';
$app->set( 'parent_file', 'link-manager.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

( new LinkHelp( get_current_screen() ) )->addManager();

include_once( ABSPATH . 'wp-admin/admin-header.php' );

if ( ! current_user_can( 'manage_links' ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit the links for this site.' ) );
}

?>

<div class="wrap nosubsub">
<h1 class="wp-heading-inline"><?php
echo esc_html( $app->get( 'title' ) );
?></h1>

<a href="link-add.php" class="page-title-action"><?php echo esc_html_x( 'Add New', 'link' ); ?></a>

<?php
if ( strlen( $_request->get( 's' ) ) ) {
	/* translators: %s: search keywords */
	printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( wp_unslash( $_request->get( 's' ) ) ) );
}
?>

<hr class="wp-header-end">

<?php
if ( $_request->get( 'deleted' ) ) {
	echo '<div id="message" class="updated notice is-dismissible"><p>';
	$deleted = (int) $_request->get( 'deleted' );
	printf(_n( '%s link deleted.', '%s links deleted', $deleted), $deleted);
	echo '</p></div>';
	$_server->set( 'REQUEST_URI', remove_query_arg(
		[ 'deleted' ],
		$app['request.uri']
	) );
}
?>

<form id="posts-filter" method="get">

<?php $wp_list_table->search_box( __( 'Search Links' ), 'link' ); ?>

<?php $wp_list_table->display(); ?>

<div id="ajax-response"></div>
</form>

</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
