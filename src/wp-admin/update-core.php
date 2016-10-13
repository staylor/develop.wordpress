<?php
/**
 * Update Core administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Install\Admin\Help as InstallHelp;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

wp_enqueue_style( 'plugin-install' );
wp_enqueue_script( 'plugin-install' );
wp_enqueue_script( 'updates' );
add_thickbox();

if ( is_multisite() && ! is_network_admin() ) {
	wp_redirect( network_admin_url( 'update-core.php' ) );
	exit();
}

if ( ! current_user_can( 'update_core' ) && ! current_user_can( 'update_themes' ) && ! current_user_can( 'update_plugins' ) )
	wp_die( __( 'Sorry, you are not allowed to update this site.' ) );

require_once( __DIR__ . '/includes/update-core-functions.php' );

$action = $_get->get( 'action', 'upgrade-core' );

$upgrade_error = false;
if ( ( 'do-theme-upgrade' == $action || ( 'do-plugin-upgrade' == $action && ! $_get->get( 'plugins' ) ) )
	&& ! isset( $_post->get( 'checked' ) ) ) {
	$upgrade_error = $action == 'do-theme-upgrade' ? 'themes' : 'plugins';
	$action = 'upgrade-core';
}

$app->title = __('WordPress Updates');
$app->parent_file = 'index.php';
$app->current_screen->set_parentage( $app->parent_file );

( new InstallHelp( $app->current_screen ) )->addUpdateCore();

if ( 'upgrade-core' == $action ) {
	// Force a update check when requested
	$force_check = ! empty( $_get->get( 'force-check' ) );
	wp_version_check( [], $force_check );

	require_once(ABSPATH . 'wp-admin/admin-header.php');
	?>
	<div class="wrap">
	<h1><?php _e( 'WordPress Updates' ); ?></h1>
	<?php
	if ( $upgrade_error ) {
		echo '<div class="error"><p>';
		if ( $upgrade_error == 'themes' )
			_e('Please select one or more themes to update.');
		else
			_e('Please select one or more plugins to update.');
		echo '</p></div>';
	}

	$last_update_check = false;
	$current = get_site_transient( 'update_core' );

	if ( $current && isset ( $current->last_checked ) )	{
		$last_update_check = $current->last_checked + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
	}

	echo '<p>';
	/* translators: %1 date, %2 time. */
	printf( __( 'Last checked on %1$s at %2$s.' ), date_i18n( __( 'F j, Y' ), $last_update_check ), date_i18n( __( 'g:i a' ), $last_update_check ) );
	echo ' &nbsp; <a class="button" href="' . esc_url( self_admin_url('update-core.php?force-check=1') ) . '">' . __( 'Check Again' ) . '</a>';
	echo '</p>';

	if ( $core = current_user_can( 'update_core' ) )
		core_upgrade_preamble();
	if ( $plugins = current_user_can( 'update_plugins' ) )
		list_plugin_updates();
	if ( $themes = current_user_can( 'update_themes' ) )
		list_theme_updates();
	if ( $core || $plugins || $themes )
		list_translation_updates();
	unset( $core, $plugins, $themes );
	/**
	 * Fires after the core, plugin, and theme update tables.
	 *
	 * @since 2.9.0
	 */
	do_action( 'core_upgrade_preamble' );
	echo '</div>';
	include(ABSPATH . 'wp-admin/admin-footer.php');

} elseif ( 'do-core-upgrade' == $action || 'do-core-reinstall' == $action ) {

	if ( ! current_user_can( 'update_core' ) )
		wp_die( __( 'Sorry, you are not allowed to update this site.' ) );

	check_admin_referer('upgrade-core');

	// Do the (un)dismiss actions before headers, so that they can redirect.
	if ( $_post->get( 'dismiss' ) )
		do_dismiss_core_update();
	elseif ( $_post->get( 'undismiss' ) )
		do_undismiss_core_update();

	require_once(ABSPATH . 'wp-admin/admin-header.php');
	if ( 'do-core-reinstall' == $action )
		$reinstall = true;
	else
		$reinstall = false;

	if ( $_post->get( 'upgrade' ) )
		do_core_upgrade($reinstall);

	include(ABSPATH . 'wp-admin/admin-footer.php');

} elseif ( 'do-plugin-upgrade' == $action ) {

	if ( ! current_user_can( 'update_plugins' ) )
		wp_die( __( 'Sorry, you are not allowed to update this site.' ) );

	check_admin_referer('upgrade-core');

	if ( $_get->get( 'plugins' ) ) {
		$plugins = explode( ',', $_get->get( 'plugins' ) );
	} elseif ( $_post->get( 'checked' ) ) {
		$plugins = (array) $_post->get( 'checked' );
	} else {
		wp_redirect( admin_url('update-core.php') );
		exit;
	}

	$url = 'update.php?action=update-selected&plugins=' . urlencode(implode(',', $plugins));
	$url = wp_nonce_url($url, 'bulk-update-plugins');

	$app->title = __('Update Plugins');

	require_once(ABSPATH . 'wp-admin/admin-header.php');
	echo '<div class="wrap">';
	echo '<h1>' . __( 'Update Plugins' ) . '</h1>';
	echo '<iframe src="', $url, '" style="width: 100%; height: 100%; min-height: 750px;" frameborder="0" title="' . esc_attr__( 'Update progress' ) . '"></iframe>';
	echo '</div>';
	include(ABSPATH . 'wp-admin/admin-footer.php');

} elseif ( 'do-theme-upgrade' == $action ) {

	if ( ! current_user_can( 'update_themes' ) )
		wp_die( __( 'Sorry, you are not allowed to update this site.' ) );

	check_admin_referer('upgrade-core');

	if ( $_get->get( 'themes' ) ) {
		$themes = explode( ',', $_get->get( 'themes' ) );
	} elseif ( $_post->get( 'checked' ) ) {
		$themes = (array) $_post->get( 'checked' );
	} else {
		wp_redirect( admin_url('update-core.php') );
		exit;
	}

	$url = 'update.php?action=update-selected-themes&themes=' . urlencode(implode(',', $themes));
	$url = wp_nonce_url($url, 'bulk-update-themes');

	$app->title = __('Update Themes');

	require_once(ABSPATH . 'wp-admin/admin-header.php');
	?>
	<div class="wrap">
		<h1><?php _e( 'Update Themes' ); ?></h1>
		<iframe src="<?php echo $url ?>" style="width: 100%; height: 100%; min-height: 750px;" frameborder="0" title="<?php esc_attr_e( 'Update progress' ); ?>"></iframe>
	</div>
	<?php
	include(ABSPATH . 'wp-admin/admin-footer.php');

} elseif ( 'do-translation-upgrade' == $action ) {

	if ( ! current_user_can( 'update_core' ) && ! current_user_can( 'update_plugins' ) && ! current_user_can( 'update_themes' ) )
		wp_die( __( 'Sorry, you are not allowed to update this site.' ) );

	check_admin_referer( 'upgrade-translations' );

	require_once( ABSPATH . 'wp-admin/admin-header.php' );

	$url = 'update-core.php?action=do-translation-upgrade';
	$nonce = 'upgrade-translations';
	$app->title = __( 'Update Translations' );
	$context = WP_LANG_DIR;

	$upgrader = new Language_Pack_Upgrader( new Language_Pack_Upgrader_Skin( compact( 'url', 'nonce', 'title', 'context' ) ) );
	$result = $upgrader->bulk_upgrade();

	require_once( ABSPATH . 'wp-admin/admin-footer.php' );

} else {
	/**
	 * Fires for each custom update action on the WordPress Updates screen.
	 *
	 * The dynamic portion of the hook name, `$action`, refers to the
	 * passed update action. The hook fires in lieu of all available
	 * default update actions.
	 *
	 * @since 3.2.0
	 */
	do_action( "update-core-custom_{$action}" );
}
