<?php
/**
 * Multisite administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

use WP\Dashboard\Help as DashboardHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

/** Load WordPress dashboard API */
require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );

if ( ! current_user_can( 'manage_network' ) )
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );

$app->title = __( 'Dashboard' );
$app->parent_file = 'index.php';
$app->current_screen->set_parentage( $app->parent_file );

( new DashboardHelp( get_current_screen() ) )->addMultisiteIndex();

wp_dashboard_setup();

wp_enqueue_script( 'dashboard' );
wp_enqueue_script( 'plugin-install' );
add_thickbox();

require_once( ABSPATH . 'wp-admin/admin-header.php' );

?>

<div class="wrap">
<h1><?php echo esc_html( $app->title ); ?></h1>

<div id="dashboard-widgets-wrap">

<?php wp_dashboard(); ?>

<div class="clear"></div>
</div><!-- dashboard-widgets-wrap -->

</div><!-- wrap -->

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
