<?php
namespace WP\Install\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addUpdateCore() {
		$updates_overview  = '<p>' . __( 'On this screen, you can update to the latest version of WordPress, as well as update your themes, plugins, and translations from the WordPress.org repositories.' ) . '</p>';
		$updates_overview .= '<p>' . __( 'If an update is available, you&#8127;ll see a notification appear in the Toolbar and navigation menu.' ) . ' ' . __( 'Keeping your site updated is important for security. It also makes the internet a safer place for you and your readers.' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $updates_overview
		] );

		$updates_howto  = '<p>' . __( '<strong>WordPress</strong> &mdash; Updating your WordPress installation is a simple one-click procedure: just <strong>click on the &#8220;Update Now&#8221; button</strong> when you are notified that a new version is available.' ) . ' ' . __( 'In most cases, WordPress will automatically apply maintenance and security updates in the background for you.' ) . '</p>';
		$updates_howto .= '<p>' . __( '<strong>Themes and Plugins</strong> &mdash; To update individual themes or plugins from this screen, use the checkboxes to make your selection, then <strong>click on the appropriate &#8220;Update&#8221; button</strong>. To update all of your themes or plugins at once, you can check the box at the top of the section to select all before clicking the update button.' ) . '</p>';

		if ( 'en_US' != get_locale() ) {
			$updates_howto .= '<p>' . __( '<strong>Translations</strong> &mdash; The files translating WordPress into your language are updated for you whenever any other updates occur. But if these files are out of date, you can <strong>click the &#8220;Update Translations&#8221;</strong> button.' ) . '</p>';
		}

		$this->screen->add_help_tab( [
			'id'      => 'how-to-update',
			'title'   => __( 'How to Update' ),
			'content' => $updates_howto
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Dashboard_Updates_Screen">Documentation on Updating WordPress</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addNetwork() {
		$network_help = '<p>' . __( 'This screen allows you to configure a network as having subdomains (<code>site1.example.com</code>) or subdirectories (<code>example.com/site1</code>). Subdomains require wildcard subdomains to be enabled in Apache and DNS records, if your host allows it.' ) . '</p>' .
			'<p>' . __( 'Choose subdomains or subdirectories; this can only be switched afterwards by reconfiguring your install. Fill out the network details, and click install. If this does not work, you may have to add a wildcard DNS record (for subdomains) or change to another setting in Permalinks (for subdirectories).' ) . '</p>' .
			'<p>' . __( 'The next screen for Network Setup will give you individually-generated lines of code to add to your wp-config.php and .htaccess files. Make sure the settings of your FTP client make files starting with a dot visible, so that you can find .htaccess; you may have to create this file if it really is not there. Make backup copies of those two files.' ) . '</p>' .
			'<p>' . __( 'Add the designated lines of code to wp-config.php (just before <code>/*...stop editing...*/</code>) and <code>.htaccess</code> (replacing the existing WordPress rules).' ) . '</p>' .
			'<p>' . __( 'Once you add this code and refresh your browser, multisite should be enabled. This screen, now in the Network Admin navigation menu, will keep an archive of the added code. You can toggle between Network Admin and Site Admin by clicking on the Network Admin or an individual site name under the My Sites dropdown in the Toolbar.' ) . '</p>' .
			'<p>' . __( 'The choice of subdirectory sites is disabled if this setup is more than a month old because of permalink problems with &#8220;/blog/&#8221; from the main site. This disabling will be addressed in a future version.' ) . '</p>' .
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Create_A_Network" target="_blank">Documentation on Creating a Network</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Tools_Network_Screen" target="_blank">Documentation on the Network Screen</a>' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'network',
			'title'   => __( 'Network' ),
			'content' => $network_help,
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Create_A_Network" target="_blank">Documentation on Creating a Network</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Tools_Network_Screen" target="_blank">Documentation on the Network Screen</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>' ) . '</p>'
		);
	}

	public function addUpgradeNetwork() {
		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' =>
				'<p>' . __( 'Only use this screen once you have updated to a new version of WordPress through Updates/Available Updates (via the Network Administration navigation menu or the Toolbar). Clicking the Upgrade Network button will step through each site in the network, five at a time, and make sure any database updates are applied.' ) . '</p>' .
				'<p>' . __( 'If a version update to core has not happened, clicking this button won&#8217;t affect anything.' ) . '</p>' .
				'<p>' . __( 'If this process fails for any reason, users logging in to their sites will force the same update.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Updates_Screen">Documentation on Upgrade Network</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addThemeInstall() {
		$help_overview =
			'<p>' . sprintf(
					/* translators: %s: Theme Directory URL */
					__( 'You can find additional themes for your site by using the Theme Browser/Installer on this screen, which will display themes from the <a href="%s">WordPress Theme Directory</a>. These themes are designed and developed by third parties, are available free of charge, and are compatible with the license WordPress uses.' ),
					__( 'https://wordpress.org/themes/' )
				) . '</p>' .
			'<p>' . __( 'You can Search for themes by keyword, author, or tag, or can get more specific and search by criteria listed in the feature filter.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>' .
			'<p>' . __( 'Alternately, you can browse the themes that are Featured, Popular, or Latest. When you find a theme you like, you can preview it or install it.' ) . '</p>' .
			'<p>' . sprintf(
					/* translators: %s: /wp-content/themes */
					__( 'You can Upload a theme manually if you have already downloaded its ZIP archive onto your computer (make sure it is from a trusted and original source). You can also do it the old-fashioned way and copy a downloaded theme&#8217;s folder via FTP into your %s directory.' ),
					'<code>/wp-content/themes</code>'
				) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $help_overview
		] );

		$help_installing =
			'<p>' . __( 'Once you have generated a list of themes, you can preview and install any of them. Click on the thumbnail of the theme you&#8217;re interested in previewing. It will open up in a full-screen Preview page to give you a better idea of how that theme will look.' ) . '</p>' .
			'<p>' . __( 'To install the theme so you can preview it with your site&#8217;s content and customize its theme options, click the "Install" button at the top of the left-hand pane. The theme files will be downloaded to your website automatically. When this is complete, the theme is now available for activation, which you can do by clicking the "Activate" link, or by navigating to your Manage Themes screen and clicking the "Live Preview" link under any installed theme&#8217;s thumbnail image.' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'installing',
			'title'   => __( 'Previewing and Installing' ),
			'content' => $help_installing
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Using_Themes#Adding_New_Themes">Documentation on Adding New Themes</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addPluginInstall() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __( 'Overview' ),
			'content'	=>
				'<p>' . sprintf( __( 'Plugins hook into WordPress to extend its functionality with custom features. Plugins are developed independently from the core WordPress application by thousands of developers all over the world. All plugins in the official <a href="%s">WordPress Plugin Directory</a> are compatible with the license WordPress uses.' ), __( 'https://wordpress.org/plugins/' ) ) . '</p>' .
				'<p>' . __( 'You can find new plugins to install by searching or browsing the directory right here in your own Plugins section.' ) . ' <span id="live-search-desc" class="hide-if-no-js">' . __( 'The search results will be updated as you type.' ) . '</span></p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'adding-plugins',
			'title'		=> __( 'Adding Plugins' ),
			'content'	=>
				'<p>' . __( 'If you know what you&#8217;re looking for, Search is your best bet. The Search screen has options to search the WordPress Plugin Directory for a particular Term, Author, or Tag. You can also search the directory by selecting popular tags. Tags in larger type mean more plugins have been labeled with that tag.' ) . '</p>' .
				'<p>' . __( 'If you just want to get an idea of what&#8217;s available, you can browse Featured and Popular plugins by using the links above the plugins list. These sections rotate regularly.' ) . '</p>' .
				'<p>' . __( 'You can also browse a user&#8217;s favorite plugins, by using the Favorites link above the plugins list and entering their WordPress.org username.' ) . '</p>' .
				'<p>' . __( 'If you want to install a plugin that you&#8217;ve downloaded elsewhere, click the Upload Plugin button above the plugins list. You will be prompted to upload the .zip package, and once uploaded, you can activate the new plugin.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Plugins_Add_New_Screen">Documentation on Installing Plugins</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter plugins list' ),
			'heading_pagination' => __( 'Plugins list navigation' ),
			'heading_list'       => __( 'Plugins list' ),
		] );
	}
}
