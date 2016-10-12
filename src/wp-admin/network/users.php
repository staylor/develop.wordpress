<?php
/**
 * Multisite users administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */
use WP\User\Admin\Help as UserHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'manage_network_users' ) )
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

if ( $_get->get( 'action' ) ) {
	/** This action is documented in wp-admin/network/edit.php */
	do_action( 'wpmuadminedit' );

	switch ( $_get->get( 'action' ) ) {
		case 'deleteuser':
			if ( ! current_user_can( 'manage_network_users' ) )
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

			check_admin_referer( 'deleteuser' );

			$id = $_get->getInt( 'id' );
			if ( $id != '0' && $id != '1' ) {
				$_post->set( 'allusers', array( $id ) ); // confirm_delete_users() can only handle with arrays
				$title = __( 'Users' );
				$parent_file = 'users.php';
				require_once( ABSPATH . 'wp-admin/admin-header.php' );
				echo '<div class="wrap">';
				confirm_delete_users( $_post->get( 'allusers' ) );
				echo '</div>';
				require_once( ABSPATH . 'wp-admin/admin-footer.php' );
			} else {
				wp_redirect( network_admin_url( 'users.php' ) );
			}
			exit();

		case 'allusers':
			if ( !current_user_can( 'manage_network_users' ) )
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

			if ( ( $_post->get( 'action' ) || $_post->get( 'action2' ) ) && $_post->get( 'allusers' ) ) {
				check_admin_referer( 'bulk-users-network' );

				$doaction = $_post->get( 'action' ) != -1 ? $_post->get( 'action' ) : $_post->get( 'action2' );
				$userfunction = '';

				foreach ( (array) $_post->get( 'allusers' ) as $user_id ) {
					if ( !empty( $user_id ) ) {
						switch ( $doaction ) {
							case 'delete':
								if ( ! current_user_can( 'delete_users' ) )
									wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
								$title = __( 'Users' );
								$parent_file = 'users.php';
								require_once( ABSPATH . 'wp-admin/admin-header.php' );
								echo '<div class="wrap">';
								confirm_delete_users( $_post->get( 'allusers' ) );
								echo '</div>';
								require_once( ABSPATH . 'wp-admin/admin-footer.php' );
								exit();

							case 'spam':
								$user = get_userdata( $user_id );
								if ( is_super_admin( $user->ID ) )
									wp_die( sprintf( __( 'Warning! User cannot be modified. The user %s is a network administrator.' ), esc_html( $user->user_login ) ) );

								$userfunction = 'all_spam';
								$blogs = get_blogs_of_user( $user_id, true );
								foreach ( (array) $blogs as $details ) {
									if ( $details->userblog_id != $current_site->blog_id ) // main blog not a spam !
										update_blog_status( $details->userblog_id, 'spam', '1' );
								}
								update_user_status( $user_id, 'spam', '1' );
							break;

							case 'notspam':
								$userfunction = 'all_notspam';
								$blogs = get_blogs_of_user( $user_id, true );
								foreach ( (array) $blogs as $details )
									update_blog_status( $details->userblog_id, 'spam', '0' );

								update_user_status( $user_id, 'spam', '0' );
							break;
						}
					}
				}

				if ( ! in_array( $doaction, array( 'delete', 'spam', 'notspam' ), true ) ) {
					$sendback = wp_get_referer();

					$user_ids = (array) $_post->get( 'allusers' );
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
					 * @param array  $user_ids The users to take the action on.
					 */
					$sendback = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $sendback, $doaction, $user_ids );

					wp_safe_redirect( $sendback );
					exit();
				}

				wp_safe_redirect( add_query_arg( array( 'updated' => 'true', 'action' => $userfunction ), wp_get_referer() ) );
			} else {
				$location = network_admin_url( 'users.php' );

				$paged = $_request->getInt( 'paged', 0 );
				if ( $paged )
					$location = add_query_arg( 'paged', $paged, $location );
				wp_redirect( $location );
			}
			exit();

		case 'dodelete':
			check_admin_referer( 'ms-users-delete' );
			if ( ! ( current_user_can( 'manage_network_users' ) && current_user_can( 'delete_users' ) ) )
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

			if ( $_post->get( 'blog' ) && is_array( $_post->get( 'blog' ) ) ) {
				foreach ( $_post->get( 'blog' ) as $id => $users ) {
					foreach ( $users as $blogid => $user_id ) {
						if ( ! current_user_can( 'delete_user', $id ) )
							continue;

						$d = $_post->get( 'delete' );
						if ( $d && 'reassign' == $d[ $blogid ][ $id ] )
							remove_user_from_blog( $id, $blogid, $user_id );
						else
							remove_user_from_blog( $id, $blogid );
					}
				}
			}
			$i = 0;
			if ( is_array( $_post->get( 'user' ) ) && ! empty( $_post->get( 'user' ) ) )
				foreach ( $_post->get( 'user' ) as $id ) {
					if ( ! current_user_can( 'delete_user', $id ) )
						continue;
					wpmu_delete_user( $id );
					$i++;
				}

			if ( $i == 1 )
				$deletefunction = 'delete';
			else
				$deletefunction = 'all_delete';

			wp_redirect( add_query_arg( array( 'updated' => 'true', 'action' => $deletefunction ), network_admin_url( 'users.php' ) ) );
			exit();
	}
}

$wp_list_table = _get_list_table('WP_MS_Users_List_Table');
$pagenum = $wp_list_table->get_pagenum();
$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}
$title = __( 'Users' );
$parent_file = 'users.php';

add_screen_option( 'per_page' );

( new UserHelp( get_current_screen() ) )->addMultisiteUsers();

require_once( ABSPATH . 'wp-admin/admin-header.php' );

if ( $_request->get( 'updated' ) == 'true' && ! empty( $_request->get( 'action' ) ) ) {
	?>
	<div id="message" class="updated notice is-dismissible"><p>
		<?php
		switch ( $_request->get( 'action' ) ) {
		case 'delete':
			_e( 'User deleted.' );
			break;
		case 'all_spam':
			_e( 'Users marked as spam.' );
			break;
		case 'all_notspam':
			_e( 'Users removed from spam.' );
			break;
		case 'all_delete':
			_e( 'Users deleted.' );
			break;
		case 'add':
			_e( 'User added.' );
			break;
		}
		?>
	</p></div>
	<?php
}
	?>
<div class="wrap">
	<h1><?php esc_html_e( 'Users' );
	if ( current_user_can( 'create_users') ) : ?>
		<a href="<?php echo network_admin_url('user-new.php'); ?>" class="page-title-action"><?php echo esc_html_x( 'Add New', 'user' ); ?></a><?php
	endif;

	if ( strlen( $usersearch ) ) {
		/* translators: %s: search keywords */
		printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $usersearch ) );
	}
	?>
	</h1>

	<?php $wp_list_table->views(); ?>

	<form method="get" class="search-form">
		<?php $wp_list_table->search_box( __( 'Search Users' ), 'all-user' ); ?>
	</form>

	<form id="form-user-list" action="users.php?action=allusers" method="post">
		<?php $wp_list_table->display(); ?>
	</form>
</div>

<?php require_once( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
