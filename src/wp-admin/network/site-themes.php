<?php
/**
 * Edit Site Themes Administration Screen
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */
use WP\Site\Admin\Help as SiteHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'manage_sites' ) ) {
	wp_die( __( 'Sorry, you are not allowed to manage themes for this site.' ) );
}

( new SiteHelp( get_current_screen() ) )->addThemes();

$wp_list_table = _get_list_table('WP_MS_Themes_List_Table');

$action = $wp_list_table->current_action();

$s = $_request->get( 's', '' );

// Clean up request URI from temporary args for screen options/paging uri's to work as expected.
$temp_args = array( 'enabled', 'disabled', 'error' );
$_server->set( 'REQUEST_URI', remove_query_arg( $temp_args, $app['request.uri'] ) );
$referer = remove_query_arg( $temp_args, wp_get_referer() );

$paged = $_request->getInt( 'paged', 0 );
if ( $paged ) {
	$referer = add_query_arg( 'paged', $paged, $referer );
}

$id = $_request->getInt( 'id', 0 );

if ( ! $id ) {
	wp_die( __('Invalid site ID.') );
}

$wp_list_table->prepare_items();

$details = get_site( $id );
if ( ! $details ) {
	wp_die( __( 'The requested site does not exist.' ) );
}

if ( !can_edit_network( $details->site_id ) ) {
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
}

$is_main_site = is_main_site( $id );

if ( $action ) {
	switch_to_blog( $id );
	$allowed_themes = get_option( 'allowedthemes' );

	switch ( $action ) {
	case 'enable':
		check_admin_referer( 'enable-theme_' . $_get->get( 'theme' ) );
		$theme = $_get->get( 'theme' );
		$action = 'enabled';
		$n = 1;
		if ( ! $allowed_themes ) {
			$allowed_themes = array( $theme => true );
		} else {
			$allowed_themes[$theme] = true;
		}
		break;
	case 'disable':
		check_admin_referer( 'disable-theme_' . $_get->get( 'theme' ) );
		$theme = $_get->get( 'theme' );
		$action = 'disabled';
		$n = 1;
		if ( !$allowed_themes ) {
			$allowed_themes = [];
		} else {
			unset( $allowed_themes[$theme] );
		}
		break;
	case 'enable-selected':
		check_admin_referer( 'bulk-themes' );
		if ( $_post->get( 'checked' ) ) {
			$themes = (array) $_post->get( 'checked' );
			$action = 'enabled';
			$n = count( $themes );
			foreach ( (array) $themes as $theme ) {
				$allowed_themes[ $theme ] = true;
			}
		} else {
			$action = 'error';
			$n = 'none';
		}
		break;
	case 'disable-selected':
		check_admin_referer( 'bulk-themes' );
		if ( $_post->get( 'checked' ) ) {
			$themes = (array) $_post->get( 'checked' );
			$action = 'disabled';
			$n = count( $themes );
			foreach ( (array) $themes as $theme ) {
				unset( $allowed_themes[ $theme ] );
			}
		} else {
			$action = 'error';
			$n = 'none';
		}
		break;
	default:
		if ( $_post->get( 'checked' ) ) {
			check_admin_referer( 'bulk-themes' );
			$themes = (array) $_post->get( 'checked' );
			$n = count( $themes );
			/**
			 * Fires when a custom bulk action should be handled.
			 *
			 * The redirect link should be modified with success or failure feedback
			 * from the action to be used to display feedback to the user.
			 *
			 * @since 4.7.0
			 *
			 * @param string $referer The redirect URL.
			 * @param string $action  The action being taken.
			 * @param array  $themes  The themes to take the action on.
			 * @param int    $site_id The current site id
			 */
			$referer = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $referer, $action, $themes, $id );
		} else {
			$action = 'error';
			$n = 'none';
		}
	}

	update_option( 'allowedthemes', $allowed_themes );
	restore_current_blog();

	wp_safe_redirect( add_query_arg( array( 'id' => $id, $action => $n ), $referer ) );
	exit;
}

if ( 'update-site' === $_get->get( 'action' ) ) {
	wp_safe_redirect( $referer );
	exit();
}

add_thickbox();
add_screen_option( 'per_page' );

/* translators: %s: site name */
$app->set( 'title', sprintf( __( 'Edit Site: %s' ), esc_html( $details->blogname ) ) );

$app->set( 'parent_file', 'sites.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );
$app->set( 'submenu_file', 'sites.php' );

require( ABSPATH . 'wp-admin/admin-header.php' ); ?>

<div class="wrap">
<h1 id="edit-site"><?php echo $app->get( 'title' ); ?></h1>
<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>
<?php

network_edit_site_nav( array(
	'blog_id'  => $id,
	'selected' => 'site-themes'
) );

if ( $_get->get( 'enabled' ) ) {
	$enabled = $_get->getInt( 'enabled' );
	if ( 1 == $enabled ) {
		$message = __( 'Theme enabled.' );
	} else {
		$message = _n( '%s theme enabled.', '%s themes enabled.', $enabled );
	}
	echo '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $enabled ) ) . '</p></div>';
} elseif ( $_get->get( 'disabled' ) ) {
	$disabled = $_get->getInt( 'disabled' );
	if ( 1 == $disabled ) {
		$message = __( 'Theme disabled.' );
	} else {
		$message = _n( '%s theme disabled.', '%s themes disabled.', $disabled );
	}
	echo '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $disabled ) ) . '</p></div>';
} elseif ( 'none' == $_get->get( 'error' ) ) {
	echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'No theme selected.' ) . '</p></div>';
} ?>

<p><?php _e( 'Network enabled themes are not shown on this screen.' ) ?></p>

<form method="get">
<?php $wp_list_table->search_box( __( 'Search Installed Themes' ), 'theme' ); ?>
<input type="hidden" name="id" value="<?php echo esc_attr( $id ) ?>" />
</form>

<?php $wp_list_table->views(); ?>

<form method="post" action="site-themes.php?action=update-site">
	<input type="hidden" name="id" value="<?php echo esc_attr( $id ) ?>" />

<?php $wp_list_table->display(); ?>

</form>

</div>
<?php include(ABSPATH . 'wp-admin/admin-footer.php'); ?>
