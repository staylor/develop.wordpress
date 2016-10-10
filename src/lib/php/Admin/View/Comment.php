<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Comment\Admin\Help;

class Comment extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help( get_current_screen() );
	}

	public function getEditMessages() {
		$approved = $this->_request->getInt( 'approved', 0 );
		$deleted = $this->_request->getInt( 'deleted', 0 );
		$trashed = $this->_request->getInt( 'trashed', 0 );
		$untrashed = $this->_request->getInt( 'untrashed', 0 );
		$spammed = $this->_request->getInt( 'spammed', 0 );
		$unspammed = $this->_request->getInt( 'unspammed', 0 );
		$same = $this->_request->getInt( 'same', 0 );

		$messages = [];

		if ( $approved ) {
			/* translators: %s: number of comments approved */
			$messages[] = sprintf( _n( '%s comment approved', '%s comments approved', $approved ), $approved );
		}

		if ( $spammed ) {
			$ids = $this->_request->get( 'ids', 0 );
			/* translators: %s: number of comments marked as spam */
			$messages[] = sprintf( _n( '%s comment marked as spam.', '%s comments marked as spam.', $spammed ), $spammed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=unspam&ids=$ids", "bulk-comments" ) ) . '">' . __( 'Undo' ) . '</a><br />';
		}

		if ( $unspammed ) {
			/* translators: %s: number of comments restored from the spam */
			$messages[] = sprintf( _n( '%s comment restored from the spam', '%s comments restored from the spam', $unspammed ), $unspammed );
		}

		if ( $trashed ) {
			$ids = $this->_request->get( 'ids', 0 );
			/* translators: %s: number of comments moved to the Trash */
			$messages[] = sprintf( _n( '%s comment moved to the Trash.', '%s comments moved to the Trash.', $trashed ), $trashed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=untrash&ids=$ids", "bulk-comments" ) ) . '">' . __( 'Undo' ) . '</a><br />';
		}

		if ( $untrashed ) {
			/* translators: %s: number of comments restored from the Trash */
			$messages[] = sprintf( _n( '%s comment restored from the Trash', '%s comments restored from the Trash', $untrashed ), $untrashed );
		}

		if ( $deleted > 0 ) {
			/* translators: %s: number of comments permanently deleted */
			$messages[] = sprintf( _n( '%s comment permanently deleted', '%s comments permanently deleted', $deleted ), $deleted );
		}

		if ( $same && $comment = get_comment( $same ) ) {
			switch ( $comment->comment_approved ) {
			case '1' :
				$messages[] = __( 'This comment is already approved.' ) . ' <a href="' . esc_url( admin_url( "comment.php?action=editcomment&c=$same" ) ) . '">' . __( 'Edit comment' ) . '</a>';
				break;
			case 'trash' :
				$messages[] = __( 'This comment is already in the Trash.' ) . ' <a href="' . esc_url( admin_url( 'edit-comments.php?comment_status=trash' ) ) . '"> ' . __( 'View Trash' ) . '</a>';
				break;
			case 'spam' :
				$messages[] = __( 'This comment is already marked as spam.' ) . ' <a href="' . esc_url( admin_url( "comment.php?action=editcomment&c=$same" ) ) . '">' . __( 'Edit comment' ) . '</a>';
				break;
			}
		}

		return $messages;
	}
}