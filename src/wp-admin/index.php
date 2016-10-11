<?php
/**
 * Dashboard Administration Screen
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Dashboard as DashboardView;

/** Load WordPress Bootstrap */
require_once( __DIR__ . '/admin.php' );

$view = new DashboardView( $app );

/** Load WordPress dashboard API */
require_once( __DIR__ . '/includes/dashboard.php' );

wp_dashboard_setup();

$view->enqueueIndexScripts();

$title = $view->l10n->dashboard;
$parent_file = 'index.php';

$view->help->addIndex();

$show_welcome_panel = has_action( 'welcome_panel' ) && current_user_can( 'edit_theme_options' );

$data = [
	'title' => $title,
	'show_welcome_panel' => $show_welcome_panel,
	'wp_dashboard' => $app->mute( 'wp_dashboard' ),
];

if ( $show_welcome_panel ) {
	$classes = 'welcome-panel';
	$option = (int) get_user_meta( get_current_user_id(), 'show_welcome_panel', true );
	// 0 = hide, 1 = toggled to show or single site creator, 2 = multisite site owner
	$hide = 0 === $option || ( 2 === $option && wp_get_current_user()->user_email !== get_option( 'admin_email' ) );
	if ( $hide ) {
		$classes .= ' hidden';
	}
	$data['classes'] = $classes;
	$data['nonce'] = wp_nonce_field( 'welcome-panel-nonce', 'welcomepanelnonce', false, false );
	$data['welcome_close_url'] = admin_url( '?welcome=0' );
}

require_once( __DIR__ . '/admin-header.php' );

$view->setData( $data );

$view->setActions( [
	/**
	 * Add content to the welcome panel on the admin dashboard.
	 *
	 * To remove the default welcome panel, use remove_action():
	 *
	 *     remove_action( 'welcome_panel', 'wp_welcome_panel' );
	 *
	 * @since 3.5.0
	 */
	'welcome_panel' => [],
] );

echo $view->render( 'dashboard/index', $view );

require_once( __DIR__ . '/admin-footer.php' );
