<?php
namespace WP\Tools\Admin;

use WP\Admin\Screen;

class Help {
	protected $screen;

	public function __construct( Screen $screen ) {
		$this->screen = $screen;
	}

	public function addMain() {
		$this->screen->add_help_tab( [
			'id'      => 'press-this',
			'title'   => __('Press This'),
			'content' => '<p>' . __('Press This is a bookmarklet that makes it easy to blog about something you come across on the web. You can use it to just grab a link, or to post an excerpt. Press This will even allow you to choose from images included on the page and use them in your post. Just drag the Press This link on this screen to your bookmarks bar in your browser, and you&#8217;ll be on your way to easier content creation. Clicking on it while on another website opens a popup window with all these options.') . '</p>',
		] );
		$this->screen->add_help_tab( [
			'id'      => 'converter',
			'title'   => __('Categories and Tags Converter'),
			'content' => '<p>' . __('Categories have hierarchy, meaning that you can nest sub-categories. Tags do not have hierarchy and cannot be nested. Sometimes people start out using one on their posts, then later realize that the other would work better for their content.' ) . '</p>' .
			'<p>' . __( 'The Categories and Tags Converter link on this screen will take you to the Import screen, where that Converter is one of the plugins you can install. Once that plugin is installed, the Activate Plugin &amp; Run Importer link will take you to a screen where you can choose to convert tags into categories or vice versa.' ) . '</p>',
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Tools_Screen">Documentation on Tools</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/">Support Forums</a>') . '</p>'
		);
	}
}