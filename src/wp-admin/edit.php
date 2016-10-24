<?php
/**
 * Edit Posts Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Post as PostView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! $typenow ) {
	wp_die( __( 'Invalid post type.' ) );
}

if ( ! in_array( $typenow, get_post_types( array( 'show_ui' => true ) ) ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit posts in this post type.' ) );
}

if ( 'attachment' === $typenow && wp_redirect( admin_url( 'upload.php' ) ) ) {
	exit();
}

$post_type_object = get_post_type_object( $typenow );

if ( ! $post_type_object ) {
	wp_die( __( 'Invalid post type.' ) );
}

if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit posts in this post type.' ) . '</p>',
		403
	);
}

$view = new PostView( $app );

$wpdb = $app['db'];

$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

// Back-compat for viewing comments of an entry
foreach ( [ 'p', 'attachment_id', 'page_id' ] as $_redirect ) {
	$value = $view->_request->getInt( $_redirect );
	if ( $value ) {
		wp_redirect( admin_url( 'edit-comments.php?p=' . $value ) );
		exit;
	}
}

if ( 'post' !== $typenow ) {
	$app->set( 'parent_file', "edit.php?post_type={$typenow}" );
	$app->set( 'submenu_file', "edit.php?post_type={$typenow}" );
	$post_new_file = "post-new.php?post_type={$typenow}";
} else {
	$app->set( 'parent_file', 'edit.php' );
	$app->set( 'submenu_file', 'edit.php' );
	$post_new_file = 'post-new.php';
}
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

$doaction = $wp_list_table->current_action();

if ( $doaction ) {
	check_admin_referer( 'bulk-posts' );

	$sendback = remove_query_arg(
		[ 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ],
		wp_get_referer()
	);
	if ( ! $sendback ) {
		$sendback = admin_url( $app->get( 'parent_file' ) );
	}
	$sendback = add_query_arg( 'paged', $pagenum, $sendback );
	if ( strpos( $sendback, 'post.php' ) !== false ) {
		$sendback = admin_url( $post_new_file);
	}

	if ( 'delete_all' == $doaction ) {
		// Prepare for deletion of all posts with a specified post status (i.e. Empty trash).
		$post_status = preg_replace( '/[^a-z0-9_-]+/i', '', $view->_request->get( 'post_status' ) );
		// Validate the post status exists.
		if ( get_post_status_object( $post_status ) ) {
			$sql = "SELECT ID FROM $wpdb->posts WHERE post_type=%s AND post_status = %s";
			$post_ids = $wpdb->get_col( $wpdb->prepare( $sql, $typenow, $post_status ) );
		}
		$doaction = 'delete';
	} elseif ( $view->_request->get( 'media' ) ) {
		$post_ids = $view->_request->get( 'media' );
	} elseif ( $view->_request->get( 'ids' ) ) {
		$post_ids = explode( ',', $view->_request->get( 'ids' ) );
	} elseif ( $view->_request->get( 'post' ) ) {
		$post_ids = array_map( 'intval', $view->_request->get( 'post' ) );
	}

	if ( ! isset( $post_ids ) ) {
		wp_redirect( $sendback );
		exit;
	}

	switch ( $doaction ) {
	case 'trash':
		$trashed = $locked = 0;

		foreach ( (array) $post_ids as $post_id ) {
			if ( !current_user_can( 'delete_post', $post_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to move this item to the Trash.' ) );
			}

			if ( wp_check_post_lock( $post_id ) ) {
				$locked++;
				continue;
			}

			if ( !wp_trash_post( $post_id ) ) {
				wp_die( __( 'Error in moving to Trash.' ) );
			}

			$trashed++;
		}

		$sendback = add_query_arg( array( 'trashed' => $trashed, 'ids' => join( ',', $post_ids), 'locked' => $locked ), $sendback );
		break;
	case 'untrash':
		$untrashed = 0;
		foreach ( (array) $post_ids as $post_id ) {
			if ( !current_user_can( 'delete_post', $post_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to restore this item from the Trash.' ) );
			}

			if ( !wp_untrash_post( $post_id ) ) {
				wp_die( __( 'Error in restoring from Trash.' ) );
			}

			$untrashed++;
		}
		$sendback = add_query_arg( 'untrashed', $untrashed, $sendback);
		break;
	case 'delete':
		$deleted = 0;
		foreach ( (array) $post_ids as $post_id ) {
			$post_del = get_post( $post_id );

			if ( !current_user_can( 'delete_post', $post_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to delete this item.' ) );
			}

			if ( $post_del->post_type == 'attachment' ) {
				if ( ! wp_delete_attachment( $post_id ) ) {
					wp_die( __( 'Error in deleting.' ) );
				}
			} else {
				if ( !wp_delete_post( $post_id ) ) {
					wp_die( __( 'Error in deleting.' ) );
				}
			}
			$deleted++;
		}
		$sendback = add_query_arg( 'deleted', $deleted, $sendback);
		break;
	case 'edit':
		if ( $view->_request->get( 'bulk_edit' ) ) {
			$done = bulk_edit_posts( $view->_request->all() );

			if ( is_array( $done) ) {
				$done['updated'] = count( $done['updated'] );
				$done['skipped'] = count( $done['skipped'] );
				$done['locked'] = count( $done['locked'] );
				$sendback = add_query_arg( $done, $sendback );
			}
		}
		break;
	default:
		/**
		 * Fires when a custom bulk action should be handled.
		 *
		 * The sendback link should be modified with success or failure feedback
		 * from the action to be used to display feedback to the user.
		 *
		 * @since 4.7.0
		 *
		 * @param string $sendback The redirect URL.
		 * @param string $doaction The action being taken.
		 * @param array  $post_ids The post IDs to take the action on.
		 */
		$sendback = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $sendback, $doaction, $post_ids );
		break;
	}

	$sendback = remove_query_arg( array( 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ), $sendback );

	wp_redirect( $sendback);
	exit();
} elseif ( $view->_request->get( '_wp_http_referer' ) ) {
	 wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $app['request.uri'] ) ) );
	 exit;
}

