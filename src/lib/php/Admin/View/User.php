<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\User\Admin\{Help,FormHandler};

class User extends View {

	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help( get_current_screen() );
		$this->handler = new FormHandler( $app );
	}

	public function enqueueAddScripts() {
		wp_enqueue_script( 'wp-ajax-response' );
		wp_enqueue_script( 'user-profile' );

		/**
		 * Filters whether to enable user auto-complete for non-super admins in Multisite.
		 *
		 * @since 3.4.0
		 *
		 * @param bool $enable Whether to enable auto-complete for non-super admins. Default false.
		 */
		if ( is_multisite() && current_user_can( 'promote_users' ) && ! wp_is_large_network( 'users' )
			&& ( is_super_admin() || apply_filters( 'autocomplete_users_for_site_admins', false ) )
		) {
			wp_enqueue_script( 'user-suggest' );
		}
	}

	public function enqueueEditScripts() {
		wp_enqueue_script( 'user-profile' );
	}

	public function getAddMessages() {
		$messages = [];
		if ( ! $this->_get->has( 'update' ) ) {
			return $messages;
		}

		$user_id = $this->_get->getInt( 'user_id' );

		if ( is_multisite() ) {
			$edit_link = '';
			if ( $user_id ) {
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $this->app['request.uri'] ) ), get_edit_user_link( $user_id ) ) );
			}

			switch ( $this->_get->get( 'update' ) ) {
			case 'newuserconfirmation':
				$messages[] = __('Invitation email sent to new user. A confirmation link must be clicked before their account is created.');
				break;

			case 'add':
				$messages[] = __('Invitation email sent to user. A confirmation link must be clicked for them to be added to your site.');
				break;

			case 'addnoconfirmation':
				if ( empty( $edit_link ) ) {
					$messages[] = __( 'User has been added to your site.' );
				} else {
					/* translators: %s: edit page url */
					$messages[] = sprintf( __( 'User has been added to your site. <a href="%s">Edit user</a>' ), $edit_link );
				}
				break;

			case 'addexisting':
				$messages[] = __('That user is already a member of this site.');
				break;

			case 'does_not_exist':
				$messages[] = __('The requested user does not exist.');
				break;

			case 'enter_email':
				$messages[] = __('Please enter a valid email address.');
				break;
			}
		} elseif ( 'add' === $this->_get->get( 'update' ) ) {
			$messages[] = __('User added.');
		}

		return $messages;
	}

	public function getEditMessages( $profileuser ) {
		$messages = [];

		if ( ! IS_PROFILE_PAGE && is_super_admin( $profileuser->ID ) && current_user_can( 'manage_network_options' ) ) {
			$messages[] = [
				'class' => 'updated',
				'label' => __( 'Important:' ),
				'message' => __( 'This user has super admin privileges.' ),
			];
		}

		if ( ! $this->_get->get( 'updated' ) ) {
			return $messages;
		}

		if ( IS_PROFILE_PAGE ) {
			$messages[] = [
				'id' => 'message',
				'class' => 'updated notice is-dismissible',
				'label' => __( 'Profile updated.'),
				'message' => '',
			];

			return $messages;
		}

		$wp_http_referer = remove_query_arg(
			[ 'update', 'delete_count', 'user_id' ],
			$this->_request->get( 'wp_http_referer' )
		);

		$extra = '';
		if ( $wp_http_referer && false === strpos( $wp_http_referer, 'user-new.php' ) ) {
			$extra = sprintf(
				'<a href="%s">%s</a>',
				'//' . esc_url( $wp_http_referer ),
				__( '&larr; Back to Users' )
			);
		}

		$messages[] = [
			'id' => 'message',
			'class' => 'updated notice is-dismissible',
			'label' => __( 'User updated.' ),
			'message' => '',
			'extra' => $extra,
		];

		return $messages;
	}
}