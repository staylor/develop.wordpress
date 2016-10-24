<?php
/**
 * Media Library administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Media as MediaView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
}

$view = new MediaView( $app );

$mode = get_user_option( 'media_library_mode', get_current_user_id() ) ?
	get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
$modes = ['grid', 'list' ];

$_mode = $view->_get->get( 'mode' );
if ( $_mode && in_array( $_mode, $modes ) ) {
	$mode = $_mode;
	update_user_option( get_current_user_id(), 'media_library_mode', $mode );
}

if ( 'grid' === $mode ) {
	wp_enqueue_media();
	wp_enqueue_script( 'media-grid' );
	wp_enqueue_script( 'media' );

	remove_action( 'admin_head', 'wp_admin_canonical_url' );

	$q = $view->_get->all();
	// let JS handle this
	unset( $q['s'] );
	$vars = wp_edit_attachments_query_vars( $q );
	$ignore = [ 'mode', 'post_type', 'post_status', 'posts_per_page' ];
	foreach ( $vars as $key => $value ) {
		if ( ! $value || in_array( $key, $ignore ) ) {
			unset( $vars[ $key ] );
		}
	}

	wp_localize_script( 'media-grid', '_wpMediaGridSettings', [
		'adminUrl' => parse_url( self_admin_url(), PHP_URL_PATH ),
		'queryVars' => (object) $vars
	] );

	$view->help->addUploadGrid();

	$app->set( 'title', __( 'Media Library' ) );
	$app->set( 'parent_file', 'upload.php' );
	$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

	$data = [
		'_admin_search_query' => $app->mute( '_admin_search_query' ),
		'js_error' => sprintf(
			/* translators: %s: list view URL */
			__( 'The grid view for the Media Library requires JavaScript. <a href="%s">Switch to the list view</a>.' ),
			'upload.php?mode=list'
		)
	];

	if ( current_user_can( 'upload_files' ) ) {
		$data['title_link_url'] = admin_url( 'media-new.php' );
	}

	$view->setData( $data );

	echo $view->render( 'media/upload-grid', $view );

	exit();
}

$wp_list_table = _get_list_table( 'WP_Media_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

// Handle bulk actions
$doaction = $wp_list_table->current_action();

if ( $doaction ) {
	check_admin_referer( 'bulk-media' );

	$post_ids = null;
	if ( 'delete_all' === $doaction ) {
		$wpdb = $app['db'];
		$sql = "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_status = 'trash'";
		$post_ids = $wpdb->get_col( $sql );
		$doaction = 'delete';
	} elseif ( $view->_request->get( 'media' ) ) {
		$post_ids = $view->_request->get( 'media' );
	} elseif ( $view->_request->get( 'ids' ) ) {
		$post_ids = explode( ',', $view->_request->get( 'ids' ) );
	}

	$location = 'upload.php';
	$referer = wp_get_referer();
	if ( $referer ) {
		if ( false !== strpos( $referer, 'upload.php' ) ) {
			$location = remove_query_arg(
				[ 'trashed', 'untrashed', 'deleted', 'message', 'ids', 'posted' ],
				$referer
			);
		}
	}

	switch ( $doaction ) {
	case 'detach':

		wp_media_attach_action( $this->_request->get( 'parent_post_id' ), 'detach' );

		break;

	case 'attach':

		wp_media_attach_action( $this->_request->get( 'found_post_id' ) );

		break;

	case 'trash':

		$view->handler->doTrash( $location, $post_ids );

		break;

	case 'untrash':

		$view->handler->doUntrash( $location, $post_ids );

		break;

	case 'delete':

		$view->handler->doDelete( $location, $post_ids );

		break;

	default:

		$view->handler->doBulkActions( get_current_screen()->id, $location, $doaction, $post_ids );

		break;
	}
} elseif ( $view->_get->get( '_wp_http_referer' ) ) {
	 wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $app['request.uri'] ) ) );
	 exit();
}

$wp_list_table->prepare_items();

$app->set( 'title', __( 'Media Library' ) );
$app->set( 'parent_file', 'upload.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

wp_enqueue_script( 'media' );

add_screen_option( 'per_page' );

$view->help->addUploadList();

$data = [
	'title' => $app->get( 'title' ),
	'message' => $view->getListMessage(),
];

if ( current_user_can( 'upload_files' ) ) {
	$data['title_link_url'] = admin_url( 'media-new.php' );
}

if ( strlen( $view->_request->get( 's' ) ) ) {
	/* translators: %s: search keywords */
	$data['search'] = sprintf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
}

$data['list_table_views'] = $app->mute( function () use ( $wp_list_table ) {
	$wp_list_table->views();
} );

$data['list_table_display'] = $app->mute( function () use ( $wp_list_table ) {
	$wp_list_table->display();
} );

$data['find_posts_div'] = $app->mute( function () {
	find_posts_div();
} );

$view->setData( $data );

echo $view->render( 'media/upload-list', $view );
