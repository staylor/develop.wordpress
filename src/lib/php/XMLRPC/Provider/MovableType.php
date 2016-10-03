<?php
namespace WP\XMLRPC\Provider;

use WP\IXR\Error;
use WP\XMLRPC\{Server,Utils};
use function WP\getApp;

/**
 * MovableType API functions
 * specs on http://www.movabletype.org/docs/mtmanual_programmatic.html
 */
class MovableType implements ProviderInterface {
	use Utils;

	protected $server;

	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			'mt.getCategoryList' => [ $this, 'mt_getCategoryList' ],
			'mt.getRecentPostTitles' => [ $this, 'mt_getRecentPostTitles' ],
			'mt.getPostCategories' => [ $this, 'mt_getPostCategories' ],
			'mt.setPostCategories' => [ $this, 'mt_setPostCategories' ],
			'mt.supportedMethods' => [ $this, 'mt_supportedMethods' ],
			'mt.supportedTextFilters' => [ $this, 'mt_supportedTextFilters' ],
			'mt.getTrackbackPings' => [ $this, 'mt_getTrackbackPings' ],
			'mt.publishPost' => [ $this, 'mt_publishPost' ],
		] );

		$this->server = $server;

		return $this;
	}

	/**
	 * Retrieve the post titles of recent posts.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $blog_id (unused)
	 *     @type string  $username
	 *     @type string  $password
	 *     @type integer $numberposts
	 * }
	 * @return array|Error
	 */
	public function mt_getRecentPostTitles( $args ) {
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

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.getRecentPostTitles' );

		$posts = wp_get_recent_posts( $query );

		if ( ! $posts ) {
			$this->error = new Error( 500, __( 'Either there are no posts, or something went wrong.' ) );
			return $this->error;
		}

		$recent_posts = [];

		foreach ( $posts as $entry ) {
			if ( ! current_user_can( 'edit_post', $entry['ID'] ) ) {
				continue;
			}

			$recent_posts[] = [
				'dateCreated' => $this->_convert_date( $entry['post_date'] ),
				'userid' => $entry['post_author'],
				'postid' => (string) $entry['ID'],
				'title' => $entry['post_title'],
				'post_status' => $entry['post_status'],
				'date_created_gmt' => $this->_convert_date_gmt(
					$entry['post_date_gmt'],
					$entry['post_date']
				)
			];
		}

		return $recent_posts;
	}

	/**
	 * Retrieve list of all categories on blog.
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
	public function mt_getCategoryList( $args ) {
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
		do_action( 'xmlrpc_call', 'mt.getCategoryList' );

		$categories = [];

		$cats = get_categories( [
			'hide_empty' => 0,
			'hierarchical' => 0
		] );

		if ( $cats ) {
			foreach ( $cats as $cat ) {
				$categories[] = [
					'categoryId' => $cat->term_id,
					'categoryName' => $cat->name,
				];
			}
		}

		return $categories;
	}

	/**
	 * Retrieve post categories.
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
	public function mt_getPostCategories( $args ) {
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

		if ( ! get_post( $post_ID ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.getPostCategories' );

		$categories = [];
		$ids = wp_get_post_categories( $post_ID );

		// first listed category will be the primary category
		$primary = true;
		foreach ( $ids as $id ) {
			$categories[] = [
				'categoryName' => get_cat_name( $id ),
				'categoryId' => (string) $id,
				'isPrimary' => $primary
			];
			$primary = false;
		}

		return $categories;
	}

	/**
	 * Sets categories for a post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $post_ID
	 *     @type string  $username
	 *     @type string  $password
	 *     @type array   $categories
	 * }
	 * @return true|Error True on success.
	 */
	public function mt_setPostCategories( $args ) {
		$this->escape( $args );

		list(
			$post_ID,
			$username,
			$password,
			$categories
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.setPostCategories' );

		if ( ! get_post( $post_ID ) ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}

		$ids = [];
		foreach ( $categories as $cat ) {
			$ids[] = $cat['categoryId'];
		}

		wp_set_post_categories( $post_ID, $ids );

		return true;
	}

	/**
	 * Retrieve an array of methods supported by this server.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function mt_supportedMethods() {
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.supportedMethods' );

		return array_keys( $this->server->methods );
	}

	/**
	 * Retrieve an empty array because we don't support per-post text filters.
	 *
	 * @since 1.5.0
	 */
	public function mt_supportedTextFilters() {
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.supportedTextFilters' );

		/**
		 * Filters the MoveableType text filters list for XML-RPC.
		 *
		 * @since 2.2.0
		 *
		 * @param array $filters An array of text filters.
		 */
		return apply_filters( 'xmlrpc_text_filters', [] );
	}

	/**
	 * Retrieve trackbacks sent to a given post.
	 *
	 * @since 1.5.0
	 *
	 * @param int $post_ID
	 * @return array|Error
	 */
	public function mt_getTrackbackPings( $post_ID ) {
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.getTrackbackPings' );

		if ( ! get_post( $post_ID ) ) {
			return new Error( 404, __( 'Sorry, no such post.' ) );
		}

		$app = getApp();
		$db = $app['db'];
		$sql = "SELECT comment_author_url, comment_content, comment_author_IP, comment_type FROM {$db->comments} WHERE comment_post_ID = %d";
		$comments = $db->get_results( $db->prepare( $sql, $post_ID ) );

		if ( ! $comments ) {
			return [];
		}

		$pings = [];
		foreach ( $comments as $comment ) {
			if ( 'trackback' !== $comment->comment_type ) {
				continue;
			}

			$content = $comment->comment_content;
			$title = substr( $content, 8, strpos( $content, '</strong>' ) - 8 );
			$pings[] = [
				'pingTitle' => $title,
				'pingURL'   => $comment->comment_author_url,
				'pingIP'    => $comment->comment_author_IP
			];
		}

		return $pings;
	}

	/**
	 * Sets a post's publish status to 'publish'.
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
	 * @return int|Error
	 */
	public function mt_publishPost( $args ) {
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
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'mt.publishPost' );

		$postdata = get_post( $post_ID, ARRAY_A );
		if ( ! $postdata ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}
		if ( ! current_user_can( 'publish_posts' ) || ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to publish this post.' ) );
		}

		$postdata['post_status'] = 'publish';

		// retain old cats
		$cats = wp_get_post_categories( $post_ID );
		$postdata['post_category'] = $cats;

		$this->escape( $postdata );

		return wp_update_post( $postdata );
	}
}