<?php
/**
 * Edit user administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\User as UserView;
use WP\User\User;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

$view = new UserView( $app );
$user_id = $view->_request->getInt( 'user_id' );

$current_user = wp_get_current_user();
define( 'IS_PROFILE_PAGE', ( $user_id === (int) $current_user->ID ) );

if ( ! $user_id && IS_PROFILE_PAGE ) {
	$user_id = $current_user->ID;
} elseif ( ! $user_id && ! IS_PROFILE_PAGE ) {
	wp_die( __( 'Invalid user ID.' ) );
} elseif ( ! get_userdata( $user_id ) ) {
	wp_die( __( 'Invalid user ID.' ) );
}

if ( is_multisite()
	&& ! current_user_can( 'manage_network_users' )
	&& ! IS_PROFILE_PAGE
	/**
	 * Filters whether to allow administrators on Multisite to edit every user.
	 *
	 * Enabling the user editing form via this filter also hinges on the user holding
	 * the 'manage_network_users' cap, and the logged-in user not matching the user
	 * profile open for editing.
	 *
	 * The filter was introduced to replace the EDIT_ANY_USER constant.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $allow Whether to allow editing of any user. Default true.
	 */
	&& ! apply_filters( 'enable_edit_any_user_configuration', true )
) {
	wp_die( __( 'Sorry, you are not allowed to edit this user.' ) );
}

$view->enqueueEditScripts();

$title = IS_PROFILE_PAGE ? __( 'Profile' ) : __( 'Edit User' );
if ( current_user_can( 'edit_users' ) && !IS_PROFILE_PAGE ) {
	$submenu_file = 'users.php';
} else {
	$submenu_file = 'profile.php';
}

if ( current_user_can( 'edit_users' ) && ! is_user_admin() ) {
	$parent_file = 'users.php';
} else {
	$parent_file = 'profile.php';
}

$view->help->addUserEdit();

$_wp_http_referer = $view->_request->get( 'wp_http_referer' );
$wp_http_referer = remove_query_arg( [ 'update', 'delete_count', 'user_id' ], $_wp_http_referer );

$user_can_edit = current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );

$wpdb = $app['db'];

if ( is_multisite() && IS_PROFILE_PAGE ) {

	$dismiss = $view->_get->get( 'dismiss' );

	// Execute confirmed email change. See send_confirmation_on_profile_email().
	if ( $view->_get->get( 'newuseremail' ) && $current_user->ID ) {

		$view->handler->doNewUserEmail( $current_user );

	} elseif ( $dismiss && $current_user->ID . '_new_email' === $dismiss ) {

		$view->handler->doDismissNewEmail( $current_user );

	}
}

if ( 'update' === $view->_request->get( 'action' ) ) {
	$form_errors = $view->handler->doUpdateUser( $user_id );
} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit this user.' ) );
}

$profileuser = get_user_to_edit( $user_id );

$errors = [];
if ( 'new-email' === $view->_get->get( 'error' ) ) {
	$errors[] = [
		'class' => 'notice notice-error',
		'message' => __( 'Error while saving the new email address. Please try again.' ),
	];
}

if ( ! empty( $form_errors ) && is_wp_error( $form_errors ) ) {
	foreach ( $form_errors->get_error_messages() as $error ) {
		$errors[] = [
			'class' => 'error',
			'message' => $error,
		];
	}
}

$submit_text = IS_PROFILE_PAGE ? __( 'Update Profile' ) : __( 'Update User' );

$show_avatars = get_option( 'show_avatars' );

/**
 * Filters the display of the password fields.
 *
 * @since 1.5.1
 * @since 2.8.0 Added the `$profileuser` parameter.
 * @since 4.4.0 Now evaluated only in user-edit.php.
 *
 * @param bool    $show        Whether to show the password fields. Default true.
 * @param User    $profileuser User object for the current user to edit.
 */
$show_password_fields = apply_filters( 'show_password_fields', true, $profileuser );

$sessions = WP_Session_Tokens::get_instance( $profileuser->ID );
$all_sessions = $sessions->get_all();

$show_additional_caps = count( $profileuser->caps ) > count( $profileuser->roles )
	/**
	 * Filters whether to display additional capabilities for the user.
	 *
	 * The 'Additional Capabilities' section will only be enabled if
	 * the number of the user's capabilities exceeds their number of
	 * roles.
	 *
	 * @since 2.8.0
	 *
	 * @param bool    $enable      Whether to display the capabilities. Default true.
	 * @param User    $profileuser The current User object.
	 */
	&& apply_filters( 'additional_capabilities_display', true, $profileuser );

