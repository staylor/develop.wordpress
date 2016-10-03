<?php
namespace WP\XMLRPC\Provider\WordPress;

trait Media {
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
	 * @return array|IXR_Error Associative array contains:
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

		$username		= $args[1];
		$password		= $args[2];
		$attachment_id	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'upload_files' ) )
			return new IXR_Error( 403, __( 'Sorry, you are not allowed to upload files.' ) );

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getMediaItem' );

		if ( ! $attachment = get_post($attachment_id) )
			return new IXR_Error( 404, __( 'Invalid attachment ID.' ) );

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
	 * @return array|IXR_Error Contains a collection of media items. See wp_xmlrpc_server::wp_getMediaItem() for a description of each item contents
	 */
	public function wp_getMediaLibrary($args) {
		$this->escape($args);

		$username	= $args[1];
		$password	= $args[2];
		$struct		= isset( $args[3] ) ? $args[3] : array() ;

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'upload_files' ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to upload files.' ) );

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getMediaLibrary' );

		$parent_id = ( isset($struct['parent_id']) ) ? absint($struct['parent_id']) : '' ;
		$mime_type = ( isset($struct['mime_type']) ) ? $struct['mime_type'] : '' ;
		$offset = ( isset($struct['offset']) ) ? absint($struct['offset']) : 0 ;
		$number = ( isset($struct['number']) ) ? absint($struct['number']) : -1 ;

		$attachments = get_posts( array('post_type' => 'attachment', 'post_parent' => $parent_id, 'offset' => $offset, 'numberposts' => $number, 'post_mime_type' => $mime_type ) );

		$attachments_struct = array();

		foreach ($attachments as $attachment )
			$attachments_struct[] = $this->_prepare_media_item( $attachment );

		return $attachments_struct;
	}
}