$wp_list_table->prepare_items();

wp_enqueue_script( 'inline-edit-post' );
wp_enqueue_script( 'heartbeat' );

$app->set( 'title', $post_type_object->labels->name );

if ( 'post' == $typenow ) {

	$view->help->addPost();

} elseif ( 'page' == $typenow ) {

	$view->help->addPage();

}

get_current_screen()->set_screen_reader_content( [
	'heading_views'      => $post_type_object->labels->filter_items_list,
	'heading_pagination' => $post_type_object->labels->items_list_navigation,
	'heading_list'       => $post_type_object->labels->items_list,
] );

add_screen_option( 'per_page', [ 'default' => 20, 'option' => 'edit_' . $typenow . '_per_page' ] );

$bulk_counts = [
	'updated'   => $view->_request->getInt( 'updated', 0 ),
	'locked'    => $view->_request->getInt( 'locked', 0 ),
	'deleted'   => $view->_request->getInt( 'deleted', 0 ),
	'trashed'   => $view->_request->getInt( 'trashed', 0 ),
	'untrashed' => $view->_request->getInt( 'untrashed', 0 ),
];

$bulk_messages = [];
$bulk_messages['post'] = array(
	'updated'   => _n( '%s post updated.', '%s posts updated.', $bulk_counts['updated'] ),
	'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 post not updated, somebody is editing it.' ) :
	                   _n( '%s post not updated, somebody is editing it.', '%s posts not updated, somebody is editing them.', $bulk_counts['locked'] ),
	'deleted'   => _n( '%s post permanently deleted.', '%s posts permanently deleted.', $bulk_counts['deleted'] ),
	'trashed'   => _n( '%s post moved to the Trash.', '%s posts moved to the Trash.', $bulk_counts['trashed'] ),
	'untrashed' => _n( '%s post restored from the Trash.', '%s posts restored from the Trash.', $bulk_counts['untrashed'] ),
);
$bulk_messages['page'] = array(
	'updated'   => _n( '%s page updated.', '%s pages updated.', $bulk_counts['updated'] ),
	'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 page not updated, somebody is editing it.' ) :
	                   _n( '%s page not updated, somebody is editing it.', '%s pages not updated, somebody is editing them.', $bulk_counts['locked'] ),
	'deleted'   => _n( '%s page permanently deleted.', '%s pages permanently deleted.', $bulk_counts['deleted'] ),
	'trashed'   => _n( '%s page moved to the Trash.', '%s pages moved to the Trash.', $bulk_counts['trashed'] ),
	'untrashed' => _n( '%s page restored from the Trash.', '%s pages restored from the Trash.', $bulk_counts['untrashed'] ),
);

