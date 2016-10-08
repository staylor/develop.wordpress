<?php
namespace WP\User\Admin;

use WP\App;

class FormHandler {
	protected $app;
	protected $_request;
	protected $_post;

	public function __construct( App $app ) {
		$this->app = $app;
		$this->_request = $app['request']->attributes;
		$this->_post = $app['request']->request;

		if ( is_multisite() ) {
			if ( ! current_user_can( 'create_users' ) && ! current_user_can( 'promote_users' ) ) {
				wp_die(
					'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to add users to this network.' ) . '</p>',
					403
				);
			}
		} elseif ( ! current_user_can( 'create_users' ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>',
				403
			);
		}
	}

	public function redirect( $location ) {
		wp_redirect( $location );
		exit();
	}

	public function doAddUser() {
		check_admin_referer( 'add-user', '_wpnonce_add-user' );

		$user_details = null;
		$email = $this->_request->get( 'email' );
		$role = $this->_request->get( 'role' );

		$user_email = wp_unslash( $email );
		if ( false !== strpos( $user_email, '@' ) ) {
			$user_details = get_user_by( 'email', $user_email );
		} else {
			if ( is_super_admin() ) {
				$user_details = get_user_by( 'login', $user_email );
			} else {
				$location = add_query_arg( [ 'update' => 'enter_email' ], 'user-new.php' );
				$this->redirect( $location );
			}
		}

		if ( ! $user_details ) {
			$location = add_query_arg( [ 'update' => 'does_not_exist' ], 'user-new.php' );
			$this->redirect( $location );
		}

		if ( ! current_user_can( 'promote_user', $user_details->ID ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to add users to this network.' ) . '</p>',
				403
			);
		}

		// Adding an existing user to this blog
		$new_user_email = $user_details->user_email;
		$location = 'user-new.php';
		$username = $user_details->user_login;
		$user_id = $user_details->ID;

		if ( ( $username != null && !is_super_admin( $user_id ) ) && ( array_key_exists( $this->app->blog_id, get_blogs_of_user($user_id)) ) ) {
			$location = add_query_arg( [ 'update' => 'addexisting' ], 'user-new.php' );
			$this->redirect( $location );
		} else {
			if ( $this->_post->has( 'noconfirmation' ) && current_user_can( 'manage_network_users' ) ) {
				add_existing_user_to_blog( [
					'user_id' => $user_id,
					'role' => $role
				] );

				$location = add_query_arg( [
					'update' => 'addnoconfirmation' ,
					'user_id' => $user_id
				], 'user-new.php' );
				$this->redirect( $location );
			} else {
				$newuser_key = substr( md5( $user_id ), 0, 5 );
				add_option( 'new_user_' . $newuser_key, [
					'user_id' => $user_id,
					'email' => $user_details->user_email,
					'role' => $role
				] );

				$roles = get_editable_roles();
				$role = $roles[ $role ];

				/**
				 * Fires immediately after a user is invited to join a site, but before the notification is sent.
				 *
				 * @since 4.4.0
				 *
				 * @param int    $user_id     The invited user's ID.
				 * @param array  $role        The role of invited user.
				 * @param string $newuser_key The key of the invitation.
				 */
				do_action( 'invite_user', $user_id, $role, $newuser_key );

				/* translators: 1: Site name, 2: site URL, 3: role, 4: activation URL */
				$message = __( 'Hi,

	You\'ve been invited to join \'%1$s\' at
	%2$s with the role of %3$s.

	Please click the following link to confirm the invite:
	%4$s' );
				wp_mail( $new_user_email, sprintf( __( '[%s] Joining confirmation' ), wp_specialchars_decode( get_option( 'blogname' ) ) ), sprintf( $message, get_option( 'blogname' ), home_url(), wp_specialchars_decode( translate_user_role( $role['name'] ) ), home_url( "/newbloguser/$newuser_key/" ) ) );
				$location = add_query_arg( [ 'update' => 'add' ], 'user-new.php' );
				$this->redirect( $location );
			}
		}
	}

	public function doCreateUser() {
		check_admin_referer( 'create-user', '_wpnonce_create-user' );

		if ( ! current_user_can( 'create_users' ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>',
				403
			);
		}

		$wpdb = $this->app['db'];

		$email = $this->_request->get( 'email' );
		$user_login = $this->_request->get( 'user_login' );
		$role = $this->_request->get( 'role' );

		if ( ! is_multisite() ) {
			$user_id = edit_user();

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			if ( current_user_can( 'list_users' ) ) {
				$redirect = 'users.php?update=add&id=' . $user_id;
			} else {
				$redirect = add_query_arg( 'update', 'add', 'user-new.php' );
			}

		} else {
			// Adding a new user to this site
			$new_user_email = wp_unslash( $email );
			$user_details = wpmu_validate_user_signup( $user_login, $new_user_email );
			if ( is_wp_error( $user_details[ 'errors' ] ) && ! empty( $user_details[ 'errors' ]->errors ) ) {
				return $user_details['errors'];
			}
			/**
			 * Filters the user_login, also known as the username, before it is added to the site.
			 *
			 * @since 2.0.3
			 *
			 * @param string $user_login The sanitized username.
			 */
			$new_user_login = apply_filters( 'pre_user_login', sanitize_user( wp_unslash( $user_login ), true ) );
			if ( $this->_post->has( 'noconfirmation' ) && current_user_can( 'manage_network_users' ) ) {
				// Disable confirmation email
				add_filter( 'wpmu_signup_user_notification', '__return_false' );
				// Disable welcome email
				add_filter( 'wpmu_welcome_user_notification', '__return_false' );
			}

			wpmu_signup_user( $new_user_login, $new_user_email, [
				'add_to_blog' => $wpdb->blogid,
				'new_role' => $role
			] );

			if ( $this->_post->has( 'noconfirmation' ) && current_user_can( 'manage_network_users' ) ) {
				$sql = "SELECT activation_key FROM {$wpdb->signups} WHERE user_login = %s AND user_email = %s";
				$key = $wpdb->get_var( $wpdb->prepare( $sql, $new_user_login, $new_user_email ) );
				$new_user = wpmu_activate_signup( $key );

				if ( is_wp_error( $new_user ) ) {
					$redirect = add_query_arg( [ 'update' => 'addnoconfirmation' ], 'user-new.php' );
				} else {
					$redirect = add_query_arg( [ 'update' => 'addnoconfirmation', 'user_id' => $new_user['user_id'] ], 'user-new.php' );
				}
			} else {
				$redirect = add_query_arg( [ 'update' => 'newuserconfirmation' ], 'user-new.php' );
			}
		}

		$this->redirect( $redirect );
	}
}

