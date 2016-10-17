<?php
namespace WP\Site\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		$this->addOverview(
			'<p>' . __( 'Add New takes you to the Add New Site screen. You can search for a site by Name, ID number, or IP address. Screen Options allows you to choose how many sites to display on one page.' ) . '</p>' .
			'<p>' . __( 'This is the main table of all sites on this network. Switch between list and excerpt views by using the icons above the right side of the table.' ) . '</p>' .
			'<p>' . __( 'Hovering over each site reveals seven options (three for the primary site):' ) . '</p>' .
			'<ul><li>' . __( 'An Edit link to a separate Edit Site screen.' ) . '</li>' .
			'<li>' . __( 'Dashboard leads to the Dashboard for that site.' ) . '</li>' .
			'<li>' . __( 'Deactivate, Archive, and Spam which lead to confirmation screens. These actions can be reversed later.' ) . '</li>' .
			'<li>' . __( 'Delete which is a permanent action after the confirmation screens.' ) . '</li>' .
			'<li>' . __( 'Visit to go to the front-end site live.' ) . '</li></ul>' .
			'<p>' . __( 'The site ID is used internally, and is not shown on the front end of the site or to users/viewers.' ) . '</p>' .
			'<p>' . __( 'Clicking on bold headings can re-sort this table.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_pagination' => __( 'Sites list navigation' ),
			'heading_list'       => __( 'Sites list' ),
		] );
	}

	public function addInfo() {
		$this->addOverview(
			'<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' .
			'<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' .
			'<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' .
			'<p>' . sprintf( __( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' .
			'<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addNew() {
		$this->addOverview(
			'<p>' . __( 'This screen is for Super Admins to add new sites to the network. This is not affected by the registration settings.' ) . '</p>' .
			'<p>' . __( 'If the admin email for the new site does not exist in the database, a new user will also be created.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addSettings() {
		$this->addOverview(
			'<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' .
			'<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' .
			'<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' .
			'<p>' . sprintf( __( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' .
			'<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addThemes() {
		$this->addOverview(
			'<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' .
			'<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' .
			'<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' .
			'<p>' . sprintf( __( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' .
			'<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter site themes list' ),
			'heading_pagination' => __( 'Site themes list navigation' ),
			'heading_list'       => __( 'Site themes list' ),
		] );
	}

	public function addUsers() {
		$this->addOverview(
			'<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' .
			'<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' .
			'<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' .
			'<p>' . sprintf( __( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' .
			'<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter site users list' ),
			'heading_pagination' => __( 'Site users list navigation' ),
			'heading_list'       => __( 'Site users list' ),
		] );
	}

	public function addMySites() {
		$this->addOverview(
			'<p>' . __( 'This screen shows an individual user all of their sites in this network, and also allows that user to set a primary site. They can use the links under each site to visit either the front end or the dashboard for that site.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Dashboard_My_Sites_Screen">Documentation on My Sites</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addNetworkSettings() {
		$this->addOverview(
			'<p>' . __( 'This screen sets and changes options for the network as a whole. The first site is the main site in the network and network options are pulled from that original site&#8217;s options.' ) . '</p>' .
			'<p>' . __( 'Operational settings has fields for the network&#8217;s name and admin email.' ) . '</p>' .
			'<p>' . __( 'Registration settings can disable/enable public signups. If you let others sign up for a site, install spam plugins. Spaces, not commas, should separate names banned as sites for this network.' ) . '</p>' .
			'<p>' . __( 'New site settings are defaults applied when a new site is created in the network. These include welcome email for when a new site or user account is registered, and what&#8127;s put in the first post, page, comment, comment author, and comment URL.' ) . '</p>' .
			'<p>' . __( 'Upload settings control the size of the uploaded files and the amount of available upload space for each site. You can change the default value for specific sites when you edit a particular site. Allowed file types are also listed (space separated only).' ) . '</p>' .
			'<p>' . __( 'You can set the language, and the translation files will be automatically downloaded and installed (available if your filesystem is writable).' ) . '</p>' .
			'<p>' . __( 'Menu setting enables/disables the plugin menus from appearing for non super admins, so that only super admins, not site admins, have access to activate plugins.' ) . '</p>' .
			'<p>' . __( 'Super admins can no longer be added on the Options screen. You must now go to the list of existing users on Network Admin > Users and click on Username or the Edit action link below that name. This goes to an Edit User page where you can check a box to grant super admin privileges.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Settings_Screen">Documentation on Network Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}