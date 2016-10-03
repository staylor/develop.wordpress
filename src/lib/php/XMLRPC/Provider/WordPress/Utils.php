<?php
namespace WP\XMLRPC\Provider\WordPress;

trait Utils {
	/**
	 * Prepares post data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param array $post   The unprepared post data.
	 * @param array $fields The subset of post type fields to return.
	 * @return array The prepared post data.
	 */
	protected function _prepare_post( $post, $fields ) {
		// Holds the data for this post. built up based on $fields.
		$_post = array( 'post_id' => strval( $post['ID'] ) );

		// Prepare common post fields.
		$post_fields = array(
			'post_title'        => $post['post_title'],
			'post_date'         => $this->_convert_date( $post['post_date'] ),
			'post_date_gmt'     => $this->_convert_date_gmt( $post['post_date_gmt'], $post['post_date'] ),
			'post_modified'     => $this->_convert_date( $post['post_modified'] ),
			'post_modified_gmt' => $this->_convert_date_gmt( $post['post_modified_gmt'], $post['post_modified'] ),
			'post_status'       => $post['post_status'],
			'post_type'         => $post['post_type'],
			'post_name'         => $post['post_name'],
			'post_author'       => $post['post_author'],
			'post_password'     => $post['post_password'],
			'post_excerpt'      => $post['post_excerpt'],
			'post_content'      => $post['post_content'],
			'post_parent'       => strval( $post['post_parent'] ),
			'post_mime_type'    => $post['post_mime_type'],
			'link'              => get_permalink( $post['ID'] ),
			'guid'              => $post['guid'],
			'menu_order'        => intval( $post['menu_order'] ),
			'comment_status'    => $post['comment_status'],
			'ping_status'       => $post['ping_status'],
			'sticky'            => ( $post['post_type'] === 'post' && is_sticky( $post['ID'] ) ),
		);

		// Thumbnail.
		$post_fields['post_thumbnail'] = array();
		$thumbnail_id = get_post_thumbnail_id( $post['ID'] );
		if ( $thumbnail_id ) {
			$thumbnail_size = current_theme_supports('post-thumbnail') ? 'post-thumbnail' : 'thumbnail';
			$post_fields['post_thumbnail'] = $this->_prepare_media_item( get_post( $thumbnail_id ), $thumbnail_size );
		}

		// Consider future posts as published.
		if ( $post_fields['post_status'] === 'future' )
			$post_fields['post_status'] = 'publish';

		// Fill in blank post format.
		$post_fields['post_format'] = get_post_format( $post['ID'] );
		if ( empty( $post_fields['post_format'] ) )
			$post_fields['post_format'] = 'standard';

		// Merge requested $post_fields fields into $_post.
		if ( in_array( 'post', $fields ) ) {
			$_post = array_merge( $_post, $post_fields );
		} else {
			$requested_fields = array_intersect_key( $post_fields, array_flip( $fields ) );
			$_post = array_merge( $_post, $requested_fields );
		}

		$all_taxonomy_fields = in_array( 'taxonomies', $fields );

		if ( $all_taxonomy_fields || in_array( 'terms', $fields ) ) {
			$post_type_taxonomies = get_object_taxonomies( $post['post_type'], 'names' );
			$terms = wp_get_object_terms( $post['ID'], $post_type_taxonomies );
			$_post['terms'] = array();
			foreach ( $terms as $term ) {
				$_post['terms'][] = $this->_prepare_term( $term );
			}
		}

		if ( in_array( 'custom_fields', $fields ) )
			$_post['custom_fields'] = $this->get_custom_fields( $post['ID'] );

		if ( in_array( 'enclosure', $fields ) ) {
			$_post['enclosure'] = array();
			$enclosures = (array) get_post_meta( $post['ID'], 'enclosure' );
			if ( ! empty( $enclosures ) ) {
				$encdata = explode( "\n", $enclosures[0] );
				$_post['enclosure']['url'] = trim( htmlspecialchars( $encdata[0] ) );
				$_post['enclosure']['length'] = (int) trim( $encdata[1] );
				$_post['enclosure']['type'] = trim( $encdata[2] );
			}
		}

		/**
		 * Filters XML-RPC-prepared date for the given post.
		 *
		 * @since 3.4.0
		 *
		 * @param array $_post  An array of modified post data.
		 * @param array $post   An array of post data.
		 * @param array $fields An array of post fields.
		 */
		return apply_filters( 'xmlrpc_prepare_post', $_post, $post, $fields );
	}

	/**
	 * Prepares term data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param array|object $term The unprepared term data.
	 * @return array The prepared term data.
	 */
	protected function _prepare_term( $term ) {
		$_term = $term;
		if ( ! is_array( $_term ) )
			$_term = get_object_vars( $_term );

		// For integers which may be larger than XML-RPC supports ensure we return strings.
		$_term['term_id'] = strval( $_term['term_id'] );
		$_term['term_group'] = strval( $_term['term_group'] );
		$_term['term_taxonomy_id'] = strval( $_term['term_taxonomy_id'] );
		$_term['parent'] = strval( $_term['parent'] );

		// Count we are happy to return as an integer because people really shouldn't use terms that much.
		$_term['count'] = intval( $_term['count'] );

		/**
		 * Filters XML-RPC-prepared data for the given term.
		 *
		 * @since 3.4.0
		 *
		 * @param array        $_term An array of term data.
		 * @param array|object $term  Term object or array.
		 */
		return apply_filters( 'xmlrpc_prepare_term', $_term, $term );
	}
}