$data = [
	'title' => $title,
	'messages' => $view->getEditMessages( $profileuser ),
	'errors' => $errors,
	'is_profile_page' => IS_PROFILE_PAGE,
	'is_network_admin' => is_network_admin(),
	'not_super_admin' =>  $profileuser->user_email !== get_site_option( 'admin_email' ) || ! is_super_admin( $profileuser->ID ),
	'super_admin_checked' => checked( is_super_admin( $profileuser->ID ), true, false ),
	'nonce' => wp_nonce_field( 'update-user_' . $user_id, '_wpnonce', true, false ),
	'action' => self_admin_url( IS_PROFILE_PAGE ? 'profile.php' : 'user-edit.php' ),
	'wp_http_referer' => $wp_http_referer,
	'user_id' => $user_id,
	'profileuser' => $profileuser,
	'submit_button' => get_submit_button( $submit_text ),
	'show_visual_editor' => ! ( IS_PROFILE_PAGE && ! $user_can_edit ),
	'visual_editor_checked' => checked( 'false', $profileuser->rich_editing ?? true, false ),
	'show_admin_color' => count( $_wp_admin_css_colors ) > 1 && has_action( 'admin_color_scheme_picker' ),
	'show_comment_shortcuts' => ! ( IS_PROFILE_PAGE && ! $user_can_edit ),
	'show_super_admin' => is_multisite() &&
		is_network_admin() &&
		! IS_PROFILE_PAGE &&
		current_user_can( 'manage_network_options' ) &&
		! isset( $super_admins ),
	'admin_bar_checked' => checked( _get_admin_bar_pref( 'front', $profileuser->ID ), true, false ),
	'show_avatars' => $show_avatars,
	'show_password_fields' => $show_password_fields,
	'has_sessions' => count( $all_sessions ) > 0,
	'has_one_session' => count( $all_sessions ) === 1,
	'log_out_all_text' => sprintf( $view->l10n->log_out_of_all_locations, $profileuser->display_name ),
	'show_additional_caps' => $show_additional_caps,
];

if ( ! empty( $profileuser->comment_shortcuts ) ) {
	$data['comment_shortcuts_checked'] = checked( 'true', $profileuser->comment_shortcuts, false );
}

if ( $show_avatars ) {
	$data['avatar'] = get_avatar( $user_id );

	if ( IS_PROFILE_PAGE ) {
		/* translators: %s: Gravatar URL */
		$description = sprintf(
			__( 'You can change your profile picture on <a href="%s">Gravatar</a>.' ),
			__( 'https://en.gravatar.com/' )
		);
	} else {
		$description = '';
	}

	/**
	 * Filters the user profile picture description displayed under the Gravatar.
	 *
	 * @since 4.4.0
	 * @since 4.7.0 Added the `$profileuser` parameter.
	 *
	 * @param string  $description The description that will be printed.
	 * @param User $profileuser The current User object.
	 */
	$data['profile_description'] = apply_filters( 'user_profile_picture_description', $description, $profileuser );
}

if ( $show_password_fields ) {
	$data['new_password'] = wp_generate_password( 24 );
}

if ( $show_additional_caps ) {
	$output = '';
	foreach ( $profileuser->caps as $cap => $value ) {
		if ( ! $app['roles']->is_role( $cap ) ) {
			if ( '' !== $output ) {
				$output .= ', ';
			}
			$output .= $value ? $cap : sprintf( __( 'Denied: %s' ), $cap );
		}
	}
	$data['additional_caps'] = $output;
}

$languages = get_available_languages();

if ( $languages ) {
	$user_locale = get_user_option( 'locale', $profileuser->ID );

	if ( 'en_US' === $user_locale ) { // en_US
		$user_locale = false;
	} elseif ( ! in_array( $user_locale, $languages, true ) ) {
		$user_locale = get_locale();
	}

	$data['languages_dropdown'] = wp_dropdown_languages( [
		'name' => 'locale',
		'id' => 'locale',
		'selected' => $user_locale,
		'languages' => $languages,
		'show_available_translations' => false,
		'echo' => false
	] );
}

if ( IS_PROFILE_PAGE ) {

} else {
	$title_link = null;

	if ( current_user_can( 'create_users' ) ) {
		$title_link = _x( 'Add New', 'user' );
	} elseif ( is_multisite() && current_user_can( 'promote_users' ) ) {
		$title_link = _x( 'Add Existing', 'user' );
	}

	// Compare user role against currently editable roles
	$user_roles = array_intersect( array_values( $profileuser->roles ), array_keys( get_editable_roles() ) );
	$user_role = reset( $user_roles );

	$data['roles_dropdown'] = function () use ( $user_role ) {
		ob_start();
		// print the full list of roles with the primary one selected.
		wp_dropdown_roles( $user_role );
		return ob_get_clean();
	};
	$data['user_role'] = $user_role;

}

include( ABSPATH . 'wp-admin/admin-header.php' );

$opts = [
	[
		'id' => 'display_nickname',
		'value' => $profileuser->nickname,
		'selected' => selected( $profileuser->display_name, $profileuser->nickname, false )
	],
	[
		'id' => 'display_username',
		'value' => $profileuser->user_login,
		'selected' => selected( $profileuser->display_name, $profileuser->user_login, false )
	],
];

