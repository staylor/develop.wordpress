<?php
/**
 * Build User Administration Menu.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 3.1.0
 */

$app->menu[2] = [ __('Dashboard'), 'exist', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'dashicons-dashboard' ];

$app->menu[4] = [ '', 'exist', 'separator1', '', 'wp-menu-separator' ];

$app->menu[70] = [ __('Profile'), 'exist', 'profile.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users' ];

$app->menu[99] = [ '', 'exist', 'separator-last', '', 'wp-menu-separator' ];

$app->submenu = [];

require_once(ABSPATH . 'wp-admin/includes/menu.php');
