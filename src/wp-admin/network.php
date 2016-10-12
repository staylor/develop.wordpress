<?php
/**
 * Network installation administration panel.
 *
 * A multi-step process allowing the user to enable a network of WordPress sites.
 *
 * @since 3.0.0
 *
 * @package WordPress
 * @subpackage Administration
 */

const WP_INSTALLING_NETWORK = true;

use WP\Install\Admin\Help as InstallHelp;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! is_super_admin() ) {
	wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
}

if ( is_multisite() ) {
	if ( ! is_network_admin() ) {
		wp_redirect( network_admin_url( 'setup.php' ) );
		exit;
	}

	if ( ! defined( 'MULTISITE' ) ) {
		wp_die( __( 'The Network creation panel is not for WordPress MU networks.' ) );
	}
}

$wpdb = $app['db'];

require_once( __DIR__ . '/includes/network.php' );

// We need to create references to ms global tables to enable Network.
foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	$wpdb->$table = $prefixed_table;
}

if ( ! network_domain_check() && ( ! defined( 'WP_ALLOW_MULTISITE' ) || ! WP_ALLOW_MULTISITE ) ) {
	wp_die(
		printf(
			/* translators: 1: WP_ALLOW_MULTISITE 2: wp-config.php */
			__( 'You must define the %1$s constant as true in your %2$s file to allow creation of a Network.' ),
			'<code>WP_ALLOW_MULTISITE</code>',
			'<code>wp-config.php</code>'
		)
	);
}

if ( is_network_admin() ) {
	$title = __( 'Network Setup' );
	$parent_file = 'settings.php';
} else {
	$title = __( 'Create a Network of WordPress Sites' );
	$parent_file = 'tools.php';
}

( new InstallHelp( get_current_screen() ) )->addNetwork();

include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<?php
if ( 'POST' === $app['request.method'] ) {

	check_admin_referer( 'install-network-1' );

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	// Create network tables.
	install_network();
	$base              = parse_url( trailingslashit( get_option( 'home' ) ), PHP_URL_PATH );
	$subdomain_install = allow_subdomain_install() ? ! empty( $_post->get( 'subdomain_install' ) ) : false;
	if ( ! network_domain_check() ) {
		$result = populate_network( 1, get_clean_basedomain(), sanitize_email( $_post->get( 'email' ) ), wp_unslash( $_post->get( 'sitename' ) ), $base, $subdomain_install );
		if ( is_wp_error( $result ) ) {
			if ( 1 == count( $result->get_error_codes() ) && 'no_wildcard_dns' == $result->get_error_code() )
				network_step2( $result );
			else
				network_step1( $result );
		} else {
			network_step2();
		}
	} else {
		network_step2();
	}
} elseif ( is_multisite() || network_domain_check() ) {
	network_step2();
} else {
	network_step1();
}
?>
</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