/**
 * Filters the bulk action updated messages.
 *
 * By default, custom post types use the messages for the 'post' post type.
 *
 * @since 3.7.0
 *
 * @param array $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
 *                             keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
 * @param array $bulk_counts   Array of item counts for each message, used to build internationalized strings.
 */
$bulk_messages = apply_filters( 'bulk_post_updated_messages', $bulk_messages, $bulk_counts );
$bulk_counts = array_filter( $bulk_counts );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap">
<h1><?php
echo esc_html( $post_type_object->labels->name );
if ( current_user_can( $post_type_object->cap->create_posts ) ) {
	echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="page-title-action">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
}

if ( strlen( $view->_request->get( 's' ) ) ) {
	/* translators: %s: search keywords */
	printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
}
?></h1>

<?php
// If we have a bulk message to issue:
$messages = [];
foreach ( $bulk_counts as $message => $count ) {
	if ( isset( $bulk_messages[ $typenow ][ $message ] ) ) {
		$messages[] = sprintf( $bulk_messages[ $typenow ][ $message ], number_format_i18n( $count ) );
	} elseif ( isset( $bulk_messages['post'][ $message ] ) ) {
		$messages[] = sprintf( $bulk_messages['post'][ $message ], number_format_i18n( $count ) );
	}

	if ( $message == 'trashed' && $view->_request->get( 'ids' ) ) {
		$ids = preg_replace( '/[^0-9,]/', '', $view->_request->get( 'ids' ) );
		$url = "edit.php?post_type={$typenow}&doaction=undo&action=untrash&ids=$ids";
		$messages[] = '<a href="' . esc_url( wp_nonce_url( $url, "bulk-posts" ) ) . '">' . __( 'Undo' ) . '</a>';
	}
}

if ( $messages ) {
	echo '<div id="message" class="updated notice is-dismissible"><p>' . join( ' ', $messages ) . '</p></div>';
}
unset( $messages );

$_server->set( 'REQUEST_URI', remove_query_arg(
	[ 'locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed' ],
	$app['request.uri']
) );
?>

<?php $wp_list_table->views(); ?>

<form id="posts-filter" method="get">

<?php $wp_list_table->search_box( $post_type_object->labels->search_items, 'post' ); ?>

<input type="hidden" name="post_status" class="post_status_page" value="<?php echo $view->_request->get( 'post_status', 'all' ) ?>" />
<input type="hidden" name="post_type" class="post_type_page" value="<?php echo $typenow; ?>" />
<?php if ( $view->_request->get( 'show_sticky' ) ) { ?>
<input type="hidden" name="show_sticky" value="1" />
<?php } ?>

<?php $wp_list_table->display(); ?>

</form>

<?php
if ( $wp_list_table->has_items() ) {
	$wp_list_table->inline_edit();
}
?>

<div id="ajax-response"></div>
<br class="clear" />
</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
