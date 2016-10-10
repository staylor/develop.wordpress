<?php
namespace WP\User\Admin;

use WP\Admin\Screen;

class Help {
	protected $screen;

	public function __construct( Screen $screen ) {
		$this->screen = $screen;
	}

	public function addUsers() {
		// contextual help - choose Help on the top right of admin panel to preview this.
		$this->screen->add_help_tab( array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => '<p>' . __( 'This screen lists all the existing users for your site. Each user has one of five defined roles as set by the site admin: Site Administrator, Editor, Author, Contributor, or Subscriber. Users with roles other than Administrator will see fewer options in the dashboard navigation when they are logged in, based on their role.' ) . '</p>' .
						 '<p>' . __( 'To add a new user for your site, click the Add New button at the top of the screen or Add New in the Users menu section.' ) . '</p>'
		) ) ;

		$this->screen->add_help_tab( array(
			'id'      => 'screen-display',
			'title'   => __( 'Screen Display' ),
			'content' => '<p>' . __( 'You can customize the display of this screen in a number of ways:' ) . '</p>' .
							'<ul>' .
							'<li>' . __( 'You can hide/display columns based on your needs and decide how many users to list per screen using the Screen Options tab.' ) . '</li>' .
							'<li>' . __( 'You can filter the list of users by User Role using the text links above the users list to show All, Administrator, Editor, Author, Contributor, or Subscriber. The default view is to show all users. Unused User Roles are not listed.' ) . '</li>' .
							'<li>' . __( 'You can view all posts made by a user by clicking on the number under the Posts column.' ) . '</li>' .
							'</ul>'
		) );

		$help = '<p>' . __( 'Hovering over a row in the users list will display action links that allow you to manage users. You can perform the following actions:' ) . '</p>' .
			'<ul>' .
			'<li>' . __( 'Edit takes you to the editable profile screen for that user. You can also reach that screen by clicking on the username.' ) . '</li>';

		if ( is_multisite() ) {
			$help .= '<li>' . __( 'Remove allows you to remove a user from your site. It does not delete their content. You can also remove multiple users at once by using Bulk Actions.' ) . '</li>';
		} else {
			$help .= '<li>' . __( 'Delete brings you to the Delete Users screen for confirmation, where you can permanently remove a user from your site and delete their content. You can also delete multiple users at once by using Bulk Actions.' ) . '</li>';
		}
		$help .= '</ul>';

		$this->screen->add_help_tab( array(
			'id'      => 'actions',
			'title'   => __( 'Actions' ),
			'content' => $help,
		) );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Users_Screen">Documentation on Managing Users</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Roles_and_Capabilities">Descriptions of Roles and Capabilities</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);

		$this->screen->set_screen_reader_content( array(
			'heading_views'      => __( 'Filter users list' ),
			'heading_pagination' => __( 'Users list navigation' ),
			'heading_list'       => __( 'Users list' ),
		) );
	}

	public function addUserNew() {
		$help = '<p>' . __( 'To add a new user to your site, fill in the form on this screen and click the Add New User button at the bottom.' ) . '</p>';

		if ( is_multisite() ) {
			$help .= '<p>' . __( 'Because this is a multisite installation, you may add accounts that already exist on the Network by specifying a username or email, and defining a role. For more options, such as specifying a password, you have to be a Network Administrator and use the hover link under an existing user&#8217;s name to Edit the user profile under Network Admin > All Users.' ) . '</p>' .
			'<p>' . __( 'New users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain their password. Check the box if you don&#8217;t want the user to receive a welcome email.' ) . '</p>';
		} else {
			$help .= '<p>' . __( 'New users are automatically assigned a password, which they can change after logging in. You can view or edit the assigned password by clicking the Show Password button. The username cannot be changed once the user has been added.' ) . '</p>' .

			'<p>' . __( 'By default, new users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain a password reset link. Uncheck the box if you don&#8217;t want to send the new user a welcome email.' ) . '</p>';
		}

		$help .= '<p>' . __( 'Remember to click the Add New User button at the bottom of this screen when you are finished.' ) . '</p>';

		$this->screen->add_help_tab( array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $help,
		) );

		$this->screen->add_help_tab( array(
			'id'      => 'user-roles',
			'title'   => __( 'User Roles' ),
			'content' => '<p>' . __( 'Here is a basic overview of the different user roles and the permissions associated with each one:' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Subscribers can read comments/comment/receive newsletters, etc. but cannot create regular site content.' ) . '</li>' .
				'<li>' . __( 'Contributors can write and manage their posts but not publish posts or upload media files.' ) . '</li>' .
				'<li>' . __( 'Authors can publish and manage their own posts, and are able to upload files.' ) . '</li>' .
				'<li>' . __( 'Editors can publish posts, manage posts as well as manage other people&#8217;s posts, etc.' ) . '</li>' .
				'<li>' . __( 'Administrators have access to all the administration features.' ) . '</li>' .
				'</ul>'
		) );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Users_Add_New_Screen">Documentation on Adding New Users</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addUserEdit() {
		$profile_help = '<p>' . __( 'Your profile contains information about you (your &#8220;account&#8221;) as well as some personal options related to using WordPress.' ) . '</p>' .
			'<p>' . __( 'You can change your password, turn on keyboard shortcuts, change the color scheme of your WordPress administration screens, and turn off the WYSIWYG (Visual) editor, among other things. You can hide the Toolbar (formerly called the Admin Bar) from the front end of your site, however it cannot be disabled on the admin screens.' ) . '</p>' .
			'<p>' . __( 'Your username cannot be changed, but you can use other fields to enter your real name or a nickname, and change which name to display on your posts.' ) . '</p>' .
			'<p>' . __( 'You can log out of other devices, such as your phone or a public computer, by clicking the Log Out Everywhere Else button.' ) . '</p>' .
			'<p>' . __( 'Required fields are indicated; the rest are optional. Profile information will only be displayed if your theme is set up to do so.' ) . '</p>' .
			'<p>' . __( 'Remember to click the Update Profile button when you are finished.' ) . '</p>';

		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $profile_help,
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Users_Your_Profile_Screen">Documentation on User Profiles</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}

