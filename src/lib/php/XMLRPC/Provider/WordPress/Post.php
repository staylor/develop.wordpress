<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\{Date,Error};

trait Post {
	/**
	 * Prepares post data for return in an XML-RPC object.
	 *
	 * @since 3.4.0
	 * @since 4.6.0 Converted the `$post_type` parameter to accept a WP_Post_Type object.
	 * @access protected
	 *
	 * @param WP_Post_Type $post_type Post type object.
	 * @param array        $fields    The subset of post fields to return.
	 * @return array The prepared post type data.
	 */
	protected function _prepare_post_type( $post_type, $fields ) {
		$_post_type = [
			'name' => $post_type->name,
			'label' => $post_type->label,
			'hierarchical' => (bool) $post_type->hierarchical,
			'public' => (bool) $post_type->public,
			'show_ui' => (bool) $post_type->show_ui,
			'_builtin' => (bool) $post_type->_builtin,
			'has_archive' => (bool) $post_type->has_archive,
			'supports' => get_all_post_type_supports( $post_type->name ),
		];

		if ( in_array( 'labels', $fields ) ) {
			$_post_type['labels'] = (array) $post_type->labels;
		}

		if ( in_array( 'cap', $fields ) ) {
			$_post_type['cap'] = (array) $post_type->cap;
			$_post_type['map_meta_cap'] = (bool) $post_type->map_meta_cap;
		}

		if ( in_array( 'menu', $fields ) ) {
			$_post_type['menu_position'] = (int) $post_type->menu_position;
			$_post_type['menu_icon'] = $post_type->menu_icon;
			$_post_type['show_in_menu'] = (bool) $post_type->show_in_menu;
		}

		if ( in_array( 'taxonomies', $fields ) ) {
			$_post_type['taxonomies'] = get_object_taxonomies( $post_type->name, 'names' );
		}
		/**
		 * Filters XML-RPC-prepared date for the given post type.
		 *
		 * @since 3.4.0
		 * @since 4.6.0 Converted the `$post_type` parameter to accept a WP_Post_Type object.
		 *
		 * @param array        $_post_type An array of post type data.
		 * @param WP_Post_Type $post_type  Post type object.
		 */
		return apply_filters( 'xmlrpc_prepare_post_type', $_post_type, $post_type );
	}
	/**
	 * Create a new post for any registered post type.
	 *
	 * @since 3.4.0
	 *
	 * @link https://en.wikipedia.org/wiki/RSS_enclosure for information on RSS enclosures.
	 *
	 * @param array  $args {
	 *     Method arguments. Note: top-level arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id        Blog ID (unused).
	 *     @type string $username       Username.
	 *     @type string $password       Password.
	 *     @type array  $content_struct {
	 *         Content struct for adding a new post. See wp_insert_post() for information on
	 *         additional post fields
	 *
	 *         @type string $post_type      Post type. Default 'post'.
	 *         @type string $post_status    Post status. Default 'draft'
	 *         @type string $post_title     Post title.
	 *         @type int    $post_author    Post author ID.
	 *         @type string $post_excerpt   Post excerpt.
	 *         @type string $post_content   Post content.
	 *         @type string $post_date_gmt  Post date in GMT.
	 *         @type string $post_date      Post date.
	 *         @type string $post_password  Post password (20-character limit).
	 *         @type string $comment_status Post comment enabled status. Accepts 'open' or 'closed'.
	 *         @type string $ping_status    Post ping status. Accepts 'open' or 'closed'.
	 *         @type bool   $sticky         Whether the post should be sticky. Automatically false if
	 *                                      `$post_status` is 'private'.
	 *         @type int    $post_thumbnail ID of an image to use as the post thumbnail/featured image.
	 *         @type array  $custom_fields  Array of meta key/value pairs to add to the post.
	 *         @type array  $terms          Associative array with taxonomy names as keys and arrays
	 *                                      of term IDs as values.
	 *         @type array  $terms_names    Associative array with taxonomy names as keys and arrays
	 *                                      of term names as values.
	 *         @type array  $enclosure      {
	 *             Array of feed enclosure data to add to post meta.
	 *
	 *             @type string $url    URL for the feed enclosure.
	 *             @type int    $length Size in bytes of the enclosure.
	 *             @type string $type   Mime-type for the enclosure.
	 *         }
	 *     }
	 * }
	 * @return int|Error Post ID on success, Error instance otherwise.
	 */
	public function wp_newPost( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$content_struct
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		// convert the date field back to IXR form
		if ( isset( $content_struct['post_date'] ) && ! ( $content_struct['post_date'] instanceof Date ) ) {
			$content_struct['post_date'] = $this->_convert_date( $content_struct['post_date'] );
		}

		// ignore the existing GMT date if it is empty or a non-GMT date was supplied in $content_struct,
		// since _insert_post will ignore the non-GMT date if the GMT date is set
		if ( isset( $content_struct['post_date_gmt'] ) && ! ( $content_struct['post_date_gmt'] instanceof Date ) ) {
			if ( $content_struct['post_date_gmt'] == '0000-00-00 00:00:00' || isset( $content_struct['post_date'] ) ) {
				unset( $content_struct['post_date_gmt'] );
			} else {
				$content_struct['post_date_gmt'] = $this->_convert_date( $content_struct['post_date_gmt'] );
			}
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.newPost' );

		unset( $content_struct['ID'] );

		return $this->_insert_post( $user, $content_struct );
	}

	/**
	 * Edit a post for any registered post type.
	 *
	 * The $content_struct parameter only needs to contain fields that
	 * should be changed. All other fields will retain their existing values.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id        Blog ID (unused).
	 *     @type string $username       Username.
	 *     @type string $password       Password.
	 *     @type int    $post_id        Post ID.
	 *     @type array  $content_struct Extra content arguments.
	 * }
	 * @return true|Error True on success, Error on failure.
	 */
	public function wp_editPost( $args ) {
		if ( ! $this->minimum_args( $args, 5 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$post_id,
			$content_struct
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.editPost' );

		$post = get_post( $post_id, ARRAY_A );

		if ( empty( $post['ID'] ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( isset( $content_struct['if_not_modified_since'] ) ) {
			// If the post has been modified since the date provided, return an error.
			if ( mysql2date( 'U', $post['post_modified_gmt'] ) > $content_struct['if_not_modified_since']->getTimestamp() ) {
				return new Error( 409, __( 'There is a revision of this post that is more recent.' ) );
			}
		}

		// Convert the date field back to IXR form.
		$post['post_date'] = $this->_convert_date( $post['post_date'] );

		/*
		 * Ignore the existing GMT date if it is empty or a non-GMT date was supplied in $content_struct,
		 * since _insert_post() will ignore the non-GMT date if the GMT date is set.
		 */
		if ( $post['post_date_gmt'] == '0000-00-00 00:00:00' || isset( $content_struct['post_date'] ) ) {
			unset( $post['post_date_gmt'] );
		} else {
			$post['post_date_gmt'] = $this->_convert_date( $post['post_date_gmt'] );
		}

		$this->escape( $post );
		$merged_content_struct = array_merge( $post, $content_struct );

		$retval = $this->_insert_post( $user, $merged_content_struct );
		if ( $retval instanceof Error ) {
			return $retval;
		}

		return true;
	}

	/**
	 * Delete a post for any registered post type.
	 *
	 * @since 3.4.0
	 *
	 * @see wp_delete_post()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type int    $post_id  Post ID.
	 * }
	 * @return true|Error True on success, Error instance on failure.
	 */
	public function wp_deletePost( $args ) {
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

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.deletePost' );

		$post = get_post( $post_id, ARRAY_A );
		if ( empty( $post['ID'] ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to delete this post.' ) );
		}

		$result = wp_delete_post( $post_id );

		if ( ! $result ) {
			return new Error( 500, __( 'The post cannot be deleted.' ) );
		}

		return true;
	}
	/**
	 * Retrieve a post.
	 *
	 * @since 3.4.0
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array. This should be a list of field names. 'post_id' will
	 * always be included in the response regardless of the value of $fields.
	 *
	 * Instead of, or in addition to, individual field names, conceptual group
	 * names can be used to specify multiple fields. The available conceptual
	 * groups are 'post' (all basic fields), 'taxonomies', 'custom_fields',
	 * and 'enclosure'.
	 *
	 * @see get_post()
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type int    $post_id  Post ID.
	 *     @type array  $fields   The subset of post type fields to return.
	 * }
	 * @return array|Error Array contains (based on $fields parameter):
	 *  - 'post_id'
	 *  - 'post_title'
	 *  - 'post_date'
	 *  - 'post_date_gmt'
	 *  - 'post_modified'
	 *  - 'post_modified_gmt'
	 *  - 'post_status'
	 *  - 'post_type'
	 *  - 'post_name'
	 *  - 'post_author'
	 *  - 'post_password'
	 *  - 'post_excerpt'
	 *  - 'post_content'
	 *  - 'link'
	 *  - 'comment_status'
	 *  - 'ping_status'
	 *  - 'sticky'
	 *  - 'custom_fields'
	 *  - 'terms'
	 *  - 'categories'
	 *  - 'tags'
	 *  - 'enclosure'
	 */
	public function wp_getPost( $args ) {
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
			 * Filters the list of post query fields used by the given XML-RPC method.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $fields Array of post fields. Default array contains 'post', 'terms', and 'custom_fields'.
			 * @param string $method Method name.
			 */
			$fields = apply_filters( 'xmlrpc_default_post_fields', [
				'post',
				'terms',
				'custom_fields'
			], 'wp.getPost' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPost' );

		$post = get_post( $post_id, ARRAY_A );

		if ( empty( $post['ID'] ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}
		return $this->_prepare_post( $post, $fields );
	}

	/**
	 * Retrieve posts.
	 *
	 * @since 3.4.0
	 *
	 * @see wp_get_recent_posts()
	 * @see wp_getPost() for more on `$fields`
	 * @see get_posts() for more on `$filter` values
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id  Blog ID (unused).
	 *     @type string $username Username.
	 *     @type string $password Password.
	 *     @type array  $filter   Optional. Modifies the query used to retrieve posts. Accepts 'post_type',
	 *                            'post_status', 'number', 'offset', 'orderby', 's', and 'order'.
	 *                            Default empty array.
	 *     @type array  $fields   Optional. The subset of post type fields to return in the response array.
	 * }
	 * @return array|Error Array contains a collection of posts.
	 */
	public function wp_getPosts( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$filter = $args[3] ?? [];

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
			$fields = apply_filters( 'xmlrpc_default_post_fields', [
				'post',
				'terms',
				'custom_fields'
			], 'wp.getPosts' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPosts' );

		$query = [];

		if ( isset( $filter['post_type'] ) ) {
			$post_type = get_post_type_object( $filter['post_type'] );
			if ( ! ( (bool) $post_type ) ) {
				return new Error( 403, __( 'Invalid post type.' ) );
			}
		} else {
			$post_type = get_post_type_object( 'post' );
		}

		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit posts in this post type.' ));
		}
		$query['post_type'] = $post_type->name;

		if ( isset( $filter['post_status'] ) ) {
			$query['post_status'] = $filter['post_status'];
		}
		if ( isset( $filter['number'] ) ) {
			$query['numberposts'] = absint( $filter['number'] );
		}
		if ( isset( $filter['offset'] ) ) {
			$query['offset'] = absint( $filter['offset'] );
		}
		if ( isset( $filter['orderby'] ) ) {
			$query['orderby'] = $filter['orderby'];

			if ( isset( $filter['order'] ) ) {
				$query['order'] = $filter['order'];
			}
		}

		if ( isset( $filter['s'] ) ) {
			$query['s'] = $filter['s'];
		}

		$posts_list = wp_get_recent_posts( $query );

		if ( ! $posts_list ) {
			return [];
		}
		// Holds all the posts data.
		$struct = [];
		foreach ( $posts_list as $post ) {
			if ( ! current_user_can( 'edit_post', $post['ID'] ) ) {
				continue;
			}

			$struct[] = $this->_prepare_post( $post, $fields );
		}

		return $struct;
	}

	/**
	 * Retrieves a list of post formats used by the site.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return array|Error List of post formats, otherwise Error object.
	 */
	public function wp_getPostFormats( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details about this site.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPostFormats' );

		$formats = get_post_format_strings();

		// find out if they want a list of currently supports formats
		if ( isset( $args[3] ) && is_array( $args[3] ) ) {
			if ( $args[3]['show-supported'] && current_theme_supports( 'post-formats' ) ) {
				$supported = get_theme_support( 'post-formats' );

				$data = [];
				$data['all'] = $formats;
				$data['supported'] = $supported[0];

				$formats = $data;
			}
		}

		return $formats;
	}

	/**
	 * Retrieves a post type
	 *
	 * @since 3.4.0
	 *
	 * @see get_post_type_object()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type string $post_type_name
	 *     @type array  $fields (optional)
	 * }
	 * @return array|Error Array contains:
	 *  - 'labels'
	 *  - 'description'
	 *  - 'capability_type'
	 *  - 'cap'
	 *  - 'map_meta_cap'
	 *  - 'hierarchical'
	 *  - 'menu_position'
	 *  - 'taxonomies'
	 *  - 'supports'
	 */
	public function wp_getPostType( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$post_type_name
		) = $args;

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/**
			 * Filters the default query fields used by the given XML-RPC method.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $fields An array of post type query fields for the given method.
			 * @param string $method The method name.
			 */
			$fields = apply_filters( 'xmlrpc_default_posttype_fields', [
				'labels',
				'cap',
				'taxonomies'
			], 'wp.getPostType' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPostType' );

		if ( ! post_type_exists( $post_type_name ) ) {
			return new Error( 403, __( 'Invalid post type.' ) );
		}
		$post_type = get_post_type_object( $post_type_name );

		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post type.' ) );
		}
		return $this->_prepare_post_type( $post_type, $fields );
	}

	/**
	 * Retrieves a post types
	 *
	 * @since 3.4.0
	 *
	 * @see get_post_types()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $filter (optional)
	 *     @type array  $fields (optional)
	 * }
	 * @return array|Error
	 */
	public function wp_getPostTypes( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$filter   = $args[3] ?? [ 'public' => true ];

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
			$fields = apply_filters( 'xmlrpc_default_posttype_fields', [
				'labels',
				'cap',
				'taxonomies'
			], 'wp.getPostTypes' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPostTypes' );

		$post_types = get_post_types( $filter, 'objects' );

		$struct = [];

		foreach ( $post_types as $post_type ) {
			if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
				continue;
			}
			$struct[ $post_type->name ] = $this->_prepare_post_type( $post_type, $fields );
		}

		return $struct;
	}

	/**
	 * Retrieve post statuses.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return array|Error
	 */
	public function wp_getPostStatusList( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details about this site.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPostStatusList' );

		return get_post_statuses();
	}
}
