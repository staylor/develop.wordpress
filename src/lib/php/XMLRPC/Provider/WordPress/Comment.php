<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;
use WP\User\User as WP_User;

trait Comment {
	/**
	 * @return string|void
	 */
	abstract public function escape( &$data );
	/**
	 * @return WP_User|bool
	 */
	abstract public function login( $username, $password );
	/**
	 * Prepares comment data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param object $comment The unprepared comment data.
	 * @return array The prepared comment data.
	 */
	protected function _prepare_comment( $comment ) {
		// Format page date.
		$comment_date_gmt = $this->_convert_date_gmt( $comment->comment_date_gmt, $comment->comment_date );

		if ( '0' == $comment->comment_approved ) {
			$comment_status = 'hold';
		} elseif ( 'spam' == $comment->comment_approved ) {
			$comment_status = 'spam';
		} elseif ( '1' == $comment->comment_approved ) {
			$comment_status = 'approve';
		} else {
			$comment_status = $comment->comment_approved;
		}
		$_comment = [
			'date_created_gmt' => $comment_date_gmt,
			'user_id'          => $comment->user_id,
			'comment_id'       => $comment->comment_ID,
			'parent'           => $comment->comment_parent,
			'status'           => $comment_status,
			'content'          => $comment->comment_content,
			'link'             => get_comment_link( $comment ),
			'post_id'          => $comment->comment_post_ID,
			'post_title'       => get_the_title( $comment->comment_post_ID ),
			'author'           => $comment->comment_author,
			'author_url'       => $comment->comment_author_url,
			'author_email'     => $comment->comment_author_email,
			'author_ip'        => $comment->comment_author_IP,
			'type'             => $comment->comment_type,
		];

		/**
		 * Filters XML-RPC-prepared data for the given comment.
		 *
		 * @since 3.4.0
		 *
		 * @param array      $_comment An array of prepared comment data.
		 * @param WP_Comment $comment  Comment object.
		 */
		return apply_filters( 'xmlrpc_prepare_comment', $_comment, $comment );
	}
	/**
	 * Retrieve comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $comment_id
	 * }
	 * @return array|Error
	 */
	public function wp_getComment( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$comment_id
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getComment' );

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return new Error( 404, __( 'Invalid comment ID.' ) );
		}

