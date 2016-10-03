<?php
namespace WP\XMLRPC\Provider\WordPress;

trait Term {
	/**
	 * Create a new term.
	 *
	 * @since 3.4.0
	 *
	 * @see wp_insert_term()
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id        Blog ID (unused).
	 *     @type string $username       Username.
	 *     @type string $password       Password.
	 *     @type array  $content_struct Content struct for adding a new term. The struct must contain
	 *                                  the term 'name' and 'taxonomy'. Optional accepted values include
	 *                                  'parent', 'description', and 'slug'.
	 * }
	 * @return int|IXR_Error The term ID on success, or an IXR_Error object on failure.
	 */
	public function wp_newTerm( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) )
			return $this->error;

		$this->escape( $args );

		$username       = $args[1];
		$password       = $args[2];
		$content_struct = $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.newTerm' );

		if ( ! taxonomy_exists( $content_struct['taxonomy'] ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $content_struct['taxonomy'] );

		if ( ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to create terms in this taxonomy.' ) );
		}

		$taxonomy = (array) $taxonomy;

		// hold the data of the term
		$term_data = array();

		$term_data['name'] = trim( $content_struct['name'] );
		if ( empty( $term_data['name'] ) )
			return new IXR_Error( 403, __( 'The term name cannot be empty.' ) );

		if ( isset( $content_struct['parent'] ) ) {
			if ( ! $taxonomy['hierarchical'] )
				return new IXR_Error( 403, __( 'This taxonomy is not hierarchical.' ) );

			$parent_term_id = (int) $content_struct['parent'];
			$parent_term = get_term( $parent_term_id , $taxonomy['name'] );

			if ( is_wp_error( $parent_term ) )
				return new IXR_Error( 500, $parent_term->get_error_message() );

			if ( ! $parent_term )
				return new IXR_Error( 403, __( 'Parent term does not exist.' ) );

			$term_data['parent'] = $content_struct['parent'];
		}

		if ( isset( $content_struct['description'] ) )
			$term_data['description'] = $content_struct['description'];

		if ( isset( $content_struct['slug'] ) )
			$term_data['slug'] = $content_struct['slug'];

		$term = wp_insert_term( $term_data['name'] , $taxonomy['name'] , $term_data );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 500, __( 'Sorry, your term could not be created.' ) );

		return strval( $term['term_id'] );
	}

	/**
	 * Edit a term.
	 *
	 * @since 3.4.0
	 *
	 * @see wp_update_term()
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id        Blog ID (unused).
	 *     @type string $username       Username.
	 *     @type string $password       Password.
	 *     @type int    $term_id        Term ID.
	 *     @type array  $content_struct Content struct for editing a term. The struct must contain the
	 *                                  term ''taxonomy'. Optional accepted values include 'name', 'parent',
	 *                                  'description', and 'slug'.
	 * }
	 * @return true|IXR_Error True on success, IXR_Error instance on failure.
	 */
	public function wp_editTerm( $args ) {
		if ( ! $this->minimum_args( $args, 5 ) )
			return $this->error;

		$this->escape( $args );

		$username       = $args[1];
		$password       = $args[2];
		$term_id        = (int) $args[3];
		$content_struct = $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.editTerm' );

		if ( ! taxonomy_exists( $content_struct['taxonomy'] ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $content_struct['taxonomy'] );

		$taxonomy = (array) $taxonomy;

		// hold the data of the term
		$term_data = array();

		$term = get_term( $term_id , $content_struct['taxonomy'] );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __( 'Invalid term ID.' ) );

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit this term.' ) );
		}

		if ( isset( $content_struct['name'] ) ) {
			$term_data['name'] = trim( $content_struct['name'] );

			if ( empty( $term_data['name'] ) )
				return new IXR_Error( 403, __( 'The term name cannot be empty.' ) );
		}

		if ( ! empty( $content_struct['parent'] ) ) {
			if ( ! $taxonomy['hierarchical'] )
				return new IXR_Error( 403, __( "This taxonomy is not hierarchical so you can't set a parent." ) );

			$parent_term_id = (int) $content_struct['parent'];
			$parent_term = get_term( $parent_term_id , $taxonomy['name'] );

			if ( is_wp_error( $parent_term ) )
				return new IXR_Error( 500, $parent_term->get_error_message() );

			if ( ! $parent_term )
				return new IXR_Error( 403, __( 'Parent term does not exist.' ) );

			$term_data['parent'] = $content_struct['parent'];
		}

		if ( isset( $content_struct['description'] ) )
			$term_data['description'] = $content_struct['description'];

		if ( isset( $content_struct['slug'] ) )
			$term_data['slug'] = $content_struct['slug'];

		$term = wp_update_term( $term_id , $taxonomy['name'] , $term_data );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 500, __( 'Sorry, editing the term failed.' ) );

		return true;
	}

	/**
	 * Delete a term.
	 *
	 * @since 3.4.0
	 *
	 * @see wp_delete_term()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id      Blog ID (unused).
	 *     @type string $username     Username.
	 *     @type string $password     Password.
	 *     @type string $taxnomy_name Taxonomy name.
	 *     @type int    $term_id      Term ID.
	 * }
	 * @return bool|IXR_Error True on success, IXR_Error instance on failure.
	 */
	public function wp_deleteTerm( $args ) {
		if ( ! $this->minimum_args( $args, 5 ) )
			return $this->error;

		$this->escape( $args );

		$username           = $args[1];
		$password           = $args[2];
		$taxonomy           = $args[3];
		$term_id            = (int) $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.deleteTerm' );

		if ( ! taxonomy_exists( $taxonomy ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $taxonomy );
		$term = get_term( $term_id, $taxonomy->name );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __( 'Invalid term ID.' ) );

		if ( ! current_user_can( 'delete_term', $term_id ) ) {
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to delete this term.' ) );
		}

		$result = wp_delete_term( $term_id, $taxonomy->name );

		if ( is_wp_error( $result ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $result )
			return new IXR_Error( 500, __( 'Sorry, deleting the term failed.' ) );

		return $result;
	}

	/**
	 * Retrieve a term.
	 *
	 * @since 3.4.0
	 *
	 * @see get_term()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type string $taxnomy  Taxonomy name.
	 *     @type string $term_id  Term ID.
	 * }
	 * @return array|IXR_Error IXR_Error on failure, array on success, containing:
	 *  - 'term_id'
	 *  - 'name'
	 *  - 'slug'
	 *  - 'term_group'
	 *  - 'term_taxonomy_id'
	 *  - 'taxonomy'
	 *  - 'description'
	 *  - 'parent'
	 *  - 'count'
	 */
	public function wp_getTerm( $args ) {
		if ( ! $this->minimum_args( $args, 5 ) )
			return $this->error;

		$this->escape( $args );

		$username           = $args[1];
		$password           = $args[2];
		$taxonomy           = $args[3];
		$term_id            = (int) $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getTerm' );

		if ( ! taxonomy_exists( $taxonomy ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $taxonomy );

		$term = get_term( $term_id , $taxonomy->name, ARRAY_A );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __( 'Invalid term ID.' ) );

		if ( ! current_user_can( 'assign_term', $term_id ) ) {
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to assign this term.' ) );
		}

		return $this->_prepare_term( $term );
	}

	/**
	 * Retrieve all terms for a taxonomy.
	 *
	 * @since 3.4.0
	 *
	 * The optional $filter parameter modifies the query used to retrieve terms.
	 * Accepted keys are 'number', 'offset', 'orderby', 'order', 'hide_empty', and 'search'.
	 *
	 * @see get_terms()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type string $taxnomy  Taxonomy name.
	 *     @type array  $filter   Optional. Modifies the query used to retrieve posts. Accepts 'number',
	 *                            'offset', 'orderby', 'order', 'hide_empty', and 'search'. Default empty array.
	 * }
	 * @return array|IXR_Error An associative array of terms data on success, IXR_Error instance otherwise.
	 */
	public function wp_getTerms( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) )
			return $this->error;

		$this->escape( $args );

		$username       = $args[1];
		$password       = $args[2];
		$taxonomy       = $args[3];
		$filter         = isset( $args[4] ) ? $args[4] : array();

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getTerms' );

		if ( ! taxonomy_exists( $taxonomy ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $taxonomy );

		if ( ! current_user_can( $taxonomy->cap->assign_terms ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to assign terms in this taxonomy.' ) );

		$query = array();

		if ( isset( $filter['number'] ) )
			$query['number'] = absint( $filter['number'] );

		if ( isset( $filter['offset'] ) )
			$query['offset'] = absint( $filter['offset'] );

		if ( isset( $filter['orderby'] ) ) {
			$query['orderby'] = $filter['orderby'];

			if ( isset( $filter['order'] ) )
				$query['order'] = $filter['order'];
		}

		if ( isset( $filter['hide_empty'] ) )
			$query['hide_empty'] = $filter['hide_empty'];
		else
			$query['get'] = 'all';

		if ( isset( $filter['search'] ) )
			$query['search'] = $filter['search'];

		$terms = get_terms( $taxonomy->name, $query );

		if ( is_wp_error( $terms ) )
			return new IXR_Error( 500, $terms->get_error_message() );

		$struct = array();

		foreach ( $terms as $term ) {
			$struct[] = $this->_prepare_term( $term );
		}

		return $struct;
	}

	/**
	 * Get list of all tags
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return array|IXR_Error
	 */
	public function wp_getTags( $args ) {
		$this->escape( $args );

		$username = $args[1];
		$password = $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to edit posts on this site in order to view tags.' ) );

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getKeywords' );

		$tags = array();

		if ( $all_tags = get_tags() ) {
			foreach ( (array) $all_tags as $tag ) {
				$struct = array();
				$struct['tag_id']			= $tag->term_id;
				$struct['name']				= $tag->name;
				$struct['count']			= $tag->count;
				$struct['slug']				= $tag->slug;
				$struct['html_url']			= esc_html( get_tag_link( $tag->term_id ) );
				$struct['rss_url']			= esc_html( get_tag_feed_link( $tag->term_id ) );

				$tags[] = $struct;
			}
		}

		return $tags;
	}

	/**
	 * Create new category.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $category
	 * }
	 * @return int|IXR_Error Category ID.
	 */
	public function wp_newCategory( $args ) {
		$this->escape( $args );

		$username = $args[1];
		$password = $args[2];
		$category = $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.newCategory' );

		// Make sure the user is allowed to add a category.
		if ( !current_user_can('manage_categories') )
			return new IXR_Error(401, __('Sorry, you are not allowed to add a category.'));

		// If no slug was provided make it empty so that
		// WordPress will generate one.
		if ( empty($category['slug']) )
			$category['slug'] = '';

		// If no parent_id was provided make it empty
		// so that it will be a top level page (no parent).
		if ( !isset($category['parent_id']) )
			$category['parent_id'] = '';

		// If no description was provided make it empty.
		if ( empty($category["description"]) )
			$category["description"] = "";

		$new_category = array(
			'cat_name'				=> $category['name'],
			'category_nicename'		=> $category['slug'],
			'category_parent'		=> $category['parent_id'],
			'category_description'	=> $category['description']
		);

		$cat_id = wp_insert_category($new_category, true);
		if ( is_wp_error( $cat_id ) ) {
			if ( 'term_exists' == $cat_id->get_error_code() )
				return (int) $cat_id->get_error_data();
			else
				return new IXR_Error(500, __('Sorry, the new category failed.'));
		} elseif ( ! $cat_id ) {
			return new IXR_Error(500, __('Sorry, the new category failed.'));
		}

		/**
		 * Fires after a new category has been successfully created via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $cat_id ID of the new category.
		 * @param array $args   An array of new category arguments.
		 */
		do_action( 'xmlrpc_call_success_wp_newCategory', $cat_id, $args );

		return $cat_id;
	}

	/**
	 * Remove category.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $category_id
	 * }
	 * @return bool|IXR_Error See wp_delete_term() for return info.
	 */
	public function wp_deleteCategory( $args ) {
		$this->escape( $args );

		$username    = $args[1];
		$password    = $args[2];
		$category_id = (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.deleteCategory' );

		if ( !current_user_can('manage_categories') )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to delete a category.' ) );

		$status = wp_delete_term( $category_id, 'category' );

		if ( true == $status ) {
			/**
			 * Fires after a category has been successfully deleted via XML-RPC.
			 *
			 * @since 3.4.0
			 *
			 * @param int   $category_id ID of the deleted category.
			 * @param array $args        An array of arguments to delete the category.
			 */
			do_action( 'xmlrpc_call_success_wp_deleteCategory', $category_id, $args );
		}

		return $status;
	}

	/**
	 * Retrieve category list.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $category
	 *     @type int    $max_results
	 * }
	 * @return array|IXR_Error
	 */
	public function wp_suggestCategories( $args ) {
		$this->escape( $args );

		$username    = $args[1];
		$password    = $args[2];
		$category    = $args[3];
		$max_results = (int) $args[4];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to edit posts on this site in order to view categories.' ) );

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.suggestCategories' );

		$category_suggestions = array();
		$args = array('get' => 'all', 'number' => $max_results, 'name__like' => $category);
		foreach ( (array) get_categories($args) as $cat ) {
			$category_suggestions[] = array(
				'category_id'	=> $cat->term_id,
				'category_name'	=> $cat->name
			);
		}

		return $category_suggestions;
	}
}
