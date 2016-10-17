<?php
namespace WP\User\Admin;

use WP\Admin\FormHandler as AdminHandler;

class FormHandler extends AdminHandler {

	public function doAddUser() {
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
				wp_mail( $new_user_email, sprintf( __( '[%s] Joining confirmation' ), wp_specialchars_decode( get_option( 'blogname' ) ) ), sprintf( $message, get_option( 'blogname' ), home_url(), wp_specialchars_decode( translate_user_role( $role['name'] ) ), home_url( '/newbloguser/' . $newuser_key . '/' ) ) );
				$location = add_query_arg( [ 'update' => 'add' ], 'user-new.php' );
				$this->redirect( $location );
			}
		}
	}

	/**
	 * @return array|void
	 */
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
				$sql = 'SELECT activation_key FROM ' . $wpdb->signups . ' WHERE user_login = %s AND user_email = %s';
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

	public function doUpdateUser( $user_id ) {
		check_admin_referer( 'update-user_' . $user_id );

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( __( 'Sorry, you are not allowed to edit this user.' ) );
		}

		if ( IS_PROFILE_PAGE ) {
			/**
			 * Fires before the page loads on the 'Your Profile' editing screen.
			 *
			 * The action only fires if the current user is editing their own profile.
			 *
			 * @since 2.0.0
			 *
			 * @param int $user_id The user ID.
			 */
			do_action( 'personal_options_update', $user_id );
		} else {
			/**
			 * Fires before the page loads on the 'Edit User' screen.
			 *
			 * @since 2.7.0
			 *
			 * @param int $user_id The user ID.
			 */
			do_action( 'edit_user_profile_update', $user_id );
		}

		// Update the email address in signups, if present.
		if ( is_multisite() ) {
			$user = get_userdata( $user_id );

			$wpdb = $this->app['db'];
			$email = $this->_post->get( 'email' );
			$sql = 'SELECT user_login FROM ' . $wpdb->signups . ' WHERE user_login = %s';
			if ( $user->user_login && $email && is_email( $email ) && $wpdb->get_var( $wpdb->prepare( $sql, $user->user_login ) ) ) {
				$sql = 'UPDATE ' . $wpdb->signups . ' SET user_email = %s WHERE user_login = %s';
				$wpdb->query( $wpdb->prepare( $sql, $email, $user->user_login ) );
			}
		}

		// Update the user.
		$errors = edit_user( $user_id );

		// Grant or revoke super admin status if requested.
		if ( is_multisite() && is_network_admin() && !IS_PROFILE_PAGE && current_user_can( 'manage_network_options' ) && ! $this->app['super_admins'] && empty( $this->_post->get( 'super_admin' ) ) == is_super_admin( $user_id ) ) {
			if ( empty( $this->_post->get( 'super_admin' ) ) ) {
				revoke_super_admin( $user_id );
			} else {
				grant_super_admin( $user_id );
			}

		}

		if ( ! is_wp_error( $errors ) ) {
			$location = add_query_arg( 'updated', true, get_edit_user_link( $user_id ) );
			$wp_http_referer = remove_query_arg(
				[ 'update', 'delete_count', 'user_id' ],
				$this->_request->get( 'wp_http_referer' )
			);
			if ( $wp_http_referer ) {
				$location = add_query_arg( 'wp_http_referer', urlencode( $wp_http_referer ), $location );
			}
			$this->redirect( $location );
		}

		return $errors;
	}

	public function doNewUserEmail( $current_user ) {
		$new_email = get_user_meta( $current_user->ID, '_new_email', true );
		if ( $new_email && hash_equals( $new_email['hash'], $this->_get->get( 'newuseremail' ) ) ) {
			$user = new stdClass;
			$user->ID = $current_user->ID;
			$user->user_email = esc_html( trim( $new_email[ 'newemail' ] ) );

			$wpdb = $this->app['db'];
			$sql = 'SELECT user_login FROM ' . $wpdb->signups . ' WHERE user_login = %s';
			if ( $wpdb->get_var( $wpdb->prepare( $sql, $current_user->user_login ) ) ) {
				$sql = 'UPDATE '. $wpdb->signups . ' SET user_email = %s WHERE user_login = %s';
				$wpdb->query( $wpdb->prepare( $sql, $user->user_email, $current_user->user_login ) );
			}
			wp_update_user( $user );
			delete_user_meta( $current_user->ID, '_new_email' );

			$location = add_query_arg( [ 'updated' => 'true' ], self_admin_url( 'profile.php' ) );
			$this->redirect( $location );
		}

		$location = add_query_arg( [ 'error' => 'new-email' ], self_admin_url( 'profile.php' ) );
		$this->redirect( $location );
	}

	public function doDismissNewEmail( $current_user ) {
		check_admin_referer( 'dismiss-' . $current_user->ID . '_new_email' );
		delete_user_meta( $current_user->ID, '_new_email' );

		$location = add_query_arg( [ 'updated' => 'true' ], self_admin_url( 'profile.php' ) );
		$this->redirect( $location );
	}

	public function doPromoteUsers( $redirect ) {
		check_admin_referer( 'bulk-users' );

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_die( __( 'You can&#8217;t edit that user.' ) );
		}

		if ( empty( $this->_request->get( 'users' ) ) ) {
			$this->redirect( $redirect );
		}

		$editable_roles = get_editable_roles();
		$role = false;
		if ( ! empty( $this->_request->get( 'new_role2' ) ) ) {
			$role = $this->_request->get( 'new_role2' );
		} elseif ( ! empty( $this->_request->get( 'new_role' ) ) ) {
			$role = $this->_request->get( 'new_role' );
		}

		if ( ! $role || empty( $editable_roles[ $role ] ) ) {
			wp_die( __( 'You can&#8217;t give users that role.' ) );
		}

		$current_user_id = get_current_user_id();
		$userids = array_map( 'intval', $this->_request->get( 'users' ) );
		$update = 'promote';
		foreach ( $userids as $id ) {
			if ( ! current_user_can( 'promote_user', $id ) ) {
				wp_die( __( 'You can&#8217;t edit that user.' ) );
			}
			// The new role of the current user must also have the promote_users cap or be a multisite super admin
			if (
				$id == $current_user_id &&
				! $this->app['roles']->role_objects[ $role ]->has_cap( 'promote_users' )
				&& ! ( is_multisite() && is_super_admin() )
			) {
				$update = 'err_admin_role';
				continue;
			}

			// If the user doesn't already belong to the blog, bail.
			if ( is_multisite() && ! is_user_member_of_blog( $id ) ) {
				wp_die(
					'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
					'<p>' . __( 'One of the selected users is not a member of this site.' ) . '</p>',
					403
				);
			}

			$user = get_userdata( $id );
			$user->set_role( $role );
		}

		$location = add_query_arg( 'update', $update, $redirect );
		$this->redirect( $location );
	}

	public function doDeleteOrReassign( $redirect ) {
		if ( is_multisite() ) {
			wp_die( __('User deletion is not allowed from this screen.') );
		}
		check_admin_referer( 'delete-users' );

		if ( empty( $this->_request->get( 'users' ) ) ) {
			$this->redirect( $redirect );
		}

		$userids = array_map( 'intval', (array) $this->_request->get( 'users' ) );

		if ( empty( $this->_request->get( 'delete_option' ) ) ) {
			$url = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) . '&error=true' );
			$location = str_replace( '&amp;', '&', wp_nonce_url( $url, 'bulk-users' ) );
			$this->redirect( $location );
		}

		if ( ! current_user_can( 'delete_users' ) ) {
			wp_die( __( 'You can&#8217;t delete users.' ) );
		}

		$update = 'del';
		$delete_count = 0;
		$current_user_id = get_current_user_id();

		foreach ( $userids as $id ) {
			if ( ! current_user_can( 'delete_user', $id ) ) {
				wp_die( __( 'You can&#8217;t delete that user.' ) );
			}

			if ( $id === $current_user_id ) {
				$update = 'err_admin_del';
				continue;
			}

			switch ( $this->_request->get( 'delete_option' ) ) {
			case 'delete':
				wp_delete_user( $id );
				break;

			case 'reassign':
				wp_delete_user( $id, $this->_request->getInt( 'reassign_user' ) );
				break;
			}
			++$delete_count;
		}

		$location = add_query_arg(
			[ 'delete_count' => $delete_count, 'update' => $update ],
			$redirect
		);
		$this->redirect( $location );
	}

	public function doDoRemove( $redirect ) {
		check_admin_referer( 'remove-users' );

		if ( ! is_multisite() ) {
			wp_die( __( 'You can&#8217;t remove users.' ) );
		}

		if ( empty( $this->_request->get( 'users' ) ) ) {
			$this->redirect( $redirect );
		}

		if ( ! current_user_can( 'remove_users' ) ) {
			wp_die( __( 'You can&#8217;t remove users.' ) );
		}

		$userids = array_map( 'intval', (array) $this->_request->get( 'users' ) );

		$update = 'remove';
		$current_user_id = get_current_user_id();
		$blog_id = get_current_blog_id();

		foreach ( $userids as $id ) {
			if ( $id === $current_user_id && ! is_super_admin() ) {
				$update = 'err_admin_remove';
				continue;
			}

			if ( ! current_user_can( 'remove_user', $id ) ) {
				$update = 'err_admin_remove';
				continue;
			}
			remove_user_from_blog( $id, $blog_id );
		}

		$location = add_query_arg(
			[ 'update' => $update ],
			$redirect
		);
		$this->redirect( $location );
	}
}

