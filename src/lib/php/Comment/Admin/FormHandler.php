<?php
namespace WP\Comment\Admin;

use WP\Admin\FormHandler as AdminHandler;

class FormHandler extends AdminHandler {
	public function doBulkComments( $doaction, $pagenum ) {
		check_admin_referer( 'bulk-comments' );

		$action = $doaction;
		$comment_ids = [];

		if ( 'delete_all' === $action && $this->_request->get( 'pagegen_timestamp' ) ) {
			$comment_status = wp_unslash( $this->_request->get( 'comment_status' ) );
			$delete_time = wp_unslash( $this->_request->get( 'pagegen_timestamp' ) );

			$wpdb = $this->app['db'];
			$sql = sprint( 'SELECT comment_ID FROM %s WHERE comment_approved = %s AND %s > comment_date_gmt', $wpdb->comments );
			$query = $wpdb->prepare( $sql, $comment_status, $delete_time );
			$comment_ids = $wpdb->get_col( $query );
			$action = 'delete';
		} elseif ( $this->_request->get( 'delete_comments' ) ) {
			$comment_ids = $this->_request->get( 'delete_comments' );
			$action = ( $this->_request->get( 'action' ) != -1 ) ?
				$this->_request->get( 'action' ) :
				$this->_request->get( 'action2' );
		} elseif ( $this->_request->get( 'ids' ) ) {
			$comment_ids = array_map( 'absint', explode( ',', $this->_request->get( 'ids' ) ) );
		} elseif ( wp_get_referer() ) {
			$this->redirect( wp_get_referer() );
		}

		$approved = $unapproved = $spammed = $unspammed = $trashed = $untrashed = $deleted = 0;

		$location = remove_query_arg(
			[ 'trashed', 'untrashed', 'deleted', 'spammed', 'unspammed', 'approved', 'unapproved', 'ids' ],
			wp_get_referer()
		);
		$redirect_to = add_query_arg( 'paged', $pagenum, $location );

		wp_defer_comment_counting( true );

		// Check the permissions on each
		foreach ( $comment_ids as $comment_id ) {
			if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
				continue;
			}

			switch ( $action ) {
			case 'approve':
				wp_set_comment_status( $comment_id, 'approve' );
				$approved++;
				break;

			case 'unapprove':
				wp_set_comment_status( $comment_id, 'hold' );
				$unapproved++;
				break;

			case 'spam':
				wp_spam_comment( $comment_id );
				$spammed++;
				break;

			case 'unspam':
				wp_unspam_comment( $comment_id );
				$unspammed++;
				break;

			case 'trash':
				wp_trash_comment( $comment_id );
				$trashed++;
				break;

			case 'untrash':
				wp_untrash_comment( $comment_id );
				$untrashed++;
				break;

			case 'delete':
				wp_delete_comment( $comment_id );
				$deleted++;
				break;
			}
		}

		if ( ! in_array( $action, [ 'approve', 'unapprove', 'spam', 'unspam', 'trash', 'delete' ], true ) ) {
			/**
			 * Fires when a custom bulk action should be handled.
			 *
			 * The redirect link should be modified with success or failure feedback
			 * from the action to be used to display feedback to the user.
			 *
			 * @since 4.7.0
			 *
			 * @param string $redirect_to The redirect URL.
			 * @param string $action      The action being taken.
			 * @param array  $comment_ids The comments to take the action on.
			 */
			$redirect_to = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $redirect_to, $action, $comment_ids );
		}

		wp_defer_comment_counting( false );

		if ( $approved ) {
			$redirect_to = add_query_arg( 'approved', $approved, $redirect_to );
		}

		if ( $unapproved ) {
			$redirect_to = add_query_arg( 'unapproved', $unapproved, $redirect_to );
		}

		if ( $spammed ) {
			$redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );
		}

		if ( $unspammed ) {
			$redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );
		}

		if ( $trashed ) {
			$redirect_to = add_query_arg( 'trashed', $trashed, $redirect_to );
		}

		if ( $untrashed ) {
			$redirect_to = add_query_arg( 'untrashed', $untrashed, $redirect_to );
		}

		if ( $deleted ) {
			$redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );
		}

		if ( $trashed || $spammed ) {
			$redirect_to = add_query_arg( 'ids', join( ',', $comment_ids ), $redirect_to );
		}

		$this->redirect( $redirect_to );
	}
}