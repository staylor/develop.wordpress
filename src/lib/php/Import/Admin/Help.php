<?php
namespace WP\Import\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => '<p>' . __( 'This screen lists links to plugins to import data from blogging/content management platforms. Choose the platform you want to import from, and click Install Now when you are prompted in the popup window. If your platform is not listed, click the link to search the plugin directory for other importer plugins to see if there is one for your platform.' ) . '</p>' .
				'<p>' . __( 'In previous versions of WordPress, all importers were built-in. They have been turned into plugins since most people only use them once or infrequently.' ) . '</p>',
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Tools_Import_Screen">Documentation on Import</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

}