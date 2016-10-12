<?php
namespace WP\Plugin\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __( 'Overview' ),
			'content'	=>
				'<p>' . __( 'Plugins extend and expand the functionality of WordPress. Once a plugin is installed, you may activate it or deactivate it here.' ) . '</p>' .
				'<p>' . __( 'The search for installed plugins will search for terms in their name, description, or author.' ) . ' <span id="live-search-desc" class="hide-if-no-js">' . __( 'The search results will be updated as you type.' ) . '</span></p>' .
				'<p>' . sprintf(
					/* translators: %s: WordPress Plugin Directory URL */
					__( 'If you would like to see more plugins to choose from, click on the &#8220;Add New&#8221; button and you will be able to browse or search for additional plugins from the <a href="%s">WordPress Plugin Directory</a>. Plugins in the WordPress Plugin Directory are designed and developed by third parties, and are compatible with the license WordPress uses. Oh, and they&#8217;re free!' ),
					__( 'https://wordpress.org/plugins/' )
				) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'compatibility-problems',
			'title'		=> __( 'Troubleshooting' ),
			'content'	=>
				'<p>' . __( 'Most of the time, plugins play nicely with the core of WordPress and with other plugins. Sometimes, though, a plugin&#8217;s code will get in the way of another plugin, causing compatibility issues. If your site starts doing strange things, this may be the problem. Try deactivating all your plugins and re-activating them in various combinations until you isolate which one(s) caused the issue.' ) . '</p>' .
				'<p>' . sprintf(
					/* translators: WP_PLUGIN_DIR constant value */
					__( 'If something goes wrong with a plugin and you can&#8217;t use WordPress, delete or rename that file in the %s directory and it will be automatically deactivated.' ),
					'<code>' . WP_PLUGIN_DIR . '</code>'
				) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Managing_Plugins#Plugin_Management">Documentation on Managing Plugins</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_views'      => __( 'Filter plugins list' ),
			'heading_pagination' => __( 'Plugins list navigation' ),
			'heading_list'       => __( 'Plugins list' ),
		] );
	}

	public function addEditor() {
		$this->screen->add_help_tab( [
			'id'		=> 'overview',
			'title'		=> __( 'Overview' ),
			'content'	=>
				'<p>' . __( 'You can use the editor to make changes to any of your plugins&#8217; individual PHP files. Be aware that if you make changes, plugins updates will overwrite your customizations.' ) . '</p>' .
				'<p>' . __( 'Choose a plugin to edit from the dropdown menu and click the Select button. Click once on any file name to load it in the editor, and make your changes. Don&#8217;t forget to save your changes (Update File) when you&#8217;re finished.' ) . '</p>' .
				'<p>' . __( 'The Documentation menu below the editor lists the PHP functions recognized in the plugin file. Clicking Look Up takes you to a web page about that particular function.' ) . '</p>' .
				'<p id="newcontent-description">' . __( 'In the editing area the Tab key enters a tab character. To move below this area by pressing Tab, press the Esc key followed by the Tab key. In some cases the Esc key will need to be pressed twice before the Tab key will allow you to continue.' ) . '</p>' .
				'<p>' . __( 'If you want to make changes but don&#8217;t want them to be overwritten when the plugin is updated, you may be ready to think about writing your own plugin. For information on how to edit plugins, write your own from scratch, or just better understand their anatomy, check out the links below.' ) . '</p>' .
				( is_network_admin() ? '<p>' . __( 'Any edits to files from this screen will be reflected on all sites in the network.' ) . '</p>' : '' )
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Plugins_Editor_Screen">Documentation on Editing Plugins</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Writing_a_Plugin">Documentation on Writing Plugins</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}