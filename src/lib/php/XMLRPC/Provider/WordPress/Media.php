<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;
use WP\User\User as WP_User;

/**
 * @property \WP\IXR\Error $error
 */
trait Media {
	/**
	 * @return string|void
	 */
	abstract public function escape( &$data );
	/**
	 * @return WP_User|bool
	 */
	abstract public function login( $username, $password );
	/**
	 * @return array
	 */
	abstract public function _prepare_media_item( $media_item, $thumbnail_size = 'thumbnail' );
	/**
	 * Retrieve a media item by ID
	 *
	 * @since 3.1.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $attachment_id
	 * }
	 * @return array|Error Associative array contains:
	 *  - 'date_created_gmt'
	 *  - 'parent'
	 *  - 'link'
	 *  - 'thumbnail'
	 *  - 'title'
	 *  - 'caption'
	 *  - 'description'
	 *  - 'metadata'
	 */
	public function wp_getMediaItem( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$attachment_id
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed to upload files.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getMediaItem' );

		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return new Error( 404, __( 'Invalid attachment ID.' ) );
		}
		return $this->_prepare_media_item( $attachment );
	}

	/**
	 * Retrieves a collection of media library items (or attachments)
	 *
	 * Besides the common blog_id (unused), username, and password arguments, it takes a filter
	 * array as last argument.
	 *
	 * Accepted 'filter' keys are 'parent_id', 'mime_type', 'offset', and 'number'.
	 *
	 * The defaults are as follows:
	 * - 'number' - Default is 5. Total number of media items to retrieve.
	 * - 'offset' - Default is 0. See WP_Query::query() for more.
	 * - 'parent_id' - Default is ''. The post where the media item is attached. Empty string shows all media items. 0 shows unattached media items.
	 * - 'mime_type' - Default is ''. Filter by mime type (e.g., 'image/jpeg', 'application/pdf')
	 *
	 * @since 3.1.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $struct
	 * }
	 * @return array|Error Contains a collection of media items. See wp_xmlrpc_server::wp_getMediaItem() for a description of each item contents
	 */
	public function wp_getMediaLibrary( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$struct = $args[3] ?? [];

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to upload files.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getMediaLibrary' );

		$attachments = get_posts( [
			'post_type' => 'attachment',
			'post_parent' => absint( $struct['parent_id'] ?? 0 ),
			'offset' => absint( $struct['offset'] ?? 0 ),
			'numberposts' => isset( $struct['number'] ) ? absint( $struct['number'] ) : -1,
			'post_mime_type' => $struct['mime_type'] ?? ''
		] );

		$data = [];
		foreach ( $attachments as $attachment ) {
			$data[] = $this->_prepare_media_item( $attachment );
		}
		return $data;
	}
}