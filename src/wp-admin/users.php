<?php
/**
 * User administration panel
 *
 * @package WordPress
 * @subpackage Administration
 * @since 1.0.0
 */
use WP\Error;
use WP\Admin\View\User as UserView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'list_users' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to browse users.' ) . '</p>',
		403
	);
}

$view = new UserView( $app );

$wp_list_table = _get_list_table( 'WP_Users_List_Table' );
$app->set( 'title', __( 'Users' ) );
$app->set( 'parent_file', 'users.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

add_screen_option( 'per_page' );

$view->help->addUsers();

if ( empty( $view->_request->all() ) ) {
	$referer = '<input type="hidden" name="wp_http_referer" value="'. esc_attr( wp_unslash( $app['request.uri'] ) ) . '" />';
} elseif ( $view->_request->has( 'wp_http_referer' ) ) {
	$redirect = remove_query_arg(
		[ 'wp_http_referer', 'updated', 'delete_count' ],
		wp_unslash( $view->_request->get( 'wp_http_referer' ) )
	);
	$referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr( $redirect ) . '" />';
} else {
	$redirect = 'users.php';
	$referer = '';
}

$update = '';
$errors = [];

switch ( $wp_list_table->current_action() ) {

/* Bulk Dropdown menu Role changes */
case 'promote':

	$view->handler->doPromoteUsers( $redirect );

	break;

case 'dodelete':

	$view->handler->doDeleteOrReassign( $redirect );

	break;

case 'delete':
	if ( is_multisite() ) {
		wp_die( __( 'User deletion is not allowed from this screen.' ) );
	}

	check_admin_referer( 'bulk-users' );

	if ( empty( $view->_request->get( 'users' ) ) && empty( $view->_request->get( 'user' ) ) ) {
		wp_redirect( $redirect );
		exit();
	}

	if ( ! current_user_can( 'delete_users' ) ) {
		$error = new Error( 'edit_users', __( 'You can&#8217;t delete users.' ) );
		$errors[] = [
			'class' => 'error',
			'label' => __( 'ERROR:' ),
			'message' => $error->get_error_message(),
		];
	}

	if ( empty( $view->_request->get( 'users' ) ) ) {
		$userids = [ $view->_request->getInt( 'user' ) ];
	} else {
		$userids = array_map( 'intval', (array) $view->_request->get( 'users' ) );
	}

	$users_have_content = false;
	$wpdb = $app['db'];
	if (
		$wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_author IN( " . implode( ',', $userids ) . " ) LIMIT 1" ) ||
		$wpdb->get_var( "SELECT link_id FROM {$wpdb->links} WHERE link_owner IN( " . implode( ',', $userids ) . " ) LIMIT 1" )
	) {
		$users_have_content = true;
	}

	if ( $users_have_content ) {
		add_action( 'admin_head', 'delete_users_add_js' );
	}

	if ( $this->_request->get( 'error' ) ) {
		$errors[] = [
			'class' => 'error',
			'label' => __( 'ERROR:' ),
			'message' => __( 'Please select an option.' ),
		];
	}

	$delete = 0;
	$list_items = [];
	foreach ( $userids as $id ) {
		$user = get_userdata( $id );
		if ( $id == $current_user->ID ) {
			/* translators: 1: user id, 2: user login */
			$list_items[] = sprintf(
				__( 'ID #%1$s: %2$s <strong>The current user will not be deleted.</strong>' ),
				$id,
				$user->user_login
			);
		} else {
			/* translators: 1: user id, 2: user login */
			$list_items[] = "<input type=\"hidden\" name=\"users[]\" value=\"" . esc_attr( $id ) . "\" />" .
				sprintf( __( 'ID #%1$s: %2$s' ), $id, $user->user_login );
			$delete++;
		}
	}

	$data = [
		'nonce' => wp_nonce_field( 'delete-users', '_wpnonce', true, false ),
		'referer' => $referer,
		'errors' => $errors,
		'one_user' => 1 === count( $userids ),
		'one_delete' => 1 === $delete,
		'list_items' => $list_items,
		'delete' => $delete,
		'users_have_content' => $users_have_content,
	];

	if ( $delete ) {
		$data['users_dropdown'] = wp_dropdown_users( [
			'name' => 'reassign_user',
			'exclude' => array_diff( $userids, [ $current_user->ID ] ),
			'show' => 'display_name_with_login',
			'echo' => 0,
		] );
		$data['submit_button'] = get_submit_button( __( 'Confirm Deletion' ), 'primary' );
	}

	$view->setData( $data );

	$view->setActions( [
		/**
		 * Fires at the end of the delete users form prior to the confirm button.
		 *
		 * @since 4.0.0
		 * @since 4.5.0 The `$userids` parameter was added.
		 *
		 * @param WP\User\User $current_user User object for the current user.
		 * @param array        $userids      Array of IDs for users being deleted.
		 */
		'delete_user_form' => [ $current_user, $userids ]
	] );

	echo $view->render( 'user/delete-form', $view );

	break;

case 'doremove':

	$view->handler->doDoRemove( $redirect );

	break;

case 'remove':

	check_admin_referer( 'bulk-users' );

	if ( ! is_multisite() ) {
		wp_die( __( 'You can&#8217;t remove users.' ) );
	}

	if ( empty( $view->_request->get( 'users' ) ) && empty( $view->_request->get( 'user' ) ) ) {
		wp_redirect( $redirect );
		exit();
	}

	if ( ! current_user_can( 'remove_users' ) ) {
		$error = new Error( 'edit_users', __( 'You can&#8217;t remove users.' ) );
	}

	if ( empty( $_request->get( 'users' ) ) ) {
		$userids = [ $_request->getInt( 'user' ) ];
	} else {
		$userids = array_map( 'intval', (array) $view->_request->get( 'users' ) );
	}

	$list_items = [];
	$remove = false;
 	foreach ( $userids as $id ) {
		$id = (int) $id;
 		$user = get_userdata( $id );
		if ( $id == $current_user->ID && ! is_super_admin() ) {
			/* translators: 1: user id, 2: user login */
			$list_items[] = sprintf(
				__( 'ID #%1$s: %2$s <strong>The current user will not be removed.</strong>' ),
				$id,
				$user->user_login
			);
		} elseif ( !current_user_can( 'remove_user', $id ) ) {
			/* translators: 1: user id, 2: user login */
			$list_items[] = sprintf(
				__( 'ID #%1$s: %2$s <strong>Sorry, you are not allowed to remove this user.</strong>' ),
				$id,
				$user->user_login
			);
		} else {
			/* translators: 1: user id, 2: user login */
			$list_items[] = "<input type=\"hidden\" name=\"users[]\" value=\"{$id}\" />" .
				sprintf( __( 'ID #%1$s: %2$s' ), $id, $user->user_login );
			$remove = true;
		}
 	}

	$data = [
		'nonce' => wp_nonce_field( 'remove-users', '_wpnonce', true, false  ),
		'referer' => $referer,
		'one_user' => 1 === count( $userids ),
		'list_items' => $list_items,
		'remove' => $remove,
	];

	if ( $remove ) {
		$data['submit_button'] = get_submit_button( __( 'Confirm Removal' ), 'primary' );
	}

	$view->setData( $data );

	echo $view->render( 'user/remove-form', $view );

	break;
}

if ( ! empty( $view->_get->get( '_wp_http_referer' ) ) ) {
	$location = remove_query_arg(
		[ '_wp_http_referer', '_wpnonce' ],
		wp_unslash( $app['request.uri'] )
	);
	wp_redirect( $location );
	exit;
}

if ( $wp_list_table->current_action() && ! empty( $view->_request->get( 'users' ) ) ) {
	$userids = $view->_request->get( 'users' );

	/**
	 * Fires when a custom bulk action should be handled.
	 *
	 * The sendback link should be modified with success or failure feedback
	 * from the action to be used to display feedback to the user.
	 *
	 * @since 4.7.0
	 *
	 * @param string $sendback The redirect URL.
	 * @param string $action   The action being taken.
	 * @param array  $userids  The users to take the action on.
	 */
	$sendback = apply_filters(
		'handle_bulk_actions-' . get_current_screen()->id,
		wp_get_referer(),
		$wp_list_table->current_action(),
		$userids
	);

	wp_safe_redirect( $sendback );
	exit();
}

$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
if ( $wp_list_table->get_pagenum() > $total_pages && $total_pages > 0 ) {
	$location = add_query_arg( 'paged', $total_pages );
	wp_redirect( $location );
	exit();
}

$data = [
	'messages' => $view->getMessages(),
	'errors' => $errors,
	'title' => $app->get( 'title' ),
];

if ( current_user_can( 'create_users' ) ) {
	$data['title_link_url'] = admin_url( 'user-new.php' );
	$data['title_link_text'] = $view->l10n->add_new;
} elseif ( is_multisite() && current_user_can( 'promote_users' ) ) {
	$data['title_link_url'] = admin_url( 'user-new.php' );
	$data['title_link_text'] = $view->l10n->add_existing;
}

if ( strlen( $usersearch ) ) {
	/* translators: %s: search keywords */
	$data['usersearch'] = sprintf(  __( 'Search results for &#8220;%s&#8221;' ), esc_html( $usersearch ) );
}

$data['list_table_views'] = $app->mute( function () use ( $wp_list_table )  {
	$wp_list_table->views();
} );

$data['list_table_search_box'] = $app->mute( function () use ( $wp_list_table )  {
	$wp_list_table->search_box( __( 'Search Users' ), 'user' );
} );

$data['list_table_display'] = $app->mute( function () use ( $wp_list_table )  {
	$wp_list_table->display();
} );

$view->setData( $data );

echo $view->render( 'user/list', $view );
