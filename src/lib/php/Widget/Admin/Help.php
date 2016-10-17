<?php
namespace WP\Widget\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addWidgets() {
		$this->addOverview(
			'<p>' . __( 'Widgets are independent sections of content that can be placed into any widgetized area provided by your theme (commonly called sidebars). To populate your sidebars/widget areas with individual widgets, drag and drop the title bars into the desired area. By default, only the first widget area is expanded. To populate additional widget areas, click on their title bars to expand them.' ) . '</p>
			<p>' . __( 'The Available Widgets section contains all the widgets you can choose from. Once you drag a widget into a sidebar, it will open to allow you to configure its settings. When you are happy with the widget settings, click the Save button and the widget will go live on your site. If you click Delete, it will remove the widget.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'		=> 'removing-reusing',
			'title'		=> __( 'Removing and Reusing' ),
			'content'	=>
				'<p>' . __( 'If you want to remove the widget but save its setting for possible future use, just drag it into the Inactive Widgets area. You can add them back anytime from there. This is especially helpful when you switch to a theme with fewer or different widget areas.' ) . '</p>
				<p>' . __( 'Widgets may be used multiple times. You can give each widget a title, to display on your site, but it&#8217;s not required.' ) . '</p>
				<p>' . __( 'Enabling Accessibility Mode, via Screen Options, allows you to use Add and Edit buttons instead of using drag and drop.' ) . '</p>'
		] );

		$this->screen->add_help_tab( [
			'id'		=> 'missing-widgets',
			'title'		=> __( 'Missing Widgets' ),
			'content'	=>
				'<p>' . __( 'Many themes show some sidebar widgets by default until you edit your sidebars, but they are not automatically displayed in your sidebar management tool. After you make your first widget change, you can re-add the default widgets by adding them from the Available Widgets area.' ) . '</p>' .
				'<p>' . __( 'When changing themes, there is often some variation in the number and setup of widget areas/sidebars and sometimes these conflicts make the transition a bit less smooth. If you changed themes and seem to be missing widgets, scroll down on this screen to the Inactive Widgets area, where all of your widgets and their settings will have been saved.' ) . '</p>'
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Appearance_Widgets_Screen">Documentation on Widgets</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}