		if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed to moderate or edit this comment.' ) );
		}

		return $this->_prepare_comment( $comment );
	}

	/**
	 * Retrieve comments.
	 *
	 * Besides the common blog_id (unused), username, and password arguments, it takes a filter
	 * array as last argument.
	 *
	 * Accepted 'filter' keys are 'status', 'post_id', 'offset', and 'number'.
	 *
	 * The defaults are as follows:
	 * - 'status' - Default is ''. Filter by status (e.g., 'approve', 'hold' )
	 * - 'post_id' - Default is ''. The post where the comment is posted. Empty string shows all comments.
	 * - 'number' - Default is 10. Total number of media items to retrieve.
	 * - 'offset' - Default is 0. See WP_Query::query() for more.
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $struct
	 * }
	 * @return array|Error Contains a collection of comments. See wp_xmlrpc_server::wp_getComment() for a description of each item contents
	 */
	public function wp_getComments( $args ) {
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

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getComments' );

		$struct	= $args[3] ?? [];
		$status = $struct['status'] ?? '';

		if ( ! current_user_can( 'moderate_comments' ) && 'approve' !== $status ) {
			return new Error( 401, __( 'Invalid comment status.' ) );
		}

		$post_id = absint( $struct['post_id'] ?? 0 );

		$post_type = '';
		if ( isset( $struct['post_type'] ) ) {
			$obj = get_post_type_object( $struct['post_type'] );
			if ( ! $obj || ! post_type_supports( $obj->name, 'comments' ) ) {
				return new Error( 404, __( 'Invalid post type.' ) );
			}
			$post_type = $struct['post_type'];
		}

		$comments = get_comments( [
			'status' => $status,
			'post_id' => $post_id,
			'offset' => absint( $struct['offset'] ?? 0 ),
			'number' => absint( $struct['number'] ?? 10 ),
			'post_type' => $post_type,
		] );

		$data = [];
		if ( is_array( $comments ) ) {
			foreach ( $comments as $comment ) {
				$data[] = $this->_prepare_comment( $comment );
			}
		}

		return $data;
	}

	/**
	 * Delete a comment.
	 *
	 * By default, the comment will be moved to the trash instead of deleted.
	 * See wp_delete_comment() for more information on this behavior.
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $comment_ID
	 * }
	 * @return bool|Error See wp_delete_comment().
	 */
	public function wp_deleteComment( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$comment_ID
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( ! get_comment( $comment_ID ) ) {
			return new Error( 404, __( 'Invalid comment ID.' ) );
		}

		if ( ! current_user_can( 'edit_comment', $comment_ID ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed to moderate or edit this comment.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.deleteComment' );

		$status = wp_delete_comment( $comment_ID );

		if ( $status ) {
			/**
			 * Fires after a comment has been successfully deleted via XML-RPC.
			 *
			 * @since 3.4.0
			 *
			 * @param int   $comment_ID ID of the deleted comment.
			 * @param array $args       An array of arguments to delete the comment.
			 */
			do_action( 'xmlrpc_call_success_wp_deleteComment', $comment_ID, $args );
		}

		return $status;
	}

	/**
	 * Edit comment.
	 *
	 * Besides the common blog_id (unused), username, and password arguments, it takes a
	 * comment_id integer and a content_struct array as last argument.
	 *
	 * The allowed keys in the content_struct array are:
	 *  - 'author'
	 *  - 'author_url'
	 *  - 'author_email'
	 *  - 'content'
	 *  - 'date_created_gmt'
	 *  - 'status'. Common statuses are 'approve', 'hold', 'spam'. See get_comment_statuses() for more details
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $comment_ID
	 *     @type array  $content_struct
	 * }
	 * @return true|Error True, on success.
	 */
	public function wp_editComment( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$comment_ID,
			$content_struct
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( ! get_comment( $comment_ID ) ) {
			return new Error( 404, __( 'Invalid comment ID.' ) );
		}

		if ( ! current_user_can( 'edit_comment', $comment_ID ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed to moderate or edit this comment.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.editComment' );

		if ( isset( $content_struct['status'] ) ) {
			$statuses = array_keys( get_comment_statuses() );

			if ( ! in_array( $content_struct['status'], $statuses ) ) {
				return new Error( 401, __( 'Invalid comment status.' ) );
			}
			$comment_approved = $content_struct['status'];
		}

		// Do some timestamp voodoo
		if ( ! empty( $content_struct['date_created_gmt'] ) ) {
			// We know this is supposed to be GMT, so we're going to slap that Z on there by force
			$dateCreated = rtrim( $content_struct['date_created_gmt']->getIso(), 'Z' ) . 'Z';
			$comment_date = get_date_from_gmt(iso8601_to_datetime( $dateCreated ) );
			$comment_date_gmt = iso8601_to_datetime( $dateCreated, 'GMT' );
		}

		$comment_content = $content_struct['content'] ?? null;
		$comment_author = $content_struct['author'] ?? null;
		$comment_author_url = $content_struct['author_url'] ?? null;
		$comment_author_email = $content_struct['author_email'] ?? null;

		// We've got all the data -- post it:
		$comment = compact(
			'comment_ID',
			'comment_content',
			'comment_approved',
			'comment_date',
			'comment_date_gmt',
			'comment_author',
			'comment_author_email',
			'comment_author_url'
		);

		$result = wp_update_comment( $comment );
		if ( is_wp_error( $result ) ) {
			return new Error( 500, $result->get_error_message() );
		}
		if ( ! $result ) {
			return new Error( 500, __( 'Sorry, the comment could not be edited.' ) );
		}
		/**
		 * Fires after a comment has been successfully updated via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $comment_ID ID of the updated comment.
		 * @param array $args       An array of arguments to update the comment.
		 */
		do_action( 'xmlrpc_call_success_wp_editComment', $comment_ID, $args );

		return true;
	}

	/**
	 * Create new comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int        $blog_id (unused)
	 *     @type string     $username
	 *     @type string     $password
	 *     @type string|int $post
	 *     @type array      $content_struct
	 * }
	 * @return int|Error See wp_new_comment().
	 */
	public function wp_newComment( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$post,
			$content_struct
		) = $args;

		/**
		 * Filters whether to allow anonymous comments over XML-RPC.
		 *
		 * @since 2.7.0
		 *
		 * @param bool $allow Whether to allow anonymous commenting via XML-RPC.
		 *                    Default false.
		 */
		$allow_anon = apply_filters( 'xmlrpc_allow_anonymous_comments', false );

		$user = $this->login( $username, $password );

		if ( ! $user ) {
			$logged_in = false;
			if ( $allow_anon && get_option( 'comment_registration' ) ) {
				return new Error( 403, __( 'You must be registered to comment.' ) );
			} elseif ( ! $allow_anon ) {
				return $this->error;
			}
		} else {
			$logged_in = true;
		}

		if ( is_numeric( $post ) ) {
			$post_id = absint( $post );
		} else {
			$post_id = url_to_postid( $post );
		}

		if ( ! $post_id ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! get_post( $post_id ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! comments_open( $post_id ) ) {
			return new Error( 403, __( 'Sorry, comments are closed for this item.' ) );
		}

		$comment = [
			'comment_post_ID' => $post_id
		];

		if ( $logged_in ) {
			$display_name = $user->display_name;
			$user_email = $user->user_email;
			$user_url = $user->user_url;

			$comment['comment_author'] = $this->escape( $display_name );
			$comment['comment_author_email'] = $this->escape( $user_email );
			$comment['comment_author_url'] = $this->escape( $user_url );
			$comment['user_ID'] = $user->ID;
		} else {
			$comment['comment_author'] = $content_struct['author'] ?? '';
			$comment['comment_author_email'] = $content_struct['author_email'] ?? '';
			$comment['comment_author_url'] = $content_struct['author_url'] ?? '';
			$comment['user_ID'] = 0;

			$opt = get_option( 'require_name_email' );

			if ( $opt && ( 6 > strlen( $comment['comment_author_email'] ) || '' === $comment['comment_author'] ) ) {
				return new Error( 403, __( 'Comment author name and email are required.' ) );
			} elseif ( $opt && ! is_email( $comment['comment_author_email'] ) ) {
				return new Error( 403, __( 'A valid email address is required.' ) );
			}
		}

		$comment['comment_parent'] = absint( $content_struct['comment_parent'] ?? 0 );

		$comment['comment_content'] = $content_struct['content'] ?? null;

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.newComment' );

		$comment_ID = wp_new_comment( $comment );

		/**
		 * Fires after a new comment has been successfully created via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $comment_ID ID of the new comment.
		 * @param array $args       An array of new comment arguments.
		 */
		do_action( 'xmlrpc_call_success_wp_newComment', $comment_ID, $args );

		return $comment_ID;
	}

	/**
	 * Retrieve all of the comment status.
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
	 * @return array|Error
	 */
	public function wp_getCommentStatusList( $args ) {
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

		if ( ! current_user_can( 'publish_posts' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details about this site.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getCommentStatusList' );

		return get_comment_statuses();
	}

	/**
	 * Retrieve comment count.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $post_id
	 * }
	 * @return array|Error
	 */
	public function wp_getCommentCount( $args ) {
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

		$post = get_post( $post_id, ARRAY_A );
		if ( empty( $post['ID'] ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details of this post.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getCommentCount' );

		$count = wp_count_comments( $post_id );

		return [
			'approved' => $count->approved,
			'awaiting_moderation' => $count->moderated,
			'spam' => $count->spam,
			'total_comments' => $count->total_comments
		];
	}
}
