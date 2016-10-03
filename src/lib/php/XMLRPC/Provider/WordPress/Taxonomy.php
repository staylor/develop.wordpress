<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;

trait Taxonomy {
	/**
	 * Prepares taxonomy data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param object $taxonomy The unprepared taxonomy data.
	 * @param array $fields    The subset of taxonomy fields to return.
	 * @return array The prepared taxonomy data.
	 */
	protected function _prepare_taxonomy( $taxonomy, $fields ) {
		$_taxonomy = array(
			'name' => $taxonomy->name,
			'label' => $taxonomy->label,
			'hierarchical' => (bool) $taxonomy->hierarchical,
			'public' => (bool) $taxonomy->public,
			'show_ui' => (bool) $taxonomy->show_ui,
			'_builtin' => (bool) $taxonomy->_builtin,
		);

		if ( in_array( 'labels', $fields ) )
			$_taxonomy['labels'] = (array) $taxonomy->labels;

		if ( in_array( 'cap', $fields ) )
			$_taxonomy['cap'] = (array) $taxonomy->cap;

		if ( in_array( 'menu', $fields ) )
			$_taxonomy['show_in_menu'] = (bool) $_taxonomy->show_in_menu;

		if ( in_array( 'object_type', $fields ) )
			$_taxonomy['object_type'] = array_unique( (array) $taxonomy->object_type );

		/**
		 * Filters XML-RPC-prepared data for the given taxonomy.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $_taxonomy An array of taxonomy data.
		 * @param object $taxonomy  Taxonomy object.
		 * @param array  $fields    The subset of taxonomy fields to return.
		 */
		return apply_filters( 'xmlrpc_prepare_taxonomy', $_taxonomy, $taxonomy, $fields );
	}
	/**
	 * Retrieve a taxonomy.
	 *
	 * @since 3.4.0
	 *
	 * @see get_taxonomy()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type string $taxnomy  Taxonomy name.
	 *     @type array  $fields   Optional. Array of taxonomy fields to limit to in the return.
	 *                            Accepts 'labels', 'cap', 'menu', and 'object_type'.
	 *                            Default empty array.
	 * }
	 * @return array|Error An array of taxonomy data on success, Error instance otherwise.
	 */
	public function wp_getTaxonomy( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) )
			return $this->error;

		$this->escape( $args );

		$username = $args[1];
		$password = $args[2];
		$taxonomy = $args[3];

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/**
			 * Filters the taxonomy query fields used by the given XML-RPC method.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $fields An array of taxonomy fields to retrieve.
			 * @param string $method The method name.
			 */
			$fields = apply_filters( 'xmlrpc_default_taxonomy_fields', array( 'labels', 'cap', 'object_type' ), 'wp.getTaxonomy' );
		}

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getTaxonomy' );

		if ( ! taxonomy_exists( $taxonomy ) )
			return new Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $taxonomy );

		if ( ! current_user_can( $taxonomy->cap->assign_terms ) )
			return new Error( 401, __( 'Sorry, you are not allowed to assign terms in this taxonomy.' ) );

		return $this->_prepare_taxonomy( $taxonomy, $fields );
	}

	/**
	 * Retrieve all taxonomies.
	 *
	 * @since 3.4.0
	 *
	 * @see get_taxonomies()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type array  $filter   Optional. An array of arguments for retrieving taxonomies.
	 *     @type array  $fields   Optional. The subset of taxonomy fields to return.
	 * }
	 * @return array|Error An associative array of taxonomy data with returned fields determined
	 *                         by `$fields`, or an Error instance on failure.
	 */
	public function wp_getTaxonomies( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) )
			return $this->error;

		$this->escape( $args );

		$username = $args[1];
		$password = $args[2];
		$filter   = isset( $args[3] ) ? $args[3] : array( 'public' => true );

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
			$fields = apply_filters( 'xmlrpc_default_taxonomy_fields', array( 'labels', 'cap', 'object_type' ), 'wp.getTaxonomies' );
		}

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getTaxonomies' );

		$taxonomies = get_taxonomies( $filter, 'objects' );

		// holds all the taxonomy data
		$struct = array();

		foreach ( $taxonomies as $taxonomy ) {
			// capability check for post_types
			if ( ! current_user_can( $taxonomy->cap->assign_terms ) )
				continue;

			$struct[] = $this->_prepare_taxonomy( $taxonomy, $fields );
		}

		return $struct;
	}
}
