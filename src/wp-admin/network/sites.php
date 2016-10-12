<?php
/**
 * Multisite sites administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */
use WP\Site\Admin\Help as SiteHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'manage_sites' ) )
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

$wp_list_table = _get_list_table( 'WP_MS_Sites_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

$title = __( 'Sites' );
$parent_file = 'sites.php';

add_screen_option( 'per_page' );

( new SiteHelp( get_current_screen() ) )->addMain();

$id = $_request->getInt( 'id', 0 );

if ( $_get->get( 'action' ) ) {
	/** This action is documented in wp-admin/network/edit.php */
	do_action( 'wpmuadminedit' );

	// A list of valid actions and their associated messaging for confirmation output.
	$manage_actions = array(
		'activateblog'   => __( 'You are about to activate the site %s.' ),
		'deactivateblog' => __( 'You are about to deactivate the site %s.' ),
		'unarchiveblog'  => __( 'You are about to unarchive the site %s.' ),
		'archiveblog'    => __( 'You are about to archive the site %s.' ),
		'unspamblog'     => __( 'You are about to unspam the site %s.' ),
		'spamblog'       => __( 'You are about to mark the site %s as spam.' ),
		'deleteblog'     => __( 'You are about to delete the site %s.' ),
		'unmatureblog'   => __( 'You are about to mark the site %s as mature.' ),
		'matureblog'     => __( 'You are about to mark the site %s as not mature.' ),
	);

	if ( 'confirm' === $_get->get( 'action' ) ) {
		// The action2 parameter contains the action being taken on the site.
		$site_action = $_get->get( 'action2' );

		if ( ! array_key_exists( $site_action, $manage_actions ) ) {
			wp_die( __( 'The requested action is not valid.' ) );
		}

		// The mature/unmature UI exists only as external code. Check the "confirm" nonce for backward compatibility.
		if ( 'matureblog' === $site_action || 'unmatureblog' === $site_action ) {
			check_admin_referer( 'confirm' );
		} else {
			check_admin_referer( $site_action . '_' . $id );
		}

		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
		}

		if ( $current_site->blog_id == $id ) {
			wp_die( __( 'Sorry, you are not allowed to change the current site.' ) );
		}

		$site_details = get_blog_details( $id );
		$site_address = untrailingslashit( $site_details->domain . $site_details->path );

		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		?>
			<div class="wrap">
				<h1><?php _e( 'Confirm your action' ); ?></h1>
				<form action="sites.php?action=<?php echo esc_attr( $site_action ); ?>" method="post">
					<input type="hidden" name="action" value="<?php echo esc_attr( $site_action ); ?>" />
					<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
					<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wp_get_referer() ); ?>" />
					<?php wp_nonce_field( $site_action . '_' . $id, '_wpnonce', false ); ?>
					<p><?php echo sprintf( $manage_actions[ $site_action ], $site_address ); ?></p>
					<?php submit_button( __( 'Confirm' ), 'primary' ); ?>
				</form>
			</div>
		<?php
		require_once( ABSPATH . 'wp-admin/admin-footer.php' );
		exit();
	} elseif ( array_key_exists( $_get->get( 'action' ), $manage_actions ) ) {
		$action = $_get->get( 'action' );
		check_admin_referer( $action . '_' . $id );
	} elseif ( 'allblogs' === $_get->get( 'action' ) ) {
		check_admin_referer( 'bulk-sites' );
	}

	$updated_action = '';

	switch ( $_get->get( 'action' ) ) {

		case 'deleteblog':
			if ( ! current_user_can( 'delete_sites' ) )
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), '', array( 'response' => 403 ) );

			$updated_action = 'not_deleted';
			if ( $id != '0' && $id != $current_site->blog_id && current_user_can( 'delete_site', $id ) ) {
				wpmu_delete_blog( $id, true );
				$updated_action = 'delete';
			}
		break;

		case 'allblogs':
			if ( $_post->get( 'action' ) || $_post->get( 'action2' ) && $_post->get( 'allblogs' ) ) {
				$doaction = $_post->get( 'action' ) != -1 ? $_post->get( 'action' ) : $_post->get( 'action2' );

				foreach ( (array) $_post->get( 'allblogs' ) as $key => $val ) {
					if ( $val != '0' && $val != $current_site->blog_id ) {
						switch ( $doaction ) {
							case 'delete':
								if ( ! current_user_can( 'delete_site', $val ) )
									wp_die( __( 'Sorry, you are not allowed to delete the site.' ) );

								$updated_action = 'all_delete';
								wpmu_delete_blog( $val, true );
							break;

							case 'spam':
							case 'notspam':
								$updated_action = ( 'spam' === $doaction ) ? 'all_spam' : 'all_notspam';
								update_blog_status( $val, 'spam', ( 'spam' === $doaction ) ? '1' : '0' );
							break;
						}
					} else {
						wp_die( __( 'Sorry, you are not allowed to change the current site.' ) );
					}
				}
				if ( ! in_array( $doaction, array( 'delete', 'spam', 'notspam' ), true ) ) {
					$redirect_to = wp_get_referer();
					$blogs = (array) $_post->get( 'allblogs' );
					/**
					 * Fires when a custom bulk action should be handled.
					 *
					 * The redirect link should be modified with success or failure feedback
					 * from the action to be used to display feedback to the user.
					 *
					 * @since 4.7.0
					 *
					 * @param string $redirect_to The redirect URL.
					 * @param string $doaction      The action being taken.
					 * @param array  $blogs       The blogs to take the action on.
					 * @param int    $site_id     The current site id.
					 */
					$redirect_to = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $redirect_to, $doaction, $blogs, $id );
					wp_safe_redirect( $redirect_to );
					exit();
				}
			} else {
				$location = network_admin_url( 'sites.php' );
				$paged = $_request->get( 'paged', 0 );
				if ( $paged ) {
					$location = add_query_arg( 'paged', $paged, $location );
				}
				wp_redirect( $location );
				exit();
			}
		break;

		case 'archiveblog':
		case 'unarchiveblog':
			update_blog_status( $id, 'archived', ( 'archiveblog' === $_get->get( 'action' ) ) ? '1' : '0' );
		break;

		case 'activateblog':
			update_blog_status( $id, 'deleted', '0' );

			/**
			 * Fires after a network site is activated.
			 *
			 * @since MU
			 *
			 * @param string $id The ID of the activated site.
			 */
			do_action( 'activate_blog', $id );
		break;

		case 'deactivateblog':
			/**
			 * Fires before a network site is deactivated.
			 *
			 * @since MU
			 *
			 * @param string $id The ID of the site being deactivated.
			 */
			do_action( 'deactivate_blog', $id );
			update_blog_status( $id, 'deleted', '1' );
		break;

		case 'unspamblog':
		case 'spamblog':
			update_blog_status( $id, 'spam', ( 'spamblog' === $_get->get( 'action' ) ) ? '1' : '0' );
		break;

		case 'unmatureblog':
		case 'matureblog':
			update_blog_status( $id, 'mature', ( 'matureblog' === $_get->get( 'action' ) ) ? '1' : '0' );
		break;
	}

	if ( empty( $updated_action ) && array_key_exists( $_get->get( 'action' ), $manage_actions ) ) {
		$updated_action = $_get->get( 'action' );
	}

	if ( ! empty( $updated_action ) ) {
		wp_safe_redirect( add_query_arg( array( 'updated' => $updated_action ), wp_get_referer() ) );
		exit();
	}
}

