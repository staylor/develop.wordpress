<?php
namespace WP\Theme\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		// Help tab: Overview
		if ( current_user_can( 'switch_themes' ) ) {
			$help_overview  = '<p>' . __( 'This screen is used for managing your installed themes. Aside from the default theme(s) included with your WordPress installation, themes are designed and developed by third parties.' ) . '</p>' .
				'<p>' . __( 'From this screen you can:' ) . '</p>' .
				'<ul><li>' . __( 'Hover or tap to see Activate and Live Preview buttons' ) . '</li>' .
				'<li>' . __( 'Click on the theme to see the theme name, version, author, description, tags, and the Delete link' ) . '</li>' .
				'<li>' . __( 'Click Customize for the current theme or Live Preview for any other theme to see a live preview' ) . '</li></ul>' .
				'<p>' . __( 'The current theme is displayed highlighted as the first theme.' ) . '</p>' .
				'<p>' . __( 'The search for installed themes will search for terms in their name, description, author, or tag.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>';

			$this->screen->add_help_tab( [
				'id'      => 'overview',
				'title'   => __( 'Overview' ),
				'content' => $help_overview
			] );
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
				'<p>' . __( 'Tap or hover on any theme then click the Live Preview button to see a live preview of that theme and change theme options in a separate, full-screen view. You can also find a Live Preview button at the bottom of the theme details screen. Any installed theme can be previewed and customized in this way.' ) . '</p>'.
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
}