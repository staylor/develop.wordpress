<?php
/**
 * Add New User network administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */
use WP\Error;
use WP\User\Admin\Help as UserHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can('create_users') ) {
	wp_die(__('Sorry, you are not allowed to add users to this network.'));
}

( new UserHelp( get_current_screen() ) )->addMultisiteUserNew();

if ( 'add-user' == $_request->get( 'action' ) ) {
	check_admin_referer( 'add-user', '_wpnonce_add-user' );

	if ( ! current_user_can( 'manage_network_users' ) ) {
		wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
	}

	if ( ! is_array( $_post->get( 'user' ) ) ) {
		wp_die( __( 'Cannot create an empty user.' ) );
	}

	$user = wp_unslash( $_post->get( 'user' ) );

	$user_details = wpmu_validate_user_signup( $user['username'], $user['email'] );
	if ( is_wp_error( $user_details[ 'errors' ] ) && ! empty( $user_details[ 'errors' ]->errors ) ) {
		$add_user_errors = $user_details[ 'errors' ];
	} else {
		$password = wp_generate_password( 12, false);
		$user_id = wpmu_create_user( esc_html( strtolower( $user['username'] ) ), $password, sanitize_email( $user['email'] ) );

		if ( ! $user_id ) {
			$add_user_errors = new Error( 'add_user_fail', __( 'Cannot add user.' ) );
		} else {
			/**
			  * Fires after a new user has been created via the network user-new.php page.
			  *
			  * @since 4.4.0
			  *
			  * @param int $user_id ID of the newly created user.
			  */
			do_action( 'network_user_new_created_user', $user_id );
			wp_redirect( add_query_arg( array('update' => 'added', 'user_id' => $user_id ), 'user-new.php' ) );
			exit;
		}
	}
}

if ( $_get->get( 'update' ) ) {
	$messages = [];
	if ( 'added' == $_get->get( 'update' ) ) {
		$edit_link = '';
		if ( $_get->get( 'user_id' ) ) {
			$user_id_new = $_get->getInt( 'user_id' );
			if ( $user_id_new ) {
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $app['request.uri'] ) ), get_edit_user_link( $user_id_new ) ) );
			}
		}

		if ( empty( $edit_link ) ) {
			$messages[] = __( 'User added.' );
		} else {
			/* translators: %s: edit page url */
			$messages[] = sprintf( __( 'User added. <a href="%s">Edit user</a>' ), $edit_link );
		}
	}
}

$app->set( 'title', __( 'Add New User' ) );
$app->set( 'parent_file', 'users.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

require( ABSPATH . 'wp-admin/admin-header.php' ); ?>

<div class="wrap">
<h1 id="add-new-user"><?php _e( 'Add New User' ); ?></h1>
<?php
if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg ) {
		echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
	}
}

if ( isset( $add_user_errors ) && is_wp_error( $add_user_errors ) ) { ?>
	<div class="error">
		<?php
			foreach ( $add_user_errors->get_error_messages() as $message ) {
				echo "<p>$message</p>";
			}
		?>
	</div>
<?php } ?>
	<form action="<?php echo network_admin_url('user-new.php?action=add-user'); ?>" id="adduser" method="post" novalidate="novalidate">
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row"><label for="username"><?php _e( 'Username' ) ?></label></th>
			<td><input type="text" class="regular-text" name="user[username]" id="username" autocapitalize="none" autocorrect="off" maxlength="60" /></td>
		</tr>
		<tr class="form-field form-required">
			<th scope="row"><label for="email"><?php _e( 'Email' ) ?></label></th>
			<td><input type="email" class="regular-text" name="user[email]" id="email"/></td>
		</tr>
		<tr class="form-field">
			<td colspan="2"><?php _e( 'A password reset link will be sent to the user via email.' ) ?></td>
		</tr>
	</table>
	<?php
	/**
	 * Fires at the end of the new user form in network admin.
	 *
	 * @since 4.5.0
	 */
	do_action( 'network_user_new_form' );

	wp_nonce_field( 'add-user', '_wpnonce_add-user' );
	submit_button( __('Add User'), 'primary', 'add-user' );
	?>
	</form>
</div>
<?php
require( ABSPATH . 'wp-admin/admin-footer.php' );
