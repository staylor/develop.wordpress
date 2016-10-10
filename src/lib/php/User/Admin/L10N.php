<?php
namespace WP\User\Admin;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			// User
			'remove_users_from_site' => __( 'Remove Users from Site' ),
			'specified_this_user_removal' => __( 'You have specified this user for removal:' ),
			'specified_these_users_removal' => __( 'You have specified these users for removal:' ),
			'no_valid_users_removal' => __( 'There are no valid users selected for removal.' ),
			'delete_users' => __( 'Delete Users' ),
			'specified_this_user_deletion' => __( 'You have specified this user for deletion:' ),
			'specified_these_users_deletion' => __( 'You have specified these users for deletion:' ),
			'no_valid_users_deletion' => __( 'There are no valid users selected for deletion.' ),
			'owned_by_this_user' => __( 'What should be done with content owned by this user?' ),
			'owned_by_these_users' => __( 'What should be done with content owned by these users?' ),
			'delete_all_content' => __( 'Delete all content.' ),
			'attribute_all_content_to' => __( 'Attribute all content to:' ),
			'add_new' => _x( 'Add New', 'user' ),
			'add_existing' => _x( 'Add Existing', 'user' ),
			'add_existing_user' => __( 'Add Existing User' ),
			'add_new_user' => __( 'Add New User' ),
			'role' => __( 'Role' ),
			'skip_confirmation_label' => __( 'Skip Confirmation Email' ),
			'skip_confirmation_description' => __( 'Add the user without sending an email that requires their confirmation' ),
			'username' => __( 'Username' ),
			'nickname' => __( 'Nickname' ),
			'email' => __( 'Email' ),
			'required' => __( '(required)' ),
			'first_name' => __( 'First Name' ),
			'last_name' => __( 'Last Name' ),
			'website' => __( 'Website' ),
			'password' => __( 'Password' ),
			'show_password' => __( 'Show password' ),
			'hide' => __( 'Hide' ),
			'cancel' => __( 'Cancel' ),
			'hide_password' => __( 'Hide password' ),
			'cancel_password_change' => __( 'Cancel password change' ),
			'confirm_password' => __( 'Confirm Password' ),
			'confirm_weak_password' => __( 'Confirm use of weak password' ),
			'send_user_notification' => __( 'Send User Notification' ),
			'send_user_notification_description' => __( 'Send the new user an email about their account.' ),
			'role' => __( 'Role' ),
			'no_role' => __( '&mdash; No role for this site &mdash;' ),
			'super_admin' => __( 'Super Admin' ),
			'grant_super_admin' => __( 'Grant this user super admin privileges for the Network.' ),
			'cannot_remove_super_admin' => __( 'Super admin privileges cannot be removed because this user has the network admin email.' ),
			'display_name_publicly_as' => __( 'Display name publicly as' ),
			'contact_info' => __( 'Contact Info' ),
			'personal_options' => __( 'Personal Options' ),
			'visual_editor' => __( 'Visual Editor' ),
			'visual_editor_description' => __( 'Disable the visual editor when writing' ),
			'admin_color_scheme' => __( 'Admin Color Scheme' ),
			'keyboard_shortcuts' => __( 'Keyboard Shortcuts' ),
			'comment_shortcuts_description' => __( 'Enable keyboard shortcuts for comment moderation.' ),
			'comment_shortcuts_info' => __( '<a href="https://codex.wordpress.org/Keyboard_Shortcuts" target="_blank">More information</a>' ),
			'toolbar' => __( 'Toolbar' ),
			'toolbar_description' => __( 'Show Toolbar when viewing site' ),
			'site_language' => __( 'Site Language' ),
			'name' => __( 'Name' ),
			'usernames_cannot_be_changed' => __( 'Usernames cannot be changed.' ),
			'about_yourself' => __( 'About Yourself' ),
			'about_the_user' => __( 'About the user' ),
			'biographical_info' => __( 'Biographical Info' ),
			'biographical_info_description' => __( 'Share a little biographical information to fill out your profile. This may be shown publicly.' ),
			'profile_picture' => __( 'Profile Picture' ),
			'account_management' => __( 'Account Management' ),
			'new_password' => __( 'New Password' ),
			'generate_password' => __( 'Generate Password' ),
			'repeat_new_password' => __( 'Repeat New Password' ),
			'type_new_password_again' => __( 'Type your new password again.' ),
			'confirm_use_of_weak_password' => __( 'Confirm use of potentially weak password' ),
			'sessions' => __( 'Sessions' ),
			'log_out_everywhere' => __( 'Log Out Everywhere' ),
			'log_out_everywhere_else' => __( 'Log Out Everywhere Else' ),
			'log_out_of_all_locations' => __( 'Log %s out of all locations.' ),
			'log_out_one_description' => __( 'You are only logged in at this location.' ),
			'log_out_everywhere_description' => __( ( 'Did you lose your phone or leave your account logged in at a public computer? You can log out everywhere else, and stay logged in here.' ) ),
			'additional_capabilities' => __( 'Additional Capabilities' ),
			'capabilities' => __( 'Capabilities' ),
		];
	}
}
