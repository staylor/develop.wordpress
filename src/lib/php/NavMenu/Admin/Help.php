<?php
namespace WP\NavMenu\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	protected function addSidebar() {
		$this->screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Appearance_Menus_Screen">Documentation on Menus</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/">Support Forums</a>') . '</p>'
		);
	}

	public function addMain() {
		$overview  = '<p>' . __( 'This screen is used for managing your custom navigation menus.' ) . '</p>';
		$overview .= '<p>' . sprintf( __( 'Menus can be displayed in locations defined by your theme, even used in sidebars by adding a &#8220;Custom Menu&#8221; widget on the <a href="%1$s">Widgets</a> screen. If your theme does not support the custom menus feature (the default themes, %2$s and %3$s, do), you can learn about adding this support by following the Documentation link to the side.' ), admin_url( 'widgets.php' ), 'Twenty Fifteen', 'Twenty Fourteen' ) . '</p>';
		$overview .= '<p>' . __( 'From this screen you can:' ) . '</p>';
		$overview .= '<ul><li>' . __( 'Create, edit, and delete menus' ) . '</li>';
		$overview .= '<li>' . __( 'Add, organize, and modify individual menu items' ) . '</li></ul>';

		$this->screen->add_help_tab( array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $overview
		) );

		$menu_management  = '<p>' . __( 'The menu management box at the top of the screen is used to control which menu is opened in the editor below.' ) . '</p>';
		$menu_management .= '<ul><li>' . __( 'To edit an existing menu, <strong>choose a menu from the drop down and click Select</strong>' ) . '</li>';
		$menu_management .= '<li>' . __( 'If you haven&#8217;t yet created any menus, <strong>click the &#8217;create a new menu&#8217; link</strong> to get started' ) . '</li></ul>';
		$menu_management .= '<p>' . __( 'You can assign theme locations to individual menus by <strong>selecting the desired settings</strong> at the bottom of the menu editor. To assign menus to all theme locations at once, <strong>visit the Manage Locations tab</strong> at the top of the screen.' ) . '</p>';

		$this->screen->add_help_tab( array(
			'id'      => 'menu-management',
			'title'   => __( 'Menu Management' ),
			'content' => $menu_management
		) );

		$editing_menus  = '<p>' . __( 'Each custom menu may contain a mix of links to pages, categories, custom URLs or other content types. Menu links are added by selecting items from the expanding boxes in the left-hand column below.' ) . '</p>';
		$editing_menus .= '<p>' . __( '<strong>Clicking the arrow to the right of any menu item</strong> in the editor will reveal a standard group of settings. Additional settings such as link target, CSS classes, link relationships, and link descriptions can be enabled and disabled via the Screen Options tab.' ) . '</p>';
		$editing_menus .= '<ul><li>' . __( 'Add one or several items at once by <strong>selecting the checkbox next to each item and clicking Add to Menu</strong>' ) . '</li>';
		$editing_menus .= '<li>' . __( 'To add a custom link, <strong>expand the Custom Links section, enter a URL and link text, and click Add to Menu</strong>' ) .'</li>';
		$editing_menus .= '<li>' . __( 'To reorganize menu items, <strong>drag and drop items with your mouse or use your keyboard</strong>. Drag or move a menu item a little to the right to make it a submenu' ) . '</li>';
		$editing_menus .= '<li>' . __( 'Delete a menu item by <strong>expanding it and clicking the Remove link</strong>' ) . '</li></ul>';

		$this->screen->add_help_tab( array(
			'id'      => 'editing-menus',
			'title'   => __( 'Editing Menus' ),
			'content' => $editing_menus
		) );

		$this->addSidebar();
	}

	public function addLocations() {
		$locations_overview  = '<p>' . __( 'This screen is used for globally assigning menus to locations defined by your theme.' ) . '</p>';
		$locations_overview .= '<ul><li>' . __( 'To assign menus to one or more theme locations, <strong>select a menu from each location&#8217;s drop down.</strong> When you&#8217;re finished, <strong>click Save Changes</strong>' ) . '</li>';
		$locations_overview .= '<li>' . __( 'To edit a menu currently assigned to a theme location, <strong>click the adjacent &#8217;Edit&#8217; link</strong>' ) . '</li>';
		$locations_overview .= '<li>' . __( 'To add a new menu instead of assigning an existing one, <strong>click the &#8217;Use new menu&#8217; link</strong>. Your new menu will be automatically assigned to that theme location' ) . '</li></ul>';

		$this->screen->add_help_tab( array(
			'id'      => 'locations-overview',
			'title'   => __( 'Overview' ),
			'content' => $locations_overview
		) );

		$this->addSidebar();
	}
}