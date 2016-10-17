<?php
namespace WP\Link\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addManager() {
		$this->addOverview(
			'<p>' . sprintf( __( 'You can add links here to be displayed on your site, usually using <a href="%s">Widgets</a>. By default, links to several sites in the WordPress community are included as examples.' ), 'widgets.php' ) . '</p>' .
			'<p>' . __( 'Links may be separated into Link Categories; these are different than the categories used on your posts.' ) . '</p>' .
			'<p>' . __( 'You can customize the display of this screen using the Screen Options tab and/or the dropdown filters above the links table.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'		=> 'deleting-links',
			'title'		=> __( 'Deleting Links' ),
			'content'	=>
				'<p>' . __( 'If you delete a link, it will be removed permanently, as Links do not have a Trash function yet.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Links_Screen">Documentation on Managing Links</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( [
			'heading_list' => __( 'Links list' ),
		] );
	}

	public function addEditLinkForm() {
		$this->addOverview(
			'<p>' . __( 'You can add or edit links on this screen by entering information in each of the boxes. Only the link&#8217;s web address and name (the text you want to display on your site as the link) are required fields.' ) . '</p>' .
			'<p>' . __( 'The boxes for link name, web address, and description have fixed positions, while the others may be repositioned using drag and drop. You can also hide boxes you don&#8217;t use in the Screen Options tab, or minimize boxes by clicking on the title bar of the box.' ) . '</p>' .
			'<p>' . __( 'XFN stands for <a href="http://gmpg.org/xfn/">XHTML Friends Network</a>, which is optional. WordPress allows the generation of XFN attributes to show how you are related to the authors/owners of the site to which you are linking.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Links_Add_New_Screen">Documentation on Creating Links</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}