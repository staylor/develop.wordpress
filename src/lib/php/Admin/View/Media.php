<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Media\Admin\{Help,FormHandler,L10N};

class Media extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help( get_current_screen() );
		$this->handler = new FormHandler( $app );
		$this->setL10n( new L10N() );
	}

	protected function setRequestUri( $uri ) {
		$this->_server->set( 'REQUEST_URI', $uri );
	}

	public function getListMessage() {
		$ids = $this->_get->get( 'ids', '' );

		$message = '';
		if (  $this->_get->get( 'posted' ) ) {
			$message = __( 'Media file updated.' );
			$this->setRequestUri( remove_query_arg(
				[ 'posted' ],
				$this->app['request.uri']
			) );
		}

		$attached = $this->_get->getInt( 'attached' );
		if ( $attached  ) {
			if ( 1 === $attached ) {
				$message = __( 'Media file attached.' );
			} else {
				/* translators: %s: number of media files */
				$message = _n( '%s media file attached.', '%s media files attached.', $attached );
			}
			$message = sprintf( $message, number_format_i18n( $attached ) );
			$this->setRequestUri( remove_query_arg(
				[ 'detach', 'attached' ],
				$this->app['request.uri']
			) );
		}

		$detached = $this->_get->getInt( 'detach' );
		if ( $detached ) {
			if ( 1 === $detached ) {
				$message = __( 'Media file detached.' );
			} else {
				/* translators: %s: number of media files */
				$message = _n( '%s media file detached.', '%s media files detached.', $detached );
			}
			$message = sprintf( $message, number_format_i18n( $detached ) );
			$this->setRequestUri( remove_query_arg(
				[ 'detach', 'attached' ],
				$this->app['request.uri']
			) );
		}

		$deleted = $this->_get->getInt( 'deleted' );
		if ( $deleted ) {
			if ( 1 === $deleted ) {
				$message = __( 'Media file permanently deleted.' );
			} else {
				/* translators: %s: number of media files */
				$message = _n(
					'%s media file permanently deleted.',
					'%s media files permanently deleted.',
					$deleted
				);
			}
			$message = sprintf( $message, number_format_i18n( $deleted ) );
			$this->setRequestUri( remove_query_arg(
				[ 'deleted' ],
				$this->app['request.uri']
			) );
		}

		$trashed = $this->_get->getInt( 'trashed' );
		if ( $trashed ) {
			if ( 1 === $trashed ) {
				$message = __( 'Media file moved to the trash.' );
			} else {
				/* translators: %s: number of media files */
				$message = _n(
					'%s media file moved to the trash.',
					'%s media files moved to the trash.',
					$trashed
				);
			}
			$message = sprintf( $message, number_format_i18n( $trashed ) );
			$url = 'upload.php?doaction=undo&action=untrash&ids=' . $ids;
			$message .= ' <a href="' . esc_url( wp_nonce_url( $url, "bulk-media" ) ) . '">' . __( 'Undo' ) . '</a>';
			$this->setRequestUri( remove_query_arg(
				[ 'trashed' ],
				$this->app['request.uri']
			) );
		}

		$untrashed = $this->_get->getInt( 'untrashed' );
		if ( $untrashed ) {
			if ( 1 == $untrashed ) {
				$message = __( 'Media file restored from the trash.' );
			} else {
				/* translators: %s: number of media files */
				$message = _n(
					'%s media file restored from the trash.',
					'%s media files restored from the trash.',
					$untrashed
				);
			}
			$message = sprintf( $message, number_format_i18n( $untrashed ) );
			$this->setRequestUri( remove_query_arg(
				[ 'untrashed' ],
				$this->app['request.uri']
			) );
		}

		$url = 'upload.php?doaction=undo&action=untrash&ids=' . $ids;
		$messages[1] = __( 'Media file updated.' );
		$messages[2] = __( 'Media file permanently deleted.' );
		$messages[3] = __( 'Error saving media file.' );
		$messages[4] = __( 'Media file moved to the trash.' ) .
			' <a href="' . esc_url( wp_nonce_url( $url, 'bulk-media' ) ) . '">' . __( 'Undo' ) . '</a>';
		$messages[5] = __( 'Media file restored from the trash.' );

		$message_id = $this->_get->getInt( 'message' );
		if ( $message_id && isset( $messages[ $message_id ] ) ) {
			$message = $messages[ $message_id ];
			$this->setRequestUri( remove_query_arg(
				[ 'message' ],
				$this->app['request.uri']
			) );
		}

		return $message;
	}
}