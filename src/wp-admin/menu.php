<?php
/**
 * Build Administration Menu.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Constructs the admin menu.
 *
 * The elements in the array are :
 *     0: Menu item name
 *     1: Minimum level or capability required.
 *     2: The URL of the item's file
 *     3: Class
 *     4: ID
 *     5: Icon for top level menu
 *
 * @global array $menu
 */

$app->menu[2] = array( __('Dashboard'), 'read', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'dashicons-dashboard' );

$app->submenu[ 'index.php' ][0] = array( __('Home'), 'read', 'index.php' );

if ( is_multisite() ) {
	$app->submenu[ 'index.php' ][5] = array( __('My Sites'), 'read', 'my-sites.php' );
}

if ( ! is_multisite() || is_super_admin() ) {
	$update_data = wp_get_update_data();
}

if ( ! is_multisite() ) {
	if ( current_user_can( 'update_core' ) ) {
		$cap = 'update_core';
	} elseif ( current_user_can( 'update_plugins' ) ) {
		$cap = 'update_plugins';
	} else {
		$cap = 'update_themes';
	}
	$app->submenu[ 'index.php' ][10] = array( sprintf( __('Updates %s'), "<span class='update-plugins count-{$update_data['counts']['total']}' title='{$update_data['title']}'><span class='update-count'>" . number_format_i18n($update_data['counts']['total']) . "</span></span>" ), $cap, 'update-core.php');
	unset( $cap );
}

$app->menu[4] = array( '', 'read', 'separator1', '', 'wp-menu-separator' );

// $app->menu[5] = Posts

$app->menu[10] = array( __('Media'), 'upload_files', 'upload.php', '', 'menu-top menu-icon-media', 'menu-media', 'dashicons-admin-media' );
	$app->submenu['upload.php'][5] = array( __('Library'), 'upload_files', 'upload.php');
	/* translators: add new file */
	$app->submenu['upload.php'][10] = array( _x('Add New', 'file'), 'upload_files', 'media-new.php');
	$i = 15;
	foreach ( get_taxonomies_for_attachments( 'objects' ) as $tax ) {
		if ( ! $tax->show_ui || ! $tax->show_in_menu ) {
			continue;
		}

		$app->submenu['upload.php'][$i++] = array( esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name . '&amp;post_type=attachment' );
	}
	unset( $tax, $i );

$app->menu[15] = array( __('Links'), 'manage_links', 'link-manager.php', '', 'menu-top menu-icon-links', 'menu-links', 'dashicons-admin-links' );
	$app->submenu['link-manager.php'][5] = array( _x('All Links', 'admin menu'), 'manage_links', 'link-manager.php' );
	/* translators: add new links */
	$app->submenu['link-manager.php'][10] = array( _x('Add New', 'link'), 'manage_links', 'link-add.php' );
	$app->submenu['link-manager.php'][15] = array( __('Link Categories'), 'manage_categories', 'edit-tags.php?taxonomy=link_category' );

// $app->menu[20] = Pages

// Avoid the comment count query for users who cannot edit_posts.
if ( current_user_can( 'edit_posts' ) ) {
	$awaiting_mod = wp_count_comments();
	$awaiting_mod = $awaiting_mod->moderated;
	$app->menu[25] = array(
		sprintf( __( 'Comments %s' ), '<span class="awaiting-mod count-' . absint( $awaiting_mod ) . '"><span class="pending-count">' . number_format_i18n( $awaiting_mod ) . '</span></span>' ),
		'edit_posts',
		'edit-comments.php',
		'',
		'menu-top menu-icon-comments',
		'menu-comments',
		'dashicons-admin-comments',
	);
	unset( $awaiting_mod );
}

$app->submenu[ 'edit-comments.php' ][0] = array( __('All Comments'), 'edit_posts', 'edit-comments.php' );

$_wp_last_object_menu = 25; // The index of the last top-level menu in the object menu group

