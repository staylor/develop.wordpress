<?php
namespace WP\Customize\Header\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addMain() {
		$this->addOverview(
			'<p>' . __( 'This screen is used to customize the header section of your theme.' ) . '</p>' .
			'<p>' . __( 'You can choose from the theme&#8217;s default header images, or use one of your own. You can also customize how your Site Title and Tagline are displayed.' ) . '<p>'
		);

		$this->screen->add_help_tab( [
			'id'      => 'set-header-image',
			'title'   => __( 'Header Image' ),
			'content' =>
				'<p>' . __( 'You can set a custom image header for your site. Simply upload the image and crop it, and the new header will go live immediately. Alternatively, you can use an image that has already been uploaded to your Media Library by clicking the &#8220;Choose Image&#8221; button.' ) . '</p>' .
				'<p>' . __( 'Some themes come with additional header images bundled. If you see multiple images displayed, select the one you&#8217;d like and click the &#8220;Save Changes&#8221; button.' ) . '</p>' .
				'<p>' . __( 'If your theme has more than one default header image, or you have uploaded more than one custom header image, you have the option of having WordPress display a randomly different image on each page of your site. Click the &#8220;Random&#8221; radio button next to the Uploaded Images or Default Images section to enable this feature.' ) . '</p>' .
				'<p>' . __( 'If you don&#8217;t want a header image to be displayed on your site at all, click the &#8220;Remove Header Image&#8221; button at the bottom of the Header Image section of this page. If you want to re-enable the header image later, you just have to select one of the other image options and click &#8220;Save Changes&#8221;.' ) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'      => 'set-header-text',
			'title'   => __( 'Header Text' ),
			'content' =>
				'<p>' . sprintf( __( 'For most themes, the header text is your Site Title and Tagline, as defined in the <a href="%1$s">General Settings</a> section.' ), admin_url( 'options-general.php' ) ) . '<p>' .
				'<p>' . __( 'In the Header Text section of this page, you can choose whether to display this text or hide it. You can also choose a color for the text by clicking the Select Color button and either typing in a legitimate HTML hex value, e.g. &#8220;#ff0000&#8221; for red, or by choosing a color using the color picker.' ) . '</p>' .
				'<p>' . __( 'Don&#8217;t forget to click &#8220;Save Changes&#8221; when you&#8217;re done!' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Appearance_Header_Screen">Documentation on Custom Header</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}
