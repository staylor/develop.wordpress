<?php
namespace WP\XMLRPC\Provider;

use WP\IXR\Error;
use WP\XMLRPC\{Server,Utils};

/**
 * MetaWeblog API functions
 * specs on wherever Dave Winer wants them to be
 *
 * @property \WP\IXR\Error $error
 */
class MetaWeblog implements ProviderInterface {
	use Utils;

	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			// MetaWeblog API (with MT extensions to structs)
			'metaWeblog.newPost' => [ $this, 'mw_newPost' ],
			'metaWeblog.editPost' => [ $this, 'mw_editPost' ],
			'metaWeblog.getPost' => [ $this, 'mw_getPost' ],
			'metaWeblog.getRecentPosts' => [ $this, 'mw_getRecentPosts' ],
			'metaWeblog.getCategories' => [ $this, 'mw_getCategories' ],
			'metaWeblog.newMediaObject' => [ $this, 'mw_newMediaObject' ],
		] );

		return $this;
	}
	/**
	 * Create a new post.
	 *
	 * The 'content_struct' argument must contain:
	 *  - title
	 *  - description
	 *  - mt_excerpt
	 *  - mt_text_more
	 *  - mt_keywords
	 *  - mt_tb_ping_urls
	 *  - categories
	 *
	 * Also, it can optionally contain:
	 *  - wp_slug
	 *  - wp_password
	 *  - wp_page_parent_id
	 *  - wp_page_order
	 *  - wp_author_id
	 *  - post_status | page_status - can be 'draft', 'private', 'publish', or 'pending'
	 *  - mt_allow_comments - can be 'open' or 'closed'
	 *  - mt_allow_pings - can be 'open' or 'closed'
	 *  - date_created_gmt
	 *  - dateCreated
	 *  - wp_post_thumbnail
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $blog_id (unused)
	 *     @type string  $username
	 *     @type string  $password
	 *     @type array   $content_struct
	 *     @type integer $publish
	 * }
	 * @return int|Error
	 */
	public function mw_newPost( $args) {
		$this->escape( $args);

		list(
			/* $blog_id */,
			$username,
			$password,
			$content_struct
		) = $args;

		$publish = $args[4] ?? 0;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.newPost' );

		$page_template = '';
		if ( empty( $content_struct['post_type'] ) ) {
			if ( $publish ) {
				$cap  = 'publish_posts';
			} elseif ( isset( $content_struct['post_status'] ) && 'publish' === $content_struct['post_status'] ) {
				$cap  = 'publish_posts';
			} else {
				$cap = 'edit_posts';
			}
			$error_message = __( 'Sorry, you are not allowed to publish posts on this site.' );
			$post_type = 'post';
		} elseif ( 'page' === $content_struct['post_type'] ) {
			if ( $publish ) {
				$cap  = 'publish_pages';
			} elseif ( isset( $content_struct['page_status'] ) && 'publish' === $content_struct['page_status'] ) {
				$cap  = 'publish_pages';
			} else {
				$cap = 'edit_pages';
			}
			$error_message = __( 'Sorry, you are not allowed to publish pages on this site.' );
			$post_type = 'page';
			if ( ! empty( $content_struct['wp_page_template'] ) ) {
				$page_template = $content_struct['wp_page_template'];
			}
		} elseif ( 'post' === $content_struct['post_type'] ) {
			if ( $publish ) {
				$cap  = 'publish_posts';
			} elseif ( isset( $content_struct['post_status'] ) && 'publish' === $content_struct['post_status'] ) {
				$cap  = 'publish_posts';
			} else {
				$cap = 'edit_posts';
			}
			$error_message = __( 'Sorry, you are not allowed to publish posts on this site.' );
			$post_type = 'post';
		} else {
			// No other post_type values are allowed here
			return new Error( 401, __( 'Invalid post type.' ) );
		}

		if ( ! current_user_can( get_post_type_object( $post_type )->cap->create_posts ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to publish posts on this site.' ) );
		}

		if ( ! current_user_can( $cap ) ) {
			return new Error( 401, $error_message );
		}
		// Check for a valid post format if one was given
		if ( isset( $content_struct['wp_post_format'] ) ) {
			$content_struct['wp_post_format'] = sanitize_key( $content_struct['wp_post_format'] );
			if ( ! array_key_exists( $content_struct['wp_post_format'], get_post_format_strings() ) ) {
				return new Error( 404, __( 'Invalid post format.' ) );
			}
		}

		// Let WordPress generate the post_name (slug) unless
		// one has been provided.
		$post_name = $content_struct['wp_slug'] ?? '';

		// Only use a password if one was given.
		$post_password = $content_struct['wp_password'] ?? null;

		// Only set a post parent if one was provided.
		$post_parent = $content_struct['wp_page_parent_id'] ?? null;

		// Only set the menu_order if it was provided.
		$menu_order = $content_struct['wp_page_order'] ?? null;

		$post_author = $user->ID;

		// If an author id was provided then use it instead.
		if ( isset( $content_struct['wp_author_id'] ) && ( $user->ID != $content_struct['wp_author_id'] ) ) {
			if ( 'post' === $post_type && ! current_user_can( 'edit_others_posts' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to create posts as this user.' ) );
			} elseif ( 'page' === $post_type && ! current_user_can( 'edit_others_pages' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to create pages as this user.' ) );
			} elseif ( ! in_array( $post_type, [ 'post', 'page' ] ) ) {
				return new Error( 401, __( 'Invalid post type.' ) );
			}

			$author = get_userdata( $content_struct['wp_author_id'] );
			if ( ! $author ) {
				return new Error( 404, __( 'Invalid author ID.' ) );
			}
			$post_author = $content_struct['wp_author_id'];
		}

		$post_title = $content_struct['title'] ?? null;
		$post_content = $content_struct['description'] ?? null;
		$post_status = $publish ? 'publish' : 'draft';

		if ( isset( $content_struct["{$post_type}_status"] ) ) {
			switch ( $content_struct["{$post_type}_status"] ) {
			case 'draft':
			case 'pending':
			case 'private':
			case 'publish':
				$post_status = $content_struct["{$post_type}_status"];
				break;

			default:
				$post_status = $publish ? 'publish' : 'draft';
				break;
			}
		}

		$post_excerpt = $content_struct['mt_excerpt'] ?? null;
		$post_more = $content_struct['mt_text_more'] ?? null;
		$tags_input = $content_struct['mt_keywords'] ?? null;

		if ( isset( $content_struct['mt_allow_comments'] ) && ! is_numeric( $content_struct['mt_allow_comments'] ) ) {
			switch ( $content_struct['mt_allow_comments'] ) {
			case 'closed':
				$comment_status = 'closed';
				break;

			case 'open':
				$comment_status = 'open';
				break;

			default:
				$comment_status = get_default_comment_status( $post_type );
				break;
			}
		} elseif ( isset( $content_struct['mt_allow_comments'] ) ) {
			switch ( (int) $content_struct['mt_allow_comments'] ) {
			case 0:
			case 2:
				$comment_status = 'closed';
				break;

			case 1:
				$comment_status = 'open';
				break;

			default:
				$comment_status = get_default_comment_status( $post_type );
				break;
			}
		} else {
			$comment_status = get_default_comment_status( $post_type );
		}

		if ( isset( $content_struct['mt_allow_pings'] ) && ! is_numeric( $content_struct['mt_allow_pings'] ) ) {
			switch ( $content_struct['mt_allow_pings'] ) {
			case 'closed':
				$ping_status = 'closed';
				break;

			case 'open':
				$ping_status = 'open';
				break;

			default:
				$ping_status = get_default_comment_status( $post_type, 'pingback' );
				break;
			}
		} elseif ( isset( $content_struct['mt_allow_pings'] ) ) {
			switch ( (int) $content_struct['mt_allow_pings'] ) {
			case 0:
				$ping_status = 'closed';
				break;

			case 1:
				$ping_status = 'open';
				break;

			default:
				$ping_status = get_default_comment_status( $post_type, 'pingback' );
				break;
			}
		} else {
			$ping_status = get_default_comment_status( $post_type, 'pingback' );
		}

		if ( $post_more ) {
			$post_content = $post_content . '<!--more-->' . $post_more;
		}

		$to_ping = $content_struct['mt_tb_ping_urls'] ?? null;
		if ( is_array( $to_ping ) ) {
			$to_ping = implode( ' ', $to_ping );
		}

		// Do some timestamp voodoo
		if ( ! empty( $content_struct['date_created_gmt'] ) ) {
			// We know this is supposed to be GMT, so we're going to slap that Z on there by force
			$dateCreated = rtrim( $content_struct['date_created_gmt']->getIso(), 'Z' ) . 'Z';
		} elseif ( ! empty( $content_struct['dateCreated'] ) ) {
			$dateCreated = $content_struct['dateCreated']->getIso();
		}

		if ( ! empty( $dateCreated ) ) {
			$post_date = get_date_from_gmt(iso8601_to_datetime( $dateCreated ) );
			$post_date_gmt = iso8601_to_datetime( $dateCreated, 'GMT' );
		} else {
			$post_date = '';
			$post_date_gmt = '';
		}

		$post_category = [];
		if ( isset( $content_struct['categories'] ) && is_array( $content_struct['categories'] ) ) {
			foreach ( $content_struct['categories'] as $cat ) {
				$post_category[] = get_cat_ID( $cat );
			}
		}

		$postdata = compact(
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_title',
			'post_category',
			'post_status',
			'post_excerpt',
			'comment_status',
			'ping_status',
			'to_ping',
			'post_type',
			'post_name',
			'post_password',
			'post_parent',
			'menu_order',
			'tags_input',
			'page_template'
		);

		$post_ID = $postdata['ID'] = get_default_post_to_edit( $post_type, true )->ID;

		// Only posts can be sticky
		if ( 'post' === $post_type && isset( $content_struct['sticky'] ) ) {
			$data = $postdata;
			$data['sticky'] = $content_struct['sticky'];
			$error = $this->_toggle_sticky( $data );
			if ( $error ) {
				return $error;
			}
		}

		if ( isset( $content_struct['custom_fields'] ) ) {
			$this->set_custom_fields( $post_ID, $content_struct['custom_fields'] );
		}

		if ( isset ( $content_struct['wp_post_thumbnail'] ) ) {
			if ( false === set_post_thumbnail( $post_ID, $content_struct['wp_post_thumbnail'] ) ) {
				return new Error( 404, __( 'Invalid attachment ID.' ) );
			}

			unset( $content_struct['wp_post_thumbnail'] );
		}

		// Handle enclosures
		$this->add_enclosure_if_new( $post_ID, $content_struct['enclosure'] ?? null );

		$this->attach_uploads( $post_ID, $post_content );

		// Handle post formats if assigned, value is validated earlier
		// in this function
		if ( isset( $content_struct['wp_post_format'] ) ) {
			set_post_format( $post_ID, $content_struct['wp_post_format'] );
		}

		$new_ID = wp_insert_post( $postdata, true );
		if ( is_wp_error( $new_ID ) ) {
			return new Error( 500, $new_ID->get_error_message() );
		}

		if ( ! $new_ID ) {
			return new Error( 500, __( 'Sorry, your entry could not be posted.' ) );
		}
		/**
		 * Fires after a new post has been successfully created via the XML-RPC MovableType API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $new_ID  ID of the new post.
		 * @param array $args    An array of arguments to create the new post.
		 */
		do_action( 'xmlrpc_call_success_mw_newPost', $new_ID, $args );

		return strval( $new_ID );
	}

	/**
	 * Edit a post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer     $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $content_struct
	 *     @type integer     $publish
	 * }
	 * @return bool|Error True on success.
	 */
	public function mw_editPost( $args ) {
		$this->escape( $args );

		list(
			$post_ID,
			$username,
			$password,
			$content_struct
		) = $args;

		$publish = $args[4] ?? 0;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.editPost' );

		$postdata = get_post( $post_ID, ARRAY_A );

		/*
		 * If there is no post data for the give post id, stop now and return an error.
		 * Otherwise a new post will be created (which was the old behavior).
		 */
		if ( ! $postdata || empty( $postdata[ 'ID' ] ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}

		// Use wp.editPost to edit post types other than post and page.
		if ( ! in_array( $postdata[ 'post_type' ], [ 'post', 'page' ] ) ) {
			return new Error( 401, __( 'Invalid post type.' ) );
		}

		// Thwart attempt to change the post type.
		if ( ! empty( $content_struct[ 'post_type' ] ) && ( $content_struct['post_type'] !== $postdata[ 'post_type' ] ) ) {
			return new Error( 401, __( 'The post type may not be changed.' ) );
		}

		// Check for a valid post format if one was given
		if ( isset( $content_struct['wp_post_format'] ) ) {
			$content_struct['wp_post_format'] = sanitize_key( $content_struct['wp_post_format'] );
			if ( ! array_key_exists( $content_struct['wp_post_format'], get_post_format_strings() ) ) {
				return new Error( 404, __( 'Invalid post format.' ) );
			}
		}

		$this->escape( $postdata );

		$ID = $postdata['ID'];
		$post_content = $content_struct['description'] ?? $postdata['post_content'];
		$post_title = $content_struct['title'] ?? $postdata['post_title'];
		$post_excerpt = $content_struct['mt_excerpt'] ?? $postdata['post_excerpt'];
		$post_type = $postdata['post_type'];
		$post_author = $postdata['post_author'];

		// Only use a password if one was given.
		$post_password = $content_struct['wp_password'] ?? $postdata['post_password'];
		// Only set a post parent if one was given.
		$post_parent = $content_struct['wp_page_parent_id'] ?? $postdata['post_parent'];

		// Only set the menu_order if it was given.
		$menu_order = $content_struct['wp_page_order'] ?? $postdata['menu_order'];

		// Let WordPress manage slug if none was provided.
		$post_name = $content_struct['wp_slug'] ?? $postdata['post_name'];

		$page_template = null;
		if ( ! empty( $content_struct['wp_page_template'] ) && 'page' === $post_type ) {
			$page_template = $content_struct['wp_page_template'];
		}

		// Only set the post_author if one is set.
		if ( isset( $content_struct['wp_author_id'] ) && ( $user->ID != $content_struct['wp_author_id'] || $user->ID != $post_author ) ) {
			// Check permissions if attempting to switch author to or from another user.
			if ( 'post' === $post_type && ! current_user_can( 'edit_others_posts' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to change the post author as this user.' ) );
			} elseif ( 'page' === $post_type && ! current_user_can( 'edit_others_pages' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to change the page author as this user.' ) );
			} elseif ( ! in_array( $post_type, [ 'post', 'page' ] ) ) {
				return new Error( 401, __( 'Invalid post type.' ) );
			}
			$post_author = $content_struct['wp_author_id'];
		}

		if ( isset( $content_struct['mt_allow_comments'] ) && ! is_numeric( $content_struct['mt_allow_comments'] ) ) {
			switch ( $content_struct['mt_allow_comments'] ) {
			case 'closed':
				$comment_status = 'closed';
				break;

			case 'open':
				$comment_status = 'open';
				break;

			default:
				$comment_status = get_default_comment_status( $post_type );
				break;
			}
		} elseif ( isset( $content_struct['mt_allow_comments'] ) ) {
			switch ( (int) $content_struct['mt_allow_comments'] ) {
			case 0:
			case 2:
				$comment_status = 'closed';
				break;

			case 1:
				$comment_status = 'open';
				break;

			default:
				$comment_status = get_default_comment_status( $post_type );
				break;
			}
		}

		if ( isset( $content_struct['mt_allow_pings'] ) && ! is_numeric( $content_struct['mt_allow_pings'] ) ) {
			switch ( $content_struct['mt_allow_pings'] ) {
			case 'closed':
				$ping_status = 'closed';
				break;

			case 'open':
				$ping_status = 'open';
				break;

			default:
				$ping_status = get_default_comment_status( $post_type, 'pingback' );
				break;
			}
		} elseif ( isset( $content_struct['mt_allow_pings'] ) ) {
			switch ( (int) $content_struct['mt_allow_pings'] ) {
			case 0:
				$ping_status = 'closed';
				break;

			case 1:
				$ping_status = 'open';
				break;

			default:
				$ping_status = get_default_comment_status( $post_type, 'pingback' );
				break;
			}
		}

		$post_category = [];
		if ( isset( $content_struct['categories'] ) && is_array( $content_struct['categories'] ) ) {
			foreach ( $content_struct['categories'] as $cat ) {
				$post_category[] = get_cat_ID( $cat );
			}
		}

		$post_status = $publish ? 'publish' : 'draft';
		if ( isset( $content_struct["{$post_type}_status"] ) ) {
			switch( $content_struct["{$post_type}_status"] ) {
			case 'draft':
			case 'pending':
			case 'private':
			case 'publish':
				$post_status = $content_struct["{$post_type}_status"];
				break;

			default:
				$post_status = $publish ? 'publish' : 'draft';
				break;
			}
		}

		$tags_input = $content_struct['mt_keywords'] ?? null;

		if ( 'publish' === $post_status || 'private' === $post_status ) {
			if ( 'page' === $post_type && ! current_user_can( 'publish_pages' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to publish this page.' ) );
			} elseif ( ! current_user_can( 'publish_posts' ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to publish this post.' ) );
			}
		}

		$post_more = $content_struct['mt_text_more'] ?? null;
		if ( $post_more ) {
			$post_content = $post_content . '<!--more-->' . $post_more;
		}

		$to_ping = $content_struct['mt_tb_ping_urls'] ?? null;
		if ( is_array( $to_ping ) ) {
			$to_ping = implode( ' ', $to_ping );
		}

		// Do some timestamp voodoo.
		if ( ! empty( $content_struct['date_created_gmt'] ) ) {
			// We know this is supposed to be GMT, so we're going to slap that Z on there by force.
			$dateCreated = rtrim( $content_struct['date_created_gmt']->getIso(), 'Z' ) . 'Z';
		} elseif ( ! empty( $content_struct['dateCreated'] ) ) {
			$dateCreated = $content_struct['dateCreated']->getIso();
		}
		// Default to not flagging the post date to be edited unless it's intentional.
		$edit_date = false;

		if ( ! empty( $dateCreated ) ) {
			$post_date = get_date_from_gmt(iso8601_to_datetime( $dateCreated ) );
			$post_date_gmt = iso8601_to_datetime( $dateCreated, 'GMT' );

			// Flag the post date to be edited.
			$edit_date = true;
		} else {
			$post_date     = $postdata['post_date'];
			$post_date_gmt = $postdata['post_date_gmt'];
		}

		// We've got all the data -- post it.
		$newpost = compact(
			'ID',
			'post_content',
			'post_title',
			'post_category',
			'post_status',
			'post_excerpt',
			'comment_status',
			'ping_status',
			'edit_date',
			'post_date',
			'post_date_gmt',
			'to_ping',
			'post_name',
			'post_password',
			'post_parent',
			'menu_order',
			'post_author',
			'tags_input',
			'page_template'
		);

		$result = wp_update_post( $newpost, true );
		if ( is_wp_error( $result ) ) {
			return new Error( 500, $result->get_error_message() );
		}

		if ( ! $result ) {
			return new Error( 500, __( 'Sorry, your entry could not be edited.' ) );
		}

		// Only posts can be sticky
		if ( 'post' === $post_type && isset( $content_struct['sticky'] ) ) {
			$data = $newpost;
			$data['sticky'] = $content_struct['sticky'];
			$data['post_type'] = 'post';
			$error = $this->_toggle_sticky( $data, true );
			if ( $error ) {
				return $error;
			}
		}

		if ( isset( $content_struct['custom_fields'] ) ) {
			$this->set_custom_fields( $post_ID, $content_struct['custom_fields'] );
		}

		if ( isset ( $content_struct['wp_post_thumbnail'] ) ) {

			// Empty value deletes, non-empty value adds/updates.
			if ( empty( $content_struct['wp_post_thumbnail'] ) ) {
				delete_post_thumbnail( $post_ID );
			} elseif ( false === set_post_thumbnail( $post_ID, $content_struct['wp_post_thumbnail'] ) ) {
				return new Error( 404, __( 'Invalid attachment ID.' ) );
			}
			unset( $content_struct['wp_post_thumbnail'] );
		}

		// Handle enclosures.
		$thisEnclosure = $content_struct['enclosure'] ?? null;
		$this->add_enclosure_if_new( $post_ID, $thisEnclosure );

		$this->attach_uploads( $ID, $post_content );

		// Handle post formats if assigned, validation is handled earlier in this function.
		if ( isset( $content_struct['wp_post_format'] ) ) {
			set_post_format( $post_ID, $content_struct['wp_post_format'] );
		}
		/**
		 * Fires after a post has been successfully updated via the XML-RPC MovableType API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $post_ID ID of the updated post.
		 * @param array $args    An array of arguments to update the post.
		 */
		do_action( 'xmlrpc_call_success_mw_editPost', $post_ID, $args );

		return true;
	}

	/**
	 * Retrieve post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $post_ID
	 *     @type string  $username
	 *     @type string  $password
	 * }
	 * @return array|Error
	 */
	public function mw_getPost( $args ) {
		$this->escape( $args );

		list(
			$post_ID,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		$postdata = get_post( $post_ID, ARRAY_A );
		if ( ! $postdata ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.getPost' );

		if ( empty( $postdata['post_date'] ) ) {
			return new Error( 404, __( 'Sorry, no such post.' ) );
		}

		$post_date = $this->_convert_date( $postdata['post_date'] );
		$post_date_gmt = $this->_convert_date_gmt(
			$postdata['post_date_gmt'],
			$postdata['post_date']
		);
		$post_modified = $this->_convert_date( $postdata['post_modified'] );
		$post_modified_gmt = $this->_convert_date_gmt(
			$postdata['post_modified_gmt'],
			$postdata['post_modified']
		);

		$categories = [];
		$catids = wp_get_post_categories( $post_ID );
		foreach ( $catids as $catid ) {
			$categories[] = get_cat_name( $catid );
		}

		$tagnames = '';
		$tags = wp_get_post_tags( $post_ID );
		if ( ! empty( $tags ) ) {
			$names = wp_list_pluck( $tags, 'name' );
			$tagnames = implode( ', ', $names );
		}

		$post = get_extended( $postdata['post_content'] );
		$link = get_permalink( $postdata['ID'] );

		// Get the author info.
		$author = get_userdata( $postdata['post_author'] );

		// Consider future posts as published
		if ( 'future' === $postdata['post_status'] ) {
			$postdata['post_status'] = 'publish';
		}

		$enclosure = [];
		foreach ( (array) get_post_custom( $post_ID ) as $key => $val ) {
			if ( 'enclosure' !== $key ) {
				continue;
			}

			foreach ( (array) $val as $enc ) {
				list( $url, $length, $type ) = explode( "\n", $enc );
				$enclosure['url'] = trim( htmlspecialchars( $url ) );
				$enclosure['length'] = (int) trim( $length );
				$enclosure['type'] = trim( $type );
				break 2;
			}
		}

		$format = get_post_format( $post_ID );
		if ( ! $format ) {
			$format = null;
		}

		$resp = [
			'dateCreated' => $post_date,
			'userid' => $postdata['post_author'],
			'postid' => $postdata['ID'],
			'description' => $post['main'],
			'title' => $postdata['post_title'],
			'link' => $link,
			'permaLink' => $link,
			// commented out because no other tool seems to use this
			// 'content' => $entry['post_content'],
			'categories' => $categories,
			'mt_excerpt' => $postdata['post_excerpt'],
			'mt_text_more' => $post['extended'],
			'wp_more_text' => $post['more_text'],
			'mt_allow_comments' => 'open' === $postdata['comment_status'] ? 1 : 0,
			'mt_allow_pings' => 'open' === $postdata['ping_status'] ? 1 : 0,
			'mt_keywords' => $tagnames,
			'wp_slug' => $postdata['post_name'],
			'wp_password' => $postdata['post_password'],
			'wp_author_id' => (string) $author->ID,
			'wp_author_display_name' => $author->display_name,
			'date_created_gmt' => $post_date_gmt,
			'post_status' => $postdata['post_status'],
			'custom_fields' => $this->get_custom_fields( $post_ID),
			'wp_post_format' => $format ?? 'standard',
			'sticky' => is_sticky( $post_ID ),
			'date_modified' => $post_modified,
			'date_modified_gmt' => $post_modified_gmt
		];

		if ( ! empty( $enclosure ) ) {
			$resp['enclosure'] = $enclosure;
		}

		$resp['wp_post_thumbnail'] = get_post_thumbnail_id( $postdata['ID'] );

		return $resp;
	}

	/**
	 * Retrieve list of recent posts.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer     $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type integer     $numberposts
	 * }
	 * @return array|Error
	 */
	public function mw_getRecentPosts( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		if ( isset( $args[3] ) ) {
			$query = [ 'numberposts' => absint( $args[3] ) ];
		} else {
			$query = [];
		}
		$user = $this->login( $username, $password);
		if ( ! $user ) {
			return $this->error;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit posts.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.getRecentPosts' );

		$posts_list = wp_get_recent_posts( $query );

		if ( ! $posts_list ) {
			return [];
		}

		$recent_posts = [];
		foreach ( $posts_list as $entry ) {
			if ( ! current_user_can( 'edit_post', $entry['ID'] ) ) {
				continue;
			}

			$post_date = $this->_convert_date( $entry['post_date'] );
			$post_date_gmt = $this->_convert_date_gmt( $entry['post_date_gmt'], $entry['post_date'] );
			$post_modified = $this->_convert_date( $entry['post_modified'] );
			$post_modified_gmt = $this->_convert_date_gmt( $entry['post_modified_gmt'], $entry['post_modified'] );

			$categories = wp_get_post_categories( $entry['ID'], [ 'fields' => 'names' ] );
			$tags = wp_get_post_tags( $entry['ID'], [ 'fields' => 'names' ] );
			$tagnames = implode( ', ', $tags );

			$post = get_extended( $entry['post_content'] );
			$link = get_permalink( $entry['ID'] );

			// Get the post author info.
			$author = get_userdata( $entry['post_author'] );

			// Consider future posts as published
			if ( 'future' === $entry['post_status'] ) {
				$entry['post_status'] = 'publish';
			}

			// Get post format
			$post_format = get_post_format( $entry['ID'] );
			if ( empty( $post_format ) ) {
				$post_format = 'standard';
			}

			$recent_posts[] = [
				'dateCreated' => $post_date,
				'userid' => $entry['post_author'],
				'postid' => (string) $entry['ID'],
				'description' => $post['main'],
				'title' => $entry['post_title'],
				'link' => $link,
				'permaLink' => $link,
				// commented out because no other tool seems to use this
				// 'content' => $entry['post_content'],
				'categories' => $categories,
				'mt_excerpt' => $entry['post_excerpt'],
				'mt_text_more' => $post['extended'],
				'wp_more_text' => $post['more_text'],
				'mt_allow_comments' => 'open' === $entry['comment_status'] ? 1 : 0,
				'mt_allow_pings' => 'open' === $entry['ping_status'] ? 1 : 0,
				'mt_keywords' => $tagnames,
				'wp_slug' => $entry['post_name'],
				'wp_password' => $entry['post_password'],
				'wp_author_id' => (string) $author->ID,
				'wp_author_display_name' => $author->display_name,
				'date_created_gmt' => $post_date_gmt,
				'post_status' => $entry['post_status'],
				'custom_fields' => $this->get_custom_fields( $entry['ID'] ),
				'wp_post_format' => $post_format,
				'date_modified' => $post_modified,
				'date_modified_gmt' => $post_modified_gmt,
				'sticky' => ( $entry['post_type'] === 'post' && is_sticky( $entry['ID'] ) ),
				'wp_post_thumbnail' => get_post_thumbnail_id( $entry['ID'] )
			];
		}

		return $recent_posts;
	}

	/**
	 * Retrieve the list of categories on a given blog.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $blog_id (unused)
	 *     @type string  $username
	 *     @type string  $password
	 * }
	 * @return array|Error
	 */
	public function mw_getCategories( $args ) {
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
			return new Error( 401, __( 'Sorry, you must be able to edit posts on this site in order to view categories.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.getCategories' );

		$categories = [];

		$cats = get_categories( [ 'get' => 'all' ] );
		if ( $cats ) {
			foreach ( $cats as $cat ) {
				$categories[] = [
					'categoryId' => $cat->term_id,
					'parentId' => $cat->parent,
					'description' => $cat->name,
					'categoryDescription' => $cat->description,
					'categoryName' => $cat->name,
					'htmlUrl' => esc_html( get_category_link( $cat->term_id ) ),
					'rssUrl' => esc_html( get_category_feed_link( $cat->term_id, 'rss2' ) ),
				];
			}
		}

		return $categories;
	}

	/**
	 * Uploads a file, following your settings.
	 *
	 * Adapted from a patch by Johann Richard.
	 *
	 * @link http://mycvs.org/archives/2004/06/30/file-upload-to-wordpress-in-ecto/
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer     $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $data
	 * }
	 * @return array|Error
	 */
	public function mw_newMediaObject( $args ) {
		$username = $this->escape( $args[1] );
		$password = $this->escape( $args[2] );
		$data     = $args[3];

		$name = sanitize_file_name( $data['name'] );
		$type = $data['type'];
		$bits = $data['bits'];

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'metaWeblog.newMediaObject' );

		if ( ! current_user_can( 'upload_files' ) ) {
			$this->error = new Error( 401, __( 'Sorry, you are not allowed to upload files.' ) );
			return $this->error;
		}

		if ( is_multisite() && upload_is_user_over_quota( false ) ) {
			$this->error = new Error( 401, __( 'Sorry, you have used your space allocation.' ) );
			return $this->error;
		}

		/**
		 * Filters whether to preempt the XML-RPC media upload.
		 *
		 * Passing a truthy value will effectively short-circuit the media upload,
		 * returning that value as a 500 error instead.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $error Whether to pre-empt the media upload. Default false.
		 */
		$upload_err = apply_filters( 'pre_upload_error', false );
		if ( $upload_err ) {
			return new Error( 500, $upload_err );
		}

		$upload = wp_upload_bits( $name, null, $bits );
		if ( ! empty( $upload['error'] ) ) {
			/* translators: 1: file name, 2: error message */
			$errorString = sprintf( __( 'Could not write file %1$s (%2$s).' ), $name, $upload['error'] );
			return new Error( 500, $errorString );
		}
		// Construct the attachment array
		$post_id = 0;
		if ( ! empty( $data['post_id'] ) ) {
			$post_id = (int) $data['post_id'];

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
			}
		}
		$attachment = [
			'post_title' => $name,
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ]
		];

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		/**
		 * Fires after a new attachment has been added via the XML-RPC MovableType API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $id   ID of the new attachment.
		 * @param array $args An array of arguments to add the attachment.
		 */
		do_action( 'xmlrpc_call_success_mw_newMediaObject', $id, $args );

		$struct = $this->_prepare_media_item( get_post( $id ) );

		// Deprecated values
		$struct['id']   = $struct['attachment_id'];
		$struct['file'] = $struct['title'];
		$struct['url']  = $struct['link'];

		return $struct;
	}
}