$msg = '';
if ( $_get->get( 'updated' ) ) {
	switch ( $_get->get( 'updated' ) ) {
	case 'all_notspam':
		$msg = __( 'Sites removed from spam.' );
		break;
	case 'all_spam':
		$msg = __( 'Sites marked as spam.' );
		break;
	case 'all_delete':
		$msg = __( 'Sites deleted.' );
		break;
	case 'delete':
		$msg = __( 'Site deleted.' );
		break;
	case 'not_deleted':
		$msg = __( 'Sorry, you are not allowed to delete that site.' );
		break;
	case 'archiveblog':
		$msg = __( 'Site archived.' );
		break;
	case 'unarchiveblog':
		$msg = __( 'Site unarchived.' );
		break;
	case 'activateblog':
		$msg = __( 'Site activated.' );
		break;
	case 'deactivateblog':
		$msg = __( 'Site deactivated.' );
		break;
	case 'unspamblog':
		$msg = __( 'Site removed from spam.' );
		break;
	case 'spamblog':
		$msg = __( 'Site marked as spam.' );
		break;
	default:
		/**
		 * Filters a specific, non-default site-updated message in the Network admin.
		 *
		 * The dynamic portion of the hook name, `$_GET['updated']`, refers to the
		 * non-default site update action.
		 *
		 * @since 3.1.0
		 *
		 * @param string $msg The update message. Default 'Settings saved'.
		 */
		$msg = apply_filters( 'network_sites_updated_message_' . $_get->get( 'updated' ), __( 'Settings saved.' ) );
		break;
	}

	if ( ! empty( $msg ) )
		$msg = '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
}

$wp_list_table->prepare_items();

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1><?php _e( 'Sites' ); ?>

<?php if ( current_user_can( 'create_sites') ) : ?>
	<a href="<?php echo network_admin_url('site-new.php'); ?>" class="page-title-action"><?php echo esc_html_x( 'Add New', 'site' ); ?></a>
<?php endif; ?>

<?php
if ( strlen( $_request->get( 's' ) ) ) {
	/* translators: %s: search keywords */
	printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $s ) );
} ?>
</h1>

<?php echo $msg; ?>

<form method="get" id="ms-search">
<?php $wp_list_table->search_box( __( 'Search Sites' ), 'site' ); ?>
<input type="hidden" name="action" value="blogs" />
</form>

<form id="form-site-list" action="sites.php?action=allblogs" method="post">
	<?php $wp_list_table->display(); ?>
</form>
</div>
<?php

require_once( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
