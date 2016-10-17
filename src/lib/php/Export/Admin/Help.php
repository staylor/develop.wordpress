<?php
namespace WP\Export\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		$this->addOverview(
			'<p>' . __( 'You can export a file of your site&#8217;s content in order to import it into another installation or platform. The export file will be an XML file format called WXR. Posts, pages, comments, custom fields, categories, and tags can be included. You can choose for the WXR file to include only certain posts or pages by setting the dropdown filters to limit the export by category, author, date range by month, or publishing status.' ) . '</p>' .
			'<p>' . __( 'Once generated, your WXR file can be imported by another WordPress site or by another blogging platform able to access this format.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Tools_Export_Screen">Documentation on Export</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}