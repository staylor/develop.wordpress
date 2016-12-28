<?php
/**
 * Install plugin administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */
require_once( dirname( __DIR__ ) . '/vendor/autoload.php' );
use WP\Install\Admin\Help as InstallHelp;

$app = \WP\getApp();
$_get = $app['request']->query;

// TODO route this pages via a specific iframe handler instead of the do_action below
if (
	! defined( 'IFRAME_REQUEST' ) &&
	$_get->get( 'tab' ) &&
	'plugin-information' === $_get->get( 'tab' )
) {
	define( 'IFRAME_REQUEST', true );
}

/**
 * WordPress Administration Bootstrap.
 */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'install_plugins' ) ) {
	wp_die( __( 'Sorry, you are not allowed to install plugins on this site.' ) );
}

if ( is_multisite() && ! is_network_admin() ) {
	wp_redirect( network_admin_url( 'plugin-install.php' ) );
	exit();
}

$wp_list_table = _get_list_table( 'WP_Plugin_Install_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

if ( $_request->get( '_wp_http_referer' ) ) {
	$location = remove_query_arg( '_wp_http_referer', wp_unslash( $app['request.uri'] ) );

	$paged = $_request->getInt( 'paged' );
	if ( $paged ) {
		$location = add_query_arg( 'paged', $paged, $location );
	}

	wp_redirect( $location );
	exit;
}

$wp_list_table->prepare_items();

$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

$app->set( 'title', __( 'Add Plugins' ) );
$app->set( 'parent_file', 'plugins.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

wp_enqueue_script( 'plugin-install' );
if ( 'plugin-information' != $tab ) {
	add_thickbox();
}

$body_id = $tab;

wp_enqueue_script( 'updates' );

/**
 * Fires before each tab on the Install Plugins screen is loaded.
 *
 * The dynamic portion of the action hook, `$tab`, allows for targeting
 * individual tabs, for instance 'install_plugins_pre_plugin-information'.
 *
 * @since 2.7.0
 */
do_action( "install_plugins_pre_{$tab}" );

/*
 * Call the pre upload action on every non-upload plugin install screen
 * because the form is always displayed on these screens.
 */
if ( 'upload' !== $tab ) {
	/** This action is documented in wp-admin/plugin-install.php */
	do_action( 'install_plugins_pre_upload' );
}

( new InstallHelp( get_current_screen() ) )->addPluginInstall();

/**
 * WordPress Administration Template Header.
 */
include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap <?php echo esc_attr( "plugin-install-tab-$tab" ); ?>">
<h1 class="wp-heading-inline"><?php
echo esc_html( $app->get( 'title' ) );
?></h1>

<?php
if ( ! empty( $tabs['upload'] ) && current_user_can( 'upload_plugins' ) ) {
	printf( ' <a href="%s" class="upload-view-toggle page-title-action"><span class="upload">%s</span><span class="browse">%s</span></a>',
		( 'upload' === $tab ) ? self_admin_url( 'plugin-install.php' ) : self_admin_url( 'plugin-install.php?tab=upload' ),
		__( 'Upload Plugin' ),
		__( 'Browse Plugins' )
	);
}
?>

<hr class="wp-header-end">

<?php
/*
 * Output the upload plugin form on every non-upload plugin install screen, so it can be
 * displayed via JavaScript rather then opening up the devoted upload plugin page.
 */
if ( 'upload' !== $tab ) {
	?>
	<div class="upload-plugin-wrap">
		<?php
		/** This action is documented in wp-admin/plugin-install.php */
		do_action( 'install_plugins_upload' );
		?>
	</div>
	<?php
	$wp_list_table->views();
	echo '<br class="clear" />';
}

/**
 * Fires after the plugins list table in each tab of the Install Plugins screen.
 *
 * The dynamic portion of the action hook, `$tab`, allows for targeting
 * individual tabs, for instance 'install_plugins_plugin-information'.
 *
 * @since 2.7.0
 *
 * @param int $paged The current page number of the plugins list table.
 */
do_action( "install_plugins_{$tab}", $paged ); ?>

	<span class="spinner"></span>
</div>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();

/**
 * WordPress Administration Template Footer.
 */
include( ABSPATH . 'wp-admin/admin-footer.php' );
