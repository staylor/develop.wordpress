<?php
namespace WP\Dashboard;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addIndex() {
		$help = '<p>' . __( 'Welcome to your WordPress Dashboard! This is the screen you will see when you log in to your site, and gives you access to all the site management features of WordPress. You can get help for any screen by clicking the Help tab above the screen title.' ) . '</p>';

		$this->addOverview( $help );

		// Help tabs

		$help  = '<p>' . __( 'The left-hand navigation menu provides links to all of the WordPress administration screens, with submenu items displayed on hover. You can minimize this menu to a narrow icon strip by clicking on the Collapse Menu arrow at the bottom.' ) . '</p>';
		$help .= '<p>' . __( 'Links in the Toolbar at the top of the screen connect your dashboard and the front end of your site, and provide access to your profile and helpful WordPress information.' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'help-navigation',
			'title'   => __( 'Navigation' ),
			'content' => $help,
		] );

		$help  = '<p>' . __( 'You can use the following controls to arrange your Dashboard screen to suit your workflow. This is true on most other administration screens as well.' ) . '</p>';
		$help .= '<p>' . __( '<strong>Screen Options</strong> &mdash; Use the Screen Options tab to choose which Dashboard boxes to show.' ) . '</p>';
		$help .= '<p>' . __( '<strong>Drag and Drop</strong> &mdash; To rearrange the boxes, drag and drop by clicking on the title bar of the selected box and releasing when you see a gray dotted-line rectangle appear in the location you want to place the box.' ) . '</p>';
		$help .= '<p>' . __( '<strong>Box Controls</strong> &mdash; Click the title bar of the box to expand or collapse it. Some boxes added by plugins may have configurable content, and will show a &#8220;Configure&#8221; link in the title bar if you hover over it.' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'help-layout',
			'title'   => __( 'Layout' ),
			'content' => $help,
		] );

		$help  = '<p>' . __( 'The boxes on your Dashboard screen are:' ) . '</p>';
		if ( current_user_can( 'edit_posts' ) ) {
			$help .= '<p>' . __( '<strong>At A Glance</strong> &mdash; Displays a summary of the content on your site and identifies which theme and version of WordPress you are using.' ) . '</p>';
			$help .= '<p>' . __( '<strong>Activity</strong> &mdash; Shows the upcoming scheduled posts, recently published posts, and the most recent comments on your posts and allows you to moderate them.' ) . '</p>';
		}

		if ( is_blog_admin() && current_user_can( 'edit_posts' ) ) {
			$help .= '<p>' . __( "<strong>Quick Draft</strong> &mdash; Allows you to create a new post and save it as a draft. Also displays links to the 5 most recent draft posts you've started." ) . '</p>';
		}

		if ( ! is_multisite() && current_user_can( 'install_plugins' ) ) {
			$help .= '<p>' . sprintf(
				/* translators: %s: WordPress Planet URL */
				__( '<strong>WordPress News</strong> &mdash; Latest news from the official WordPress project, the <a href="%s">WordPress Planet</a>, and popular plugins.' ),
				__( 'https://planet.wordpress.org/' )
			) . '</p>';
		} else {
			$help .= '<p>' . sprintf(
				/* translators: %s: WordPress Planet URL */
				__( '<strong>WordPress News</strong> &mdash; Latest news from the official WordPress project and the <a href="%s">WordPress Planet</a>.' ),
				__( 'https://planet.wordpress.org/' )
			) . '</p>';
		}

		if ( current_user_can( 'edit_theme_options' ) ) {
			$help .= '<p>' . __( '<strong>Welcome</strong> &mdash; Shows links for some of the most common tasks when setting up a new site.' ) . '</p>';
		}

		$this->screen->add_help_tab( [
			'id'      => 'help-content',
			'title'   => __( 'Content' ),
			'content' => $help,
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Dashboard_Screen">Documentation on Dashboard</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addMultisiteIndex() {
		$overview = '<p>' . __( 'Welcome to your Network Admin. This area of the Administration Screens is used for managing all aspects of your Multisite Network.' ) . '</p>';
		$overview .= '<p>' . __( 'From here you can:' ) . '</p>';
		$overview .= '<ul><li>' . __( 'Add and manage sites or users' ) . '</li>';
		$overview .= '<li>' . __( 'Install and activate themes or plugins' ) . '</li>';
		$overview .= '<li>' . __( 'Update your network' ) . '</li>';
		$overview .= '<li>' . __( 'Modify global network settings' ) . '</li></ul>';

		$this->addOverview( $overview );

		$quick_tasks = '<p>' . __( 'The Right Now widget on this screen provides current user and site counts on your network.' ) . '</p>';
		$quick_tasks .= '<ul><li>' . __( 'To add a new user, <strong>click Create a New User</strong>.' ) . '</li>';
		$quick_tasks .= '<li>' . __( 'To add a new site, <strong>click Create a New Site</strong>.' ) . '</li></ul>';
		$quick_tasks .= '<p>' . __( 'To search for a user or site, use the search boxes.' ) . '</p>';
		$quick_tasks .= '<ul><li>' . __( 'To search for a user, <strong>enter an email address or username</strong>. Use a wildcard to search for a partial username, such as user&#42;.' ) . '</li>';
		$quick_tasks .= '<li>' . __( 'To search for a site, <strong>enter the path or domain</strong>.' ) . '</li></ul>';

		$this->screen->add_help_tab( [
			'id'      => 'quick-tasks',
			'title'   => __( 'Quick Tasks' ),
			'content' => $quick_tasks
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin">Documentation on the Network Admin</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
		);
	}
}