<?php
/**
 * Your Rights administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

$title = __( 'Freedoms' );

$view = new View( $app );

list( $display_version ) = explode( '-', $app['wp_version'] );
$plugins_url = current_user_can( 'activate_plugins' ) ? admin_url( 'plugins.php' ) : $view->l10n->dotorg_plugins_url;
$themes_url = current_user_can( 'switch_themes' ) ? admin_url( 'themes.php' ) : $view->l10n->dotorg_themes_url;

$data = [
	'welcome_text' => sprintf( $view->l10n->welcome, $display_version ),
	'about_text' => sprintf( $view->l10n->about, $display_version ),
	'version_text' => sprintf( $view->l10n->version, $display_version ),
	'license_text' => sprintf( $view->l10n->license, $plugins_url, $themes_url, 'https://wordpress.org/about/license/' ),
	'trademark_text' => sprintf( $view->l10n->trademark_policy, 'http://wordpressfoundation.org/trademark-policy/' ),
	'freedoms_about_text' => sprintf( $view->l10n->freedoms_about, 'https://wordpress.org/about/license/' ),
];

$view->setData( $data );

echo $view->render( 'admin/freedoms', $view );
