<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;

trait Multisite {
	/**
	 * Retrieve the blogs of the user.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type string $username Username.
	 *     @type string $password Password.
	 * }
	 * @return array|Error Array contains:
	 *  - 'isAdmin'
	 *  - 'isPrimary' - whether the blog is the user's primary blog
	 *  - 'url'
	 *  - 'blogid'
	 *  - 'blogName'
	 *  - 'xmlrpc' - url of xmlrpc endpoint
	 */
	public function wp_getUsersBlogs( $args ) {
		if ( ! $this->minimum_args( $args, 2 ) ) {
			return $this->error;
		}

		// If this isn't on WPMU then just use blogger_getUsersBlogs
		if ( !is_multisite() ) {
			array_unshift( $args, 1 );
			return $this->blogger_getUsersBlogs( $args );
		}

		$this->escape( $args );

		$username = $args[0];
		$password = $args[1];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		/**
		 * Fires after the XML-RPC user has been authenticated but before the rest of
		 * the method logic begins.
		 *
		 * All built-in XML-RPC methods use the action xmlrpc_call, with a parameter
		 * equal to the method's name, e.g., wp.getUsersBlogs, wp.newPost, etc.
		 *
		 * @since 2.5.0
		 *
		 * @param string $name The method name.
		 */
		do_action( 'xmlrpc_call', 'wp.getUsersBlogs' );

		$blogs = (array) get_blogs_of_user( $user->ID );
		$struct = array();
		$primary_blog_id = 0;
		$active_blog = get_active_blog_for_user( $user->ID );
		if ( $active_blog ) {
			$primary_blog_id = (int) $active_blog->blog_id;
		}

		foreach ( $blogs as $blog ) {
			// Don't include blogs that aren't hosted at this site.
			if ( $blog->site_id != get_current_site()->id )
				continue;

			$blog_id = $blog->userblog_id;

			switch_to_blog( $blog_id );

			$is_admin = current_user_can( 'manage_options' );
			$is_primary = ( (int) $blog_id === $primary_blog_id );

			$struct[] = array(
				'isAdmin'   => $is_admin,
				'isPrimary' => $is_primary,
				'url'       => home_url( '/' ),
				'blogid'    => (string) $blog_id,
				'blogName'  => get_option( 'blogname' ),
				'xmlrpc'    => site_url( 'xmlrpc.php', 'rpc' ),
			);

			restore_current_blog();
		}

		return $struct;
	}
}