<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;

trait Revision {
	/**
	 * Retrieve revisions for a specific post.
	 *
	 * @since 3.5.0
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array.
	 *
	 * @uses wp_get_post_revisions()
	 * @see wp_getPost() for more on $fields
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $post_id
	 *     @type array  $fields (optional)
	 * }
	 * @return array|Error contains a collection of posts.
	 */
	public function wp_getRevisions( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$post_id
		) = $args;

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/**
			 * Filters the default revision query fields used by the given XML-RPC method.
			 *
			 * @since 3.5.0
			 *
			 * @param array  $field  An array of revision query fields.
			 * @param string $method The method name.
			 */
			$fields = apply_filters( 'xmlrpc_default_revision_fields', [
				'post_date',
				'post_date_gmt'
			], 'wp.getRevisions' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getRevisions' );

		if ( ! $post = get_post( $post_id ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit posts.' ) );
		}
		// Check if revisions are enabled.
		if ( ! wp_revisions_enabled( $post ) ) {
			return new Error( 401, __( 'Sorry, revisions are disabled.' ) );
		}
		$revisions = wp_get_post_revisions( $post_id );

		if ( ! $revisions ) {
			return [];
		}

		$struct = [];

		foreach ( $revisions as $revision ) {
			if ( ! current_user_can( 'read_post', $revision->ID ) ) {
				continue;
			}

			// Skip autosaves
			if ( wp_is_post_autosave( $revision ) ) {
				continue;
			}

			$struct[] = $this->_prepare_post( get_object_vars( $revision ), $fields );
		}

		return $struct;
	}
	/**
	 * Restore a post revision
	 *
	 * @since 3.5.0
	 *
	 * @uses wp_restore_post_revision()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $revision_id
	 * }
	 * @return bool|Error false if there was an error restoring, true if success.
	 */
	public function wp_restoreRevision( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$revision_id
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.restoreRevision' );

		if ( ! $revision = wp_get_post_revision( $revision_id ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( wp_is_post_autosave( $revision ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( ! $post = get_post( $revision->post_parent ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( ! current_user_can( 'edit_post', $revision->post_parent ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}
		// Check if revisions are disabled.
		if ( ! wp_revisions_enabled( $post ) ) {
			return new Error( 401, __( 'Sorry, revisions are disabled.' ) );
		}

		return (bool) wp_restore_post_revision( $revision_id );
	}
}