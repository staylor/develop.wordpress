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
}
