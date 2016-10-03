<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;

trait User {
	/**
	 * Prepares user data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param WP_User $user   The unprepared user object.
	 * @param array   $fields The subset of user fields to return.
	 * @return array The prepared user data.
	 */
	protected function _prepare_user( $user, $fields ) {
		$_user = [ 'user_id' => strval( $user->ID ) ];

		$user_fields = [
			'username'          => $user->user_login,
			'first_name'        => $user->user_firstname,
			'last_name'         => $user->user_lastname,
			'registered'        => $this->_convert_date( $user->user_registered ),
			'bio'               => $user->user_description,
			'email'             => $user->user_email,
			'nickname'          => $user->nickname,
			'nicename'          => $user->user_nicename,
			'url'               => $user->user_url,
			'display_name'      => $user->display_name,
			'roles'             => $user->roles,
		];

		if ( in_array( 'all', $fields ) ) {
			$_user = array_merge( $_user, $user_fields );
		} else {
			if ( in_array( 'basic', $fields ) ) {
				$fields = array_merge( $fields, [
					'username',
					'email',
					'registered',
					'display_name',
					'nicename'
				] );
			}
			$requested_fields = array_intersect_key( $user_fields, array_flip( $fields ) );
			$_user = array_merge( $_user, $requested_fields );
		}

		/**
		 * Filters XML-RPC-prepared data for the given user.
		 *
		 * @since 3.5.0
		 *
		 * @param array   $_user  An array of user data.
		 * @param WP_User $user   User object.
		 * @param array   $fields An array of user fields.
		 */
		return apply_filters( 'xmlrpc_prepare_user', $_user, $user, $fields );
	}
	/**
	 * Retrieve a user.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array. This should be a list of field names. 'user_id' will
	 * always be included in the response regardless of the value of $fields.
	 *
	 * Instead of, or in addition to, individual field names, conceptual group
	 * names can be used to specify multiple fields. The available conceptual
	 * groups are 'basic' and 'all'.
	 *
	 * @uses get_userdata()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $user_id
	 *     @type array  $fields (optional)
	 * }
	 * @return array|Error Array contains (based on $fields parameter):
	 *  - 'user_id'
	 *  - 'username'
	 *  - 'first_name'
	 *  - 'last_name'
	 *  - 'registered'
	 *  - 'bio'
	 *  - 'email'
	 *  - 'nickname'
	 *  - 'nicename'
	 *  - 'url'
	 *  - 'display_name'
	 *  - 'roles'
	 */
	public function wp_getUser( $args ) {
		if ( ! $this->minimum_args( $args, 4 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$user_id
		) = $args;

		if ( isset( $args[4] ) ) {
			$fields = $args[4];
		} else {
			/**
			 * Filters the default user query fields used by the given XML-RPC method.
			 *
			 * @since 3.5.0
			 *
			 * @param array  $fields User query fields for given method. Default 'all'.
			 * @param string $method The method name.
			 */
			$fields = apply_filters( 'xmlrpc_default_user_fields', [ 'all' ], 'wp.getUser' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getUser' );

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this user.' ) );
		}
		$user_data = get_userdata( $user_id );

		if ( ! $user_data ) {
			return new Error( 404, __( 'Invalid user ID.' ) );
		}
		return $this->_prepare_user( $user_data, $fields );
	}

	/**
	 * Retrieve users.
	 *
	 * The optional $filter parameter modifies the query used to retrieve users.
	 * Accepted keys are 'number' (default: 50), 'offset' (default: 0), 'role',
	 * 'who', 'orderby', and 'order'.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array.
	 *
	 * @uses get_users()
	 * @see wp_getUser() for more on $fields and return values
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
	 * @return array|Error users data
	 */
	public function wp_getUsers( $args ) {
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
			$fields = apply_filters( 'xmlrpc_default_user_fields', [ 'all' ], 'wp.getUsers' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getUsers' );

		if ( ! current_user_can( 'list_users' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to browse users.' ) );
		}
		$query = [ 'fields' => 'all_with_meta' ];

		$query['number'] = absint( $filter['number'] ?? 50 );
		$query['offset'] = absint( $filter['offset'] ?? 0 );

		if ( isset( $filter['orderby'] ) ) {
			$query['orderby'] = $filter['orderby'];

			if ( isset( $filter['order'] ) ) {
				$query['order'] = $filter['order'];
			}
		}

		if ( isset( $filter['role'] ) ) {
			if ( get_role( $filter['role'] ) === null ) {
				return new Error( 403, __( 'Invalid role.' ) );
			}
			$query['role'] = $filter['role'];
		}

		if ( isset( $filter['who'] ) ) {
			$query['who'] = $filter['who'];
		}

		$users = get_users( $query );

		$_users = [];
		foreach ( $users as $user_data ) {
			if ( current_user_can( 'edit_user', $user_data->ID ) ) {
				$_users[] = $this->_prepare_user( $user_data, $fields );
			}
		}
		return $_users;
	}

	/**
	 * Retrieve information about the requesting user.
	 *
	 * @uses get_userdata()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $fields (optional)
	 * }
	 * @return array|Error (@see wp_getUser)
	 */
	public function wp_getProfile( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) ) {
			return $this->error;
		}

		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		if ( isset( $args[3] ) ) {
			$fields = $args[3];
		} else {
			/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
			$fields = apply_filters( 'xmlrpc_default_user_fields', [ 'all' ], 'wp.getProfile' );
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getProfile' );

		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit your profile.' ) );
		}
		$user_data = get_userdata( $user->ID );

		return $this->_prepare_user( $user_data, $fields );
	}

	/**
	 * Edit user's profile.
	 *
	 * @uses wp_update_user()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $content_struct It can optionally contain:
	 *      - 'first_name'
	 *      - 'last_name'
	 *      - 'website'
	 *      - 'display_name'
	 *      - 'nickname'
	 *      - 'nicename'
	 *      - 'bio'
	 * }
	 * @return true|Error True, on success.
	 */
	public function wp_editProfile( $args ) {
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
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.editProfile' );

		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit your profile.' ) );
		}
		// holds data of the user
		$user_data = [];
		$user_data['ID'] = $user->ID;

		// only set the user details if it was given
		if ( isset( $content_struct['first_name'] ) ) {
			$user_data['first_name'] = $content_struct['first_name'];
		}
		if ( isset( $content_struct['last_name'] ) ) {
			$user_data['last_name'] = $content_struct['last_name'];
		}
		if ( isset( $content_struct['url'] ) ) {
			$user_data['user_url'] = $content_struct['url'];
		}
		if ( isset( $content_struct['display_name'] ) ) {
			$user_data['display_name'] = $content_struct['display_name'];
		}
		if ( isset( $content_struct['nickname'] ) ) {
			$user_data['nickname'] = $content_struct['nickname'];
		}
		if ( isset( $content_struct['nicename'] ) ) {
			$user_data['user_nicename'] = $content_struct['nicename'];
		}
		if ( isset( $content_struct['bio'] ) ) {
			$user_data['description'] = $content_struct['bio'];
		}
		$result = wp_update_user( $user_data );

		if ( is_wp_error( $result ) ) {
			return new Error( 500, $result->get_error_message() );
		}
		if ( ! $result ) {
			return new Error( 500, __( 'Sorry, the user cannot be updated.' ) );
		}
		return true;
	}

	/**
	 * Retrieve authors list.
	 *
	 * @since 2.2.0
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
	public function wp_getAuthors( $args ) {
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
			return new Error( 401, __( 'Sorry, you are not allowed to edit posts.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getAuthors' );

		$authors = [];
		foreach ( get_users( [ 'fields' => [ 'ID','user_login','display_name' ] ] ) as $user ) {
			$authors[] = [
				'user_id'       => $user->ID,
				'user_login'    => $user->user_login,
				'display_name'  => $user->display_name
			];
		}

		return $authors;
	}
}
