<?php
/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\User as UserView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( is_multisite() ) {
	add_filter( 'wpmu_signup_user_notification_email', 'admin_created_user_email' );
}

$view = new UserView( $app );

$form_errors = null;

$action = $view->_request->get( 'action' );

if ( 'adduser' === $action ) {

	$form_errors = $view->handler->doAddUser();

} elseif ( 'createuser' === $action ) {

	$form_errors = $view->handler->doCreateUser();

}

$view->help->addUserNew();

$app->set( 'parent_file', 'users.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

$view->enqueueScripts();

$app->set( 'title', $view->l10n->add_new_user );
if ( current_user_can( 'create_users' ) ) {
	$app->set( 'title', $view->l10n->add_new_user );
} elseif ( current_user_can( 'promote_users' ) ) {
	$app->set( 'title', $view->l10n->add_existing_user );
}

$errors = [];
if ( isset( $form_errors ) && is_wp_error( $form_errors ) ) {
	foreach ( $form_errors->get_error_messages() as $err ) {
		$errors[] = $err;
	}
}

$data = [
	'title' => $app->get( 'title' ),
	'errors' => $errors,
	'messages' => $view->getAddMessages(),
	'multisite' => is_multisite(),
	'create_users' => current_user_can( 'create_users' ),
	'manage_network_users' => current_user_can( 'manage_network_users' ),
	'add_form' => null,
	'create_form' => null,
];

$do_both = false;
if ( is_multisite() && current_user_can( 'promote_users' ) && current_user_can( 'create_users' ) ) {
	$do_both = true;
}

if ( is_multisite() ) {
	$add_form = [
		'nonce' => wp_nonce_field( 'add-user', '_wpnonce_add-user', true, false ),
	];

	if ( $do_both ) {
		$add_form['subheading'] = $view->l10n->add_existing_user;
	}

	if ( ! is_super_admin() ) {
		$add_form['description'] = __( 'Enter the email address of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' );
		$add_form['label'] = __( 'Email' );
		$add_form['type'] = 'email';
	} else {
		$add_form['description'] = __( 'Enter the email address or username of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' );
		$add_form['label'] = __( 'Email or Username' );
		$add_form['type'] = 'text';
	}

	$add_form['roles_dropdown'] = function () {
		ob_start();
		wp_dropdown_roles( get_option( 'default_role' ) );
		return ob_get_clean();
	};

	$add_form['submit_button'] = get_submit_button(
		$view->l10n->add_existing_user,
		'primary',
		'adduser',
		true,
		[ 'id' => 'addusersub' ]
	);

	$data['add_form'] = $add_form;
}

if ( current_user_can( 'create_users' ) ) {
	$create_form = [
		'description' => __( 'Create a brand new user and add them to this site.' ),
		'submit_button' => get_submit_button(
			$view->l10n->add_new_user,
			'primary',
			'createuser',
			true,
			[ 'id' => 'createusersub' ]
		)
	];

	if ( $do_both ) {
		$create_form['subheading'] = $view->l10n->add_new_user;
	}

	$new_user_role = get_option( 'default_role' );
	if ( $view->_post->has( 'createuser' ) ) {
		$create_form['user_login'] = wp_unslash( $view->_post->get( 'user_login' ) );
		$create_form['first_name'] = wp_unslash( $view->_post->get( 'first_name' ) );
		$create_form['last_name'] = wp_unslash( $view->_post->get( 'last_name' ) );
		$create_form['email'] =  wp_unslash( $view->_post->get( 'email' ) );
		$create_form['url'] = wp_unslash( $view->_post->get( 'url' ) );
		$new_user_role = wp_unslash( $view->_post->get( 'role' ) );
		$create_form['send_user_notification'] = $view->_post->get( 'send_user_notification' );
		$create_form['noconfirmation'] = checked( wp_unslash( $view->_post->get( 'noconfirmation' ) ), true, false );
		$create_form['initial_password'] = wp_generate_password( 24 );
	}

	$create_form['roles_dropdown'] = function () use ( $new_user_role ) {
		ob_start();
		wp_dropdown_roles( $new_user_role );
		return ob_get_clean();
	};

	$data['create_form'] = $create_form;
}

$view->setData( $data );

echo $view->render( 'user/new', $view );