$types = (array) get_post_types( array('show_ui' => true, '_builtin' => false, 'show_in_menu' => true ) );
$builtin = array( 'post', 'page' );
foreach ( array_merge( $builtin, $types ) as $ptype ) {
	$ptype_obj = get_post_type_object( $ptype );
	// Check if it should be a submenu.
	if ( $ptype_obj->show_in_menu !== true ) {
		continue;
	}
	$ptype_menu_position = is_int( $ptype_obj->menu_position ) ? $ptype_obj->menu_position : ++$_wp_last_object_menu; // If we're to use $_wp_last_object_menu, increment it first.
	$ptype_for_id = sanitize_html_class( $ptype );

	$menu_icon = 'dashicons-admin-post';
	if ( is_string( $ptype_obj->menu_icon ) ) {
		// Special handling for data:image/svg+xml and Dashicons.
		if ( 0 === strpos( $ptype_obj->menu_icon, 'data:image/svg+xml;base64,' ) || 0 === strpos( $ptype_obj->menu_icon, 'dashicons-' ) ) {
			$menu_icon = $ptype_obj->menu_icon;
		} else {
			$menu_icon = esc_url( $ptype_obj->menu_icon );
		}
	} elseif ( in_array( $ptype, $builtin ) ) {
		$menu_icon = 'dashicons-admin-' . $ptype;
	}

	$menu_class = 'menu-top menu-icon-' . $ptype_for_id;
	// 'post' special case
	if ( 'post' === $ptype ) {
		$menu_class .= ' open-if-no-js';
		$ptype_file = "edit.php";
		$post_new_file = "post-new.php";
		$edit_tags_file = "edit-tags.php?taxonomy=%s";
	} else {
		$ptype_file = "edit.php?post_type=$ptype";
		$post_new_file = "post-new.php?post_type=$ptype";
		$edit_tags_file = "edit-tags.php?taxonomy=%s&amp;post_type=$ptype";
	}

	if ( in_array( $ptype, $builtin ) ) {
		$ptype_menu_id = 'menu-' . $ptype_for_id . 's';
	} else {
		$ptype_menu_id = 'menu-posts-' . $ptype_for_id;
	}
	/*
	 * If $ptype_menu_position is already populated or will be populated
	 * by a hard-coded value below, increment the position.
	 */
	$core_menu_positions = array(59, 60, 65, 70, 75, 80, 85, 99);
	while ( isset($app->menu[$ptype_menu_position]) || in_array($ptype_menu_position, $core_menu_positions) ) {
		$ptype_menu_position++;
	}

	$app->menu[$ptype_menu_position] = array( esc_attr( $ptype_obj->labels->menu_name ), $ptype_obj->cap->edit_posts, $ptype_file, '', $menu_class, $ptype_menu_id, $menu_icon );
	$app->submenu[ $ptype_file ][5]  = array( $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts,  $ptype_file );
	$app->submenu[ $ptype_file ][10]  = array( $ptype_obj->labels->add_new, $ptype_obj->cap->create_posts, $post_new_file );

	$i = 15;
	foreach ( get_taxonomies( [], 'objects' ) as $tax ) {
		if ( ! $tax->show_ui || ! $tax->show_in_menu || ! in_array($ptype, (array) $tax->object_type, true) ) {
			continue;
		}

		$app->submenu[ $ptype_file ][$i++] = array( esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, sprintf( $edit_tags_file, $tax->name ) );
	}
}
unset( $ptype, $ptype_obj, $ptype_for_id, $ptype_menu_position, $menu_icon, $i, $tax, $post_new_file );

$app->menu[59] = array( '', 'read', 'separator2', '', 'wp-menu-separator' );

$appearance_cap = current_user_can( 'switch_themes') ? 'switch_themes' : 'edit_theme_options';

$app->menu[60] = array( __( 'Appearance' ), $appearance_cap, 'themes.php', '', 'menu-top menu-icon-appearance', 'menu-appearance', 'dashicons-admin-appearance' );
	$app->submenu['themes.php'][5] = array( __( 'Themes' ), $appearance_cap, 'themes.php' );

	$customize_url = add_query_arg( 'return', urlencode( wp_unslash( $app['request.uri'] ) ), 'customize.php' );
	$app->submenu['themes.php'][6] = array( __( 'Customize' ), 'customize', esc_url( $customize_url ), '', 'hide-if-no-customize' );

	if ( current_theme_supports( 'menus' ) || current_theme_supports( 'widgets' ) ) {
		$app->submenu['themes.php'][10] = array( __( 'Menus' ), 'edit_theme_options', 'nav-menus.php' );
	}

	if ( current_theme_supports( 'custom-header' ) && current_user_can( 'customize') ) {
		$customize_header_url = add_query_arg( array( 'autofocus' => array( 'control' => 'header_image' ) ), $customize_url );
		$app->submenu['themes.php'][15] = array( __( 'Header' ), $appearance_cap, esc_url( $customize_header_url ), '', 'hide-if-no-customize' );
	}

	if ( current_theme_supports( 'custom-background' ) && current_user_can( 'customize') ) {
		$customize_background_url = add_query_arg( array( 'autofocus' => array( 'control' => 'background_image' ) ), $customize_url );
		$app->submenu['themes.php'][20] = array( __( 'Background' ), $appearance_cap, esc_url( $customize_background_url ), '', 'hide-if-no-customize' );
	}

	unset( $customize_url );

unset( $appearance_cap );

// Add 'Editor' to the bottom of the Appearance menu.
if ( ! is_multisite() ) {
	add_action('admin_menu', '_add_themes_utility_last', 101);
}
/**
 * Adds the (theme) 'Editor' link to the bottom of the Appearance menu.
 *
 * @access private
 * @since 3.0.0
 */
