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

	public function enqueueScripts() {
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

	public function getMessages() {
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
}