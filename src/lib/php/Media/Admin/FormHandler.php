<?php
namespace WP\Media\Admin;

use WP\Admin\FormHandler as AdminHandler;

class FormHandler extends AdminHandler {

	public function doTrash( $location, $post_ids = null ) {
		if ( ! isset( $post_ids ) ) {
			return;
		}

		foreach ( (array) $post_ids as $post_id ) {
			if ( ! current_user_can( 'delete_post', $post_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to move this item to the Trash.' ) );
			}

			if ( ! wp_trash_post( $post_id ) ) {
				wp_die( __( 'Error in moving to Trash.' ) );
			}
		}

		$redirect = add_query_arg(
			[ 'trashed' => count( $post_ids ), 'ids' => join( ',', $post_ids ) ],
			$location
		);
		$this->redirect( $redirect );
	}

	public function doUntrash( $location, $post_ids = null ) {
		if ( ! isset( $post_ids ) ) {
			return;
		}

		foreach ( (array) $post_ids as $post_id ) {
			if ( ! current_user_can( 'delete_post', $post_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to restore this item from the Trash.' ) );
			}

			if ( ! wp_untrash_post( $post_id ) ) {
				wp_die( __( 'Error in restoring from Trash.' ) );
			}
		}
		$redirect = add_query_arg( 'untrashed', count( $post_ids ), $location );
		$this->redirect( $redirect );
	}

	public function doDelete( $location, $post_ids = null ) {
		if ( ! isset( $post_ids ) ) {
			return;
		}

		foreach ( (array) $post_ids as $post_id_del ) {
			if ( ! current_user_can( 'delete_post', $post_id_del ) ) {
				wp_die( __( 'Sorry, you are not allowed to delete this item.' ) );
			}

			if ( ! wp_delete_attachment( $post_id_del ) ) {
				wp_die( __( 'Error in deleting.' ) );
			}
		}
		$redirect = add_query_arg( 'deleted', count( $post_ids ), $location );
		$this->redirect( $redirect );
	}

	public function doBulkActions( $id, $location, $doaction, $post_ids ) {
		/**
		 * Fires when a custom bulk action should be handled.
		 *
		 * The redirect link should be modified with success or failure feedback
		 * from the action to be used to display feedback to the user.
		 *
		 * @since 4.7.0
		 *
		 * @param string $location The redirect URL.
		 * @param string $doaction The action being taken.
		 * @param array  $post_ids The posts to take the action on.
		 */
		$redirect = apply_filters( 'handle_bulk_actions-' . $id, $location, $doaction, $post_ids );
		$this->redirect( $redirect );
	}
}