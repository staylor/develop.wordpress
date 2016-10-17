<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;
use WP\User\User as WP_User;
use WP\XMLRPC\Provider\MetaWeblog;
use function WP\getApp;

/**
 * @property \WP\IXR\Error $error
 */
trait Page {
	/**
	 * @return string|void
	 */
	abstract public function escape( &$data );
	/**
	 * @return WP_User|bool
	 */
	abstract public function login( $username, $password );
	/**
	 * Prepares page data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param object $page The unprepared page data.
	 * @return array The prepared page data.
	 */
	protected function _prepare_page( $page ) {
		// Get all of the page content and link.
		$full_page = get_extended( $page->post_content );
		$link = get_permalink( $page->ID );

		// Get info the page parent if there is one.
		$parent_title = '';
		if ( ! empty( $page->post_parent ) ) {
			$parent = get_post( $page->post_parent );
			$parent_title = $parent->post_title;
		}

		// Pull the categories info together.
		$categories = [];
		if ( is_object_in_taxonomy( 'page', 'category' ) ) {
			foreach ( wp_get_post_categories( $page->ID ) as $cat_id ) {
				$categories[] = get_cat_name( $cat_id );
			}
		}

		// Get the author info.
		$author = get_userdata( $page->post_author );

		$page_template = get_page_template_slug( $page->ID );
		if ( empty( $page_template ) ) {
			$page_template = 'default';
		}

		$_page = [
			'dateCreated'            => $this->_convert_date( $page->post_date ),
			'userid'                 => $page->post_author,
			'page_id'                => $page->ID,
			'page_status'            => $page->post_status,
			'description'            => $full_page['main'],
			'title'                  => $page->post_title,
			'link'                   => $link,
			'permaLink'              => $link,
			'categories'             => $categories,
			'excerpt'                => $page->post_excerpt,
			'text_more'              => $full_page['extended'],
			'mt_allow_comments'      => comments_open( $page->ID ) ? 1 : 0,
			'mt_allow_pings'         => pings_open( $page->ID ) ? 1 : 0,
			'wp_slug'                => $page->post_name,
			'wp_password'            => $page->post_password,
			'wp_author'              => $author->display_name,
			'wp_page_parent_id'      => $page->post_parent,
			'wp_page_parent_title'   => $parent_title,
			'wp_page_order'          => $page->menu_order,
			'wp_author_id'           => (string) $author->ID,
			'wp_author_display_name' => $author->display_name,
			'date_created_gmt'       => $this->_convert_date_gmt(
				$page->post_date_gmt,
				$page->post_date
			),
			'custom_fields'          => $this->get_custom_fields( $page->ID ),
			'wp_page_template'       => $page_template
		];

		/**
		 * Filters XML-RPC-prepared data for the given page.
		 *
		 * @since 3.4.0
		 *
		 * @param array   $_page An array of page data.
		 * @param WP_Post $page  Page object.
		 */
		return apply_filters( 'xmlrpc_prepare_page', $_page, $page );
	}
	/**
	 * Retrieve page.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type int    $page_id
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return array|Error
	 */
	public function wp_getPage( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$page_id,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		$page = get_post( $page_id );
		if ( ! $page ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_page', $page_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this page.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPage' );

		// If we found the page then format the data.
		if ( $page->ID && 'page' === $page->post_type ) {
			return $this->_prepare_page( $page );
		} else {
			// If the page doesn't exist indicate that.
			return new Error( 404, __( 'Sorry, no such page.' ) );
		}
	}

	/**
	 * Retrieve Pages.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $num_pages
	 * }
	 * @return array|Error
	 */
	public function wp_getPages( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password
		) = $args;

