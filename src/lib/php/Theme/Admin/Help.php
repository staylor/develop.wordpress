<?php
namespace WP\Theme\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		// Help tab: Overview
		if ( current_user_can( 'switch_themes' ) ) {
			$help_overview = '<p>' . __( 'This screen is used for managing your installed themes. Aside from the default theme(s) included with your WordPress installation, themes are designed and developed by third parties.' ) . '</p>' .
				'<p>' . __( 'From this screen you can:' ) . '</p>' .
				'<ul><li>' . __( 'Hover or tap to see Activate and Live Preview buttons' ) . '</li>' .
				'<li>' . __( 'Click on the theme to see the theme name, version, author, description, tags, and the Delete link' ) . '</li>' .
				'<li>' . __( 'Click Customize for the current theme or Live Preview for any other theme to see a live preview' ) . '</li></ul>' .
				'<p>' . __( 'The current theme is displayed highlighted as the first theme.' ) . '</p>' .
				'<p>' . __( 'The search for installed themes will search for terms in their name, description, author, or tag.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>';

			$this->addOverview( $help_overview );
		} // switch_themes

		// Help tab: Adding Themes
		if ( current_user_can( 'install_themes' ) ) {
			if ( is_multisite() ) {
				$help_install = '<p>' . __( 'Installing themes on Multisite can only be done from the Network Admin section.' ) . '</p>';
			} else {
				$help_install = '<p>' . sprintf( __( 'If you would like to see more themes to choose from, click on the &#8220;Add New&#8221; button and you will be able to browse or search for additional themes from the <a href="%s">WordPress Theme Directory</a>. Themes in the WordPress Theme Directory are designed and developed by third parties, and are compatible with the license WordPress uses. Oh, and they&#8217;re free!' ), __( 'https://wordpress.org/themes/' ) ) . '</p>';
			}

			$this->screen->add_help_tab( [
				'id'      => 'adding-themes',
				'title'   => __( 'Adding Themes' ),
				'content' => $help_install
			] );
		} // install_themes

		// Help tab: Previewing and Customizing
		if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
			$help_customize =
				'<p>' . __( 'Tap or hover on any theme then click the Live Preview button to see a live preview of that theme and change theme options in a separate, full-screen view. You can also find a Live Preview button at the bottom of the theme details screen. Any installed theme can be previewed and customized in this way.' ) . '</p>' .
				'<p>' . __( 'The theme being previewed is fully interactive &mdash; navigate to different pages to see how the theme handles posts, archives, and other page templates. The settings may differ depending on what theme features the theme being previewed supports. To accept the new settings and activate the theme all in one step, click the Save &amp; Activate button above the menu.' ) . '</p>' .
				'<p>' . __( 'When previewing on smaller monitors, you can use the collapse icon at the bottom of the left-hand pane. This will hide the pane, giving you more room to preview your site in the new theme. To bring the pane back, click on the collapse icon again.' ) . '</p>';

			$this->screen->add_help_tab( [
				'id'		=> 'customize-preview-themes',
				'title'		=> __( 'Previewing and Customizing' ),
				'content'	=> $help_customize
			] );
		} // edit_theme_options && customize

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Using_Themes">Documentation on Using Themes</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addMultisiteThemes() {
		$this->addOverview(
			'<p>' . __( 'This screen enables and disables the inclusion of themes available to choose in the Appearance menu for each site. It does not activate or deactivate which theme a site is currently using.' ) . '</p>' .
			'<p>' . __( 'If the network admin disables a theme that is in use, it can still remain selected on that site. If another theme is chosen, the disabled theme will not appear in the site&#8217;s Appearance > Themes screen.' ) . '</p>' .
			'<p>' . __( 'Themes can be enabled on a site by site basis by the network admin on the Edit Site screen (which has a Themes tab); get there via the Edit action link on the All Sites screen. Only network admins are able to install or edit themes.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Themes_Screen">Documentation on Network Themes</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter themes list' ),
			'heading_pagination' => __( 'Themes list navigation' ),
			'heading_list'       => __( 'Themes list' ),
		] );
	}

	public function addEditor() {
		$this->addOverview(
			'<p>' . __( 'You can use the Theme Editor to edit the individual CSS and PHP files which make up your theme.' ) . '</p>
			<p>' . __( 'Begin by choosing a theme to edit from the dropdown menu and clicking the Select button. A list then appears of the theme&#8217;s template files. Clicking once on any file name causes the file to appear in the large Editor box.' ) . '</p>
			<p>' . __( 'For PHP files, you can use the Documentation dropdown to select from functions recognized in that file. Look Up takes you to a web page with reference material about that particular function.' ) . '</p>
			<p id="newcontent-description">' . __( 'In the editing area the Tab key enters a tab character. To move below this area by pressing Tab, press the Esc key followed by the Tab key. In some cases the Esc key will need to be pressed twice before the Tab key will allow you to continue.' ) . '</p>
			<p>' . __( 'After typing in your edits, click Update File.' ) . '</p>
			<p>' . __( '<strong>Advice:</strong> think very carefully about your site crashing if you are live-editing the theme currently in use.' ) . '</p>
			<p>' . sprintf( __( 'Upgrading to a newer version of the same theme will override changes made here. To avoid this, consider creating a <a href="%s">child theme</a> instead.' ), __( 'https://codex.wordpress.org/Child_Themes' ) ) . '</p>' .
			( is_network_admin() ? '<p>' . __( 'Any edits to files from this screen will be reflected on all sites in the network.' ) . '</p>' : '' )
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Theme_Development">Documentation on Theme Development</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Using_Themes">Documentation on Using Themes</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Editing_Files">Documentation on Editing Files</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Template_Tags">Documentation on Template Tags</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}