function _add_themes_utility_last() {
	// Must use API on the admin_menu hook, direct modification is only possible on/before the _admin_menu hook
	add_submenu_page('themes.php', _x('Editor', 'theme editor'), _x('Editor', 'theme editor'), 'edit_themes', 'theme-editor.php');
}

$count = '';
if ( ! is_multisite() && current_user_can( 'update_plugins' ) ) {
	if ( ! isset( $update_data ) ) {
		$update_data = wp_get_update_data();
	}
	$count = "<span class='update-plugins count-{$update_data['counts']['plugins']}'><span class='plugin-count'>" . number_format_i18n($update_data['counts']['plugins']) . "</span></span>";
}

$app->menu[65] = array( sprintf( __('Plugins %s'), $count ), 'activate_plugins', 'plugins.php', '', 'menu-top menu-icon-plugins', 'menu-plugins', 'dashicons-admin-plugins' );

$app->submenu['plugins.php'][5]  = array( __('Installed Plugins'), 'activate_plugins', 'plugins.php' );

	if ( ! is_multisite() ) {
		/* translators: add new plugin */
		$app->submenu['plugins.php'][10] = array( _x('Add New', 'plugin'), 'install_plugins', 'plugin-install.php' );
		$app->submenu['plugins.php'][15] = array( _x('Editor', 'plugin editor'), 'edit_plugins', 'plugin-editor.php' );
	}

unset( $update_data );

if ( current_user_can('list_users') ) {
	$app->menu[70] = array( __('Users'), 'list_users', 'users.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users' );
} else {
	$app->menu[70] = array( __('Profile'), 'read', 'profile.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users' );
}

if ( current_user_can('list_users') ) {
	$app->submenu['users.php'][5] = array(__('All Users'), 'list_users', 'users.php');
	if ( current_user_can( 'create_users' ) ) {
		$app->submenu['users.php'][10] = array(_x('Add New', 'user'), 'create_users', 'user-new.php');
	} elseif ( is_multisite() ) {
		$app->submenu['users.php'][10] = array(_x('Add New', 'user'), 'promote_users', 'user-new.php');
	}

	$app->submenu['users.php'][15] = array(__('Your Profile'), 'read', 'profile.php');
} else {
	$app->submenu['profile.php'][5] = array(__('Your Profile'), 'read', 'profile.php');
	if ( current_user_can( 'create_users' ) ) {
		$app->submenu['profile.php'][10] = array(__('Add New User'), 'create_users', 'user-new.php');
	} elseif ( is_multisite() ) {
		$app->submenu['profile.php'][10] = array(__('Add New User'), 'promote_users', 'user-new.php');
	}
}

$app->menu[75] = array( __('Tools'), 'edit_posts', 'tools.php', '', 'menu-top menu-icon-tools', 'menu-tools', 'dashicons-admin-tools' );
	$app->submenu['tools.php'][5] = array( __('Available Tools'), 'edit_posts', 'tools.php' );
	$app->submenu['tools.php'][10] = array( __('Import'), 'import', 'import.php' );
	$app->submenu['tools.php'][15] = array( __('Export'), 'export', 'export.php' );
	if ( is_multisite() && !is_main_site() ) {
		$app->submenu['tools.php'][25] = array( __('Delete Site'), 'delete_site', 'ms-delete-site.php' );
	}
	if ( ! is_multisite() && defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE ) {
		$app->submenu['tools.php'][50] = array(__('Network Setup'), 'manage_options', 'network.php');
	}

$app->menu[80] = array( __('Settings'), 'manage_options', 'options-general.php', '', 'menu-top menu-icon-settings', 'menu-settings', 'dashicons-admin-settings' );
	$app->submenu['options-general.php'][10] = array(_x('General', 'settings screen'), 'manage_options', 'options-general.php');
	$app->submenu['options-general.php'][15] = array(__('Writing'), 'manage_options', 'options-writing.php');
	$app->submenu['options-general.php'][20] = array(__('Reading'), 'manage_options', 'options-reading.php');
	$app->submenu['options-general.php'][25] = array(__('Discussion'), 'manage_options', 'options-discussion.php');
	$app->submenu['options-general.php'][30] = array(__('Media'), 'manage_options', 'options-media.php');
	$app->submenu['options-general.php'][40] = array(__('Permalinks'), 'manage_options', 'options-permalink.php');

$_wp_last_utility_menu = 80; // The index of the last top-level menu in the utility menu group

$app->menu[99] = array( '', 'read', 'separator-last', '', 'wp-menu-separator' );

require_once(ABSPATH . 'wp-admin/includes/menu.php');
