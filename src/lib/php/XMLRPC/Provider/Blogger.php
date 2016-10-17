<?php
namespace WP\XMLRPC\Provider;

use WP\IXR\{Client,Error};
use WP\XMLRPC\{Server,Utils};
use function WP\getApp;

/**
 * Blogger API functions.
 * specs on http://plant.blogger.com/api and https://groups.yahoo.com/group/bloggerDev/
 *
 * @property \WP\IXR\Error $error
 */
class Blogger implements ProviderInterface {
	use Utils;

	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			// Blogger API
			'blogger.getUsersBlogs' => [ $this, 'blogger_getUsersBlogs' ],
			'blogger.getUserInfo' => [ $this, 'blogger_getUserInfo' ],
			'blogger.getPost' => [ $this, 'blogger_getPost' ],
			'blogger.getRecentPosts' => [ $this, 'blogger_getRecentPosts' ],
			'blogger.newPost' => [ $this, 'blogger_newPost' ],
			'blogger.editPost' => [ $this, 'blogger_editPost' ],
			'blogger.deletePost' => [ $this, 'blogger_deletePost' ],
		] );

		return $this;
	}

	/**
	 * Retrieve blogs that user owns.
	 *
	 * Will make more sense once we support multiple blogs.
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
	 * @return \WP\IXR\Error|array
	 */
	public function blogger_getUsersBlogs( $args ) {
		if ( ! $this->minimum_args( $args, 3 ) ) {
			return $this->error;
		}

		if ( is_multisite() ) {
			return $this->_multisite_getUsersBlogs( $args );
		}

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
		do_action( 'xmlrpc_call', 'blogger.getUsersBlogs' );

		$struct = [
			'isAdmin'  => current_user_can( 'manage_options' ),
			'url'      => get_option( 'home' ) . '/',
			'blogid'   => '1',
			'blogName' => get_option( 'blogname' ),
			'xmlrpc'   => site_url( 'xmlrpc.php', 'rpc' ),
		];

		return [ $struct ];
	}

	/**
	 * Private function for retrieving a users blogs for multisite setups
	 *
	 * @since 3.0.0
	 * @access protected
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type string $username Username.
	 *     @type string $password Password.
	 * }
	 * @return \WP\IXR\Error|array
	 */
	protected function _multisite_getUsersBlogs( $args ) {
		$current_blog = get_blog_details();

		$domain = $current_blog->domain;
		$path = $current_blog->path . 'xmlrpc.php';

		$url = sprintf( 'http://%s%s', $domain, $path );
		$rpc = new Client( set_url_scheme( $url ) );
		$rpc->query( 'wp.getUsersBlogs', $args[1], $args[2] );
		$blogs = $rpc->getResponse();

		if ( isset( $blogs['faultCode'] ) ) {
			return new Error( $blogs['faultCode'], $blogs['faultString'] );
		}

		$app = getApp();
		if ( $domain === $app['request.host'] && $path === $app['request.uri'] ) {
			return $blogs;
		}

		foreach ( (array) $blogs as $blog ) {
			if ( strpos( $blog['url'], $app['request.host'] ) ) {
				return [ $blog ];
			}
		}
		return [];
	}

	/**
	 * Retrieve user's data.
	 *
	 * Gives your client some info about you, so you don't have to.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return \WP\IXR\Error|array
	 */
	public function blogger_getUserInfo( $args ) {
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
			return new Error( 401, __( 'Sorry, you are not allowed to access user data on this site.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.getUserInfo' );

		return [
			'nickname'  => $user->nickname,
			'userid'    => $user->ID,
			'url'       => $user->user_url,
			'lastname'  => $user->last_name,
			'firstname' => $user->first_name
		];
	}

	/**
	 * Retrieve post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $blog_id (unused)
	 *     @type integer $post_ID
	 *     @type string  $username
	 *     @type string  $password
	 * }
	 * @return \WP\IXR\Error|array
	 */
	public function blogger_getPost( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$post_ID,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		$post_data = get_post( $post_ID, ARRAY_A );
		if ( ! $post_data ) {
			return new Error( 404, __( 'Invalid post ID.' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.getPost' );

		$categories = implode( ',', wp_get_post_categories( $post_ID ) );

		$content  = '<title>' . wp_unslash( $post_data['post_title'] ) . '</title>';
		$content .= '<category>' . $categories . '</category>';
		$content .= wp_unslash( $post_data['post_content'] );

		return [
			'userid' => $post_data['post_author'],
			'dateCreated' => $this->_convert_date( $post_data['post_date'] ),
			'content' => $content,
			'postid' => (string) $post_data['ID']
		];
	}

	/**
	 * Retrieve list of recent posts.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type string $appkey (unused)
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type int    $numberposts (optional)
	 * }
	 * @return \WP\IXR\Error|array
	 */
	public function blogger_getRecentPosts( $args ) {

		$this->escape( $args );

		list(
			/* $appkey */
			/* $blog_id */,
			$username,
			$password
		) = $args;

		if ( isset( $args[4] ) ) {
			$query = [ 'numberposts' => absint( $args[4] ) ];
		} else {
			$query = [];
		}

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit posts.' ) );
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.getRecentPosts' );

		$posts_list = wp_get_recent_posts( $query );

		if ( ! $posts_list ) {
			$this->error = new Error( 500, __( 'Either there are no posts, or something went wrong.' ) );
			return $this->error;
		}

		$recent_posts = [];
		foreach ( $posts_list as $entry ) {
			if ( ! current_user_can( 'edit_post', $entry['ID'] ) ) {
				continue;
			}

			$post_date  = $this->_convert_date( $entry['post_date'] );
			$categories = implode( ',', wp_get_post_categories( $entry['ID'] ) );

			$content  = '<title>' . wp_unslash( $entry['post_title'] ) . '</title>';
			$content .= '<category>' . $categories . '</category>';
			$content .= wp_unslash( $entry['post_content'] );

			$recent_posts[] = [
				'userid' => $entry['post_author'],
				'dateCreated' => $post_date,
				'content' => $content,
				'postid' => (string) $entry['ID'],
			];
		}

		return $recent_posts;
	}

	/**
	 * Creates new post.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type string $appkey (unused)
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type string $content
	 *     @type string $publish
	 * }
	 * @return int|\WP\IXR\Error
	 */
	public function blogger_newPost( $args ) {
		$this->escape( $args );

		list(
			/* $appkey */
			/* $blog_id */,
			$username,
			$password,
			$content,
			$publish
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.newPost' );

		$cap = $publish ? 'publish_posts' : 'edit_posts';
		if ( ! current_user_can( get_post_type_object( 'post' )->cap->create_posts ) || ! current_user_can( $cap ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to post on this site.' ) );
		}

		$post_status = $publish ? 'publish' : 'draft';
		$post_author = $user->ID;
		$post_title = xmlrpc_getposttitle( $content );
		$post_category = xmlrpc_getpostcategory( $content );
		$post_content = xmlrpc_removepostdata( $content );

		$post_date = current_time( 'mysql' );
		$post_date_gmt = current_time( 'mysql', 1 );

		$post_data = compact(
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_title',
			'post_category',
			'post_status'
		);

		$post_ID = wp_insert_post( $post_data );
		if ( is_wp_error( $post_ID ) ) {
			return new Error( 500, $post_ID->get_error_message() );
		}

		if ( ! $post_ID ) {
			return new Error( 500, __( 'Sorry, your entry could not be posted.' ) );
		}
		$this->attach_uploads( $post_ID, $post_content );

		/**
		 * Fires after a new post has been successfully created via the XML-RPC Blogger API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $post_ID ID of the new post.
		 * @param array $args    An array of new post arguments.
		 */
		do_action( 'xmlrpc_call_success_blogger_newPost', $post_ID, $args );

		return $post_ID;
	}

	/**
	 * Edit a post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type int    $post_ID
	 *     @type string $username
	 *     @type string $password
	 *     @type string $content
	 *     @type bool   $publish
	 * }
	 * @return true|\WP\IXR\Error true when done.
	 */
	public function blogger_editPost( $args ) {

		$this->escape( $args );

		list(
			/* $blog_id */,
			$post_ID,
			$username,
			$password,
			$content,
			$publish
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}

		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.editPost' );

		$post = get_post( $post_ID, ARRAY_A );

		if ( ! $post || $post['post_type'] != 'post' ) {
			return new Error( 404, __( 'Sorry, no such post.' ) );
		}

		$this->escape( $post );

		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		}
		if ( 'publish' === $post['post_status'] && ! current_user_can( 'publish_posts' ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to publish this post.' ) );
		}

		$postdata = [];
		$postdata['ID'] = $post['ID'];
		$postdata['post_content'] = xmlrpc_removepostdata( $content );
		$postdata['post_title'] = xmlrpc_getposttitle( $content );
		$postdata['post_category'] = xmlrpc_getpostcategory( $content );
		$postdata['post_status'] = $post['post_status'];
		$postdata['post_excerpt'] = $post['post_excerpt'];
		$postdata['post_status'] = $publish ? 'publish' : 'draft';

		$result = wp_update_post( $postdata );

		if ( ! $result ) {
			return new Error( 500, __( 'For some strange yet very annoying reason, this post could not be edited.' ) );
		}
		$this->attach_uploads( $post['ID'], $postdata['post_content'] );

		/**
		 * Fires after a post has been successfully updated via the XML-RPC Blogger API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $post_ID ID of the updated post.
		 * @param array $args    An array of arguments for the post to edit.
		 */
		do_action( 'xmlrpc_call_success_blogger_editPost', $post_ID, $args );

		return true;
	}

	/**
	 * Remove a post.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type int    $post_ID
	 *     @type string $username
	 *     @type string $password
	 * }
	 * @return true|\WP\IXR\Error True when post is deleted.
	 */
	public function blogger_deletePost( $args ) {
		$this->escape( $args );

		list(
			/* $blog_id */,
			$post_ID,
			$username,
			$password
		) = $args;

		$user = $this->login( $username, $password );
		if ( ! $user ) {
			return $this->error;
		}
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'blogger.deletePost' );

		$post = get_post( $post_ID, ARRAY_A );

		if ( ! $post || 'post' !== $post['post_type'] ) {
			return new Error( 404, __( 'Sorry, no such post.' ) );
		}

		if ( ! current_user_can( 'delete_post', $post_ID ) ) {
			return new Error( 401, __( 'Sorry, you are not allowed to delete this post.' ) );
		}

		$result = wp_delete_post( $post_ID );

		if ( ! $result ) {
			return new Error( 500, __( 'The post cannot be deleted.' ) );
		}

		/**
		 * Fires after a post has been successfully deleted via the XML-RPC Blogger API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $post_ID ID of the deleted post.
		 * @param array $args    An array of arguments to delete the post.
		 */
		do_action( 'xmlrpc_call_success_blogger_deletePost', $post_ID, $args );

		return true;
	}
}