if ( ! empty( $profileuser->first_name ) ) {
	$opts[] = [
		'id' => 'display_firstname',
		'value' => $profileuser->first_name,
		'selected' => selected( $profileuser->display_name, $profileuser->first_name, false )
	];
}
if ( ! empty( $profileuser->last_name ) ) {
	$opts[] = [
		'id' => 'display_lastname',
		'value' => $profileuser->last_name,
		'selected' => selected( $profileuser->display_name, $profileuser->last_name, false )
	];
}
if ( ! empty( $profileuser->first_name ) && ! empty( $profileuser->last_name ) ) {
	$firstlast = $profileuser->first_name . ' ' . $profileuser->last_name;
	$opts[] = [
		'id' => 'display_firstlast',
		'value' => $firstlast,
		'selected' => selected( $profileuser->display_name, $firstlast, false )
	];
	$lastfirst = $profileuser->last_name . ' ' . $profileuser->first_name;
	$opts[] = [
		'id' => 'display_lastfirst',
		'value' => $lastfirst,
		'selected' => selected( $profileuser->display_name, $lastfirst, false )
	];

	unset( $firstlast, $lastfirst );
}

// Only add this if it isn't duplicated elsewhere
if ( ! in_array( $profileuser->display_name, $opts ) ) {
	array_unshift( $opts, [
		'id' => 'display_displayname',
		'value' => $profileuser->display_name,
		'selected' => selected( $profileuser->display_name, $profileuser->display_name, false )
	] );
}
$data['public_display'] = array_unique( array_map( function ( $opt ) {
	$opt['value'] = trim( $opt['value'] );
	return $opt;
}, $opts ) );

$new_email = get_user_meta( $current_user->ID, '_new_email', true );
$data['pending_email'] = $new_email && $new_email['newemail'] != $current_user->user_email && $profileuser->ID == $current_user->ID;
if ( $data['pending_email'] ) {
	$data['pending_email_message'] = sprintf(
		/* translators: %s: new email */
		__( 'There is a pending change of your email to %s.' ),
		'<code>' . esc_html( $new_email['newemail'] ) . '</code>'
	) . sprintf(
		' <a href="%1$s">%2$s</a>',
		esc_url( wp_nonce_url( self_admin_url( 'profile.php?dismiss=' . $current_user->ID . '_new_email' ), 'dismiss-' . $current_user->ID . '_new_email' ) ),
		__( 'Cancel' )
	);
}

$contact_methods = [];
foreach ( wp_get_user_contact_methods( $profileuser ) as $name => $desc ) {
	$contact_methods[] = [
		'name' => $name,
		/**
		 * Filters a user contactmethod label.
		 *
		 * The dynamic portion of the filter hook, `$name`, refers to
		 * each of the keys in the contactmethods array.
		 *
		 * @since 2.9.0
		 *
		 * @param string $desc The translatable label for the contactmethod.
		 */
		'label' => apply_filters( "user_{$name}_label", $desc ),
		'value' => $profileuser->{$name},
	];
}

$data['contact_methods'] = $contact_methods;

$view->setData( $data );

$view->setActions( [
	/**
	 * Fires inside the your-profile form tag on the user editing screen.
	 *
	 * @since 3.0.0
	 */
	'user_edit_form_tag' => [],
	/**
	 * Fires in the 'Admin Color Scheme' section of the user editing screen.
	 *
	 * The section is only enabled if a callback is hooked to the action,
	 * and if there is more than one defined color scheme for the admin.
	 *
	 * @since 3.0.0
	 * @since 3.8.1 Added `$user_id` parameter.
	 *
	 * @param int $user_id The user ID.
	 */
	'admin_color_scheme_picker' => [ $user_id ],
	/**
	 * Fires at the end of the 'Personal Options' settings table on the user editing screen.
	 *
	 * @since 2.7.0
	 *
	 * @param User $profileuser The current User object.
	 */
	'personal_options' => [ $profileuser ],
	/**
	 * Fires after the 'Personal Options' settings table on the 'Your Profile' editing screen.
	 *
	 * The action only fires if the current user is editing their own profile.
	 *
	 * @since 2.0.0
	 *
	 * @param User $profileuser The current User object.
	 */
	'profile_personal_options' => [ $profileuser ],
	/**
	 * Fires after the 'About Yourself' settings table on the 'Your Profile' editing screen.
	 *
	 * The action only fires if the current user is editing their own profile.
	 *
	 * @since 2.0.0
	 *
	 * @param User $profileuser The current User object.
	 */
	'show_user_profile' => [ $profileuser ],
	/**
	 * Fires after the 'About the User' settings table on the 'Edit User' screen.
	 *
	 * @since 2.0.0
	 *
	 * @param User $profileuser The current User object.
	 */
	'edit_user_profile' => [ $profileuser ],
] );

echo $view->render( 'user/edit', $view );

include( ABSPATH . 'wp-admin/admin-footer.php');
