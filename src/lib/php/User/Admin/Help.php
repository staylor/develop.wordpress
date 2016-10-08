<?php
namespace WP\User\Admin;

use WP\Admin\Screen;

class Help {
	protected $screen;

	public function __construct( Screen $screen ) {
		$this->screen = $screen;
	}

	public function addUserNew() {
		$help = '<p>' . __('To add a new user to your site, fill in the form on this screen and click the Add New User button at the bottom.') . '</p>';

		if ( is_multisite() ) {
			$help .= '<p>' . __('Because this is a multisite installation, you may add accounts that already exist on the Network by specifying a username or email, and defining a role. For more options, such as specifying a password, you have to be a Network Administrator and use the hover link under an existing user&#8217;s name to Edit the user profile under Network Admin > All Users.') . '</p>' .
			'<p>' . __('New users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain their password. Check the box if you don&#8217;t want the user to receive a welcome email.') . '</p>';
		} else {
			$help .= '<p>' . __('New users are automatically assigned a password, which they can change after logging in. You can view or edit the assigned password by clicking the Show Password button. The username cannot be changed once the user has been added.') . '</p>' .

			'<p>' . __('By default, new users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain a password reset link. Uncheck the box if you don&#8217;t want to send the new user a welcome email.') . '</p>';
		}

		$help .= '<p>' . __('Remember to click the Add New User button at the bottom of this screen when you are finished.') . '</p>';

		$this->screen->add_help_tab( array(
			'id'      => 'overview',
			'title'   => __('Overview'),
			'content' => $help,
		) );

		$this->screen->add_help_tab( array(
			'id'      => 'user-roles',
			'title'   => __('User Roles'),
			'content' => '<p>' . __('Here is a basic overview of the different user roles and the permissions associated with each one:') . '</p>' .
				'<ul>' .
				'<li>' . __('Subscribers can read comments/comment/receive newsletters, etc. but cannot create regular site content.') . '</li>' .
				'<li>' . __('Contributors can write and manage their posts but not publish posts or upload media files.') . '</li>' .
				'<li>' . __('Authors can publish and manage their own posts, and are able to upload files.') . '</li>' .
				'<li>' . __('Editors can publish posts, manage posts as well as manage other people&#8217;s posts, etc.') . '</li>' .
				'<li>' . __('Administrators have access to all the administration features.') . '</li>' .
				'</ul>'
		) );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Users_Add_New_Screen">Documentation on Adding New Users</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}