		$num_pages = intval( $args[3] ?? 10 );

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( !current_user_can( 'edit_pages' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit pages.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPages' );

		$pages = get_posts( [
			'post_type' => 'page',
			'post_status' => 'any',
			'numberposts' => $num_pages
		] );

		$data = [];
		// If we have pages, put together their info.
		if ( ! empty( $pages ) ) {
			foreach ( $pages as $page ) {
				if ( current_user_can( 'edit_page', $page->ID ) ) {
					$data[] = $this->_prepare_page( $page );
				}
			}
		}

		return $data;
	}

	/**
	 * Create new page.
	 *
	 * @since 2.2.0
	 *
	 * @see wp_xmlrpc_server::mw_newPost()
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $content_struct
	 * }
	 * @return int|Error
	 */
	public function wp_newPage( $args ) {
		// Items not escaped here will be escaped in newPost.
		$username = $this->escape( $args[1] );
		$password = $this->escape( $args[2] );

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.newPage' );

		// Mark this as content for a page.
		$args[3]['post_type'] = 'page';

		$mw = new MetaWeblog();
		// Let mw_newPost do all of the heavy lifting.
		return $mw->mw_newPost( $args );
	}

	/**
	 * Delete page.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $page_id
	 * }
	 * @return true|Error True, if success.
	 */
	public function wp_deletePage( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$username,
			$password,
			$page_id
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.deletePage' );

		// Get the current page based on the page_id and
		// make sure it is a page and not a post.
		$page = get_post( $page_id, ARRAY_A );
		if ( ! $page || 'page' !== $page['post_type'] ) {
			return new Error( 404, __( 'Sorry, no such page.' ) );
		}

		// Make sure the user can delete pages.
		if ( ! current_user_can( 'delete_page', $page_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to delete this page.' ) );
		}
		// Attempt to delete the page.
		$result = wp_delete_post( $page_id );
		if ( ! $result ) {
			return new Error( 500, __( 'Failed to delete the page.' ) );
		}
		/**
		 * Fires after a page has been successfully deleted via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $page_id ID of the deleted page.
		 * @param array $args    An array of arguments to delete the page.
		 */
		do_action( 'xmlrpc_call_success_wp_deletePage', $page_id, $args );

		return true;
	}

	/**
	 * Edit page.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type int    $page_id
	 *     @type string $username
	 *     @type string $password
	 *     @type string $content
	 *     @type string $publish
	 * }
	 * @return array|Error
	 */
	public function wp_editPage( $args ) {
		// Items will be escaped in mw_editPost.
		list(
			/* $blog_id */,
			$page_id,
			$username,
			$password,
			$content,
			$publish
		) = $args;

		$escaped_username = $this->escape( $username );
		$escaped_password = $this->escape( $password );

		$user = $this->login( $escaped_username, $escaped_password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.editPage' );

		// Get the page data and make sure it is a page.
		$page = get_post( $page_id, ARRAY_A );
		if ( ! $page || 'page' !== $page['post_type'] ) {
			return new Error( 404, __( 'Sorry, no such page.' ) );
		}

		// Make sure the user is allowed to edit pages.
		if ( ! current_user_can( 'edit_page', $page_id ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this page.' ) );
		}
		// Mark this as content for a page.
		$content['post_type'] = 'page';

		// Arrange args in the way mw_editPost understands.
		// Let mw_editPost do all of the heavy lifting.
		$mw = new MetaWeblog();
		return $mw->mw_editPost( [
			$page_id,
			$username,
			$password,
			$content,
			$publish
		] );
	}

	/**
	 * Retrieve page list.
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
	public function wp_getPageList( $args ) {
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
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit pages.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPageList' );

		$app = getApp();
		$db = $app['db'];
		// Get list of pages ids and titles
		$pages = $db->get_results( '
			SELECT ID page_id,
				post_title page_title,
				post_parent page_parent_id,
				post_date_gmt,
				post_date,
				post_status
			FROM ' . $db->posts . '
			WHERE post_type = "page"
			ORDER BY ID
		' );

		foreach ( $pages as &$page ) {
			// The date needs to be formatted properly.
			$page->dateCreated = $this->_convert_date( $page->post_date );
			$page->date_created_gmt = $this->_convert_date_gmt(
				$page->post_date_gmt,
				$page->post_date
			);

			unset(
				$page->post_date_gmt,
				$page->post_date,
				$page->post_status
			);
		}

		return $pages;
	}

	/**
	 * Retrieve page statuses.
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
	public function wp_getPageStatusList( $args ) {
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
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details about this site.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'wp.getPageStatusList' );

		return get_page_statuses();
	}

	/**
	 * Retrieve page templates.
	 *
	 * @since 2.6.0
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
	public function wp_getPageTemplates( $args ) {
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
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new Error( 403, __( 'Sorry, you are not allowed access to details about this site.' ) );
		}
		$templates = get_page_templates();
		$templates['Default'] = 'default';

		return $templates;
	}

}