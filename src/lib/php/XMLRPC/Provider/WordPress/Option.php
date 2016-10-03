<?php
namespace WP\XMLRPC\Provider\WordPress;

use WP\IXR\Error;

trait Option {
	/**
	 * Blog options.
	 *
	 * @access public
	 * @var array
	 */
	public $blog_options;

	/**
	 * Retrieve blog options.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $options
	 * }
	 * @return array|Error
	 */
	public function wp_getOptions( $args ) {
		$this->escape( $args );

		$username	= $args[1];
		$password	= $args[2];
		$options	= isset( $args[3] ) ? (array) $args[3] : array();

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		// If no specific options where asked for, return all of them
		if ( count( $options ) == 0 )
			$options = array_keys($this->get_blog_options());

		return $this->_getOptions($options);
	}

	/**
	 * Retrieve blog options value from list.
	 *
	 * @since 2.6.0
	 *
	 * @param array $options Options to retrieve.
	 * @return array
	 */
	public function _getOptions($options) {
		$data = array();
		$can_manage = current_user_can( 'manage_options' );
		$blog_options = $this->get_blog_options();

		foreach ( $options as $option ) {
			if ( array_key_exists( $option, $blog_options ) ) {
				$data[$option] = $blog_options[$option];
				//Is the value static or dynamic?
				if ( isset( $data[$option]['option'] ) ) {
					$data[$option]['value'] = get_option( $data[$option]['option'] );
					unset($data[$option]['option']);
				}

				if ( ! $can_manage )
					$data[$option]['readonly'] = true;
			}
		}

		return $data;
	}

	/**
	 * Update blog options.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type int    $blog_id (unused)
	 *     @type string $username
	 *     @type string $password
	 *     @type array  $options
	 * }
	 * @return array|Error
	 */
	public function wp_setOptions( $args ) {
		$this->escape( $args );

		$username	= $args[1];
		$password	= $args[2];
		$options	= (array) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'manage_options' ) )
			return new Error( 403, __( 'Sorry, you are not allowed to update options.' ) );

		$option_names = array();
		$blog_options = $this->get_blog_options();

		foreach ( $options as $o_name => $o_value ) {
			$option_names[] = $o_name;
			if ( !array_key_exists( $o_name, $blog_options ) )
				continue;

			if ( $blog_options[$o_name]['readonly'] == true )
				continue;

			update_option( $blog_options[$o_name]['option'], wp_unslash( $o_value ) );
		}

		//Now return the updated values
		return $this->_getOptions($option_names);
	}

	/**
	 * Set up blog options property.
	 *
	 * Passes property through {@see 'xmlrpc_blog_options'} filter.
	 *
	 * @since 2.6.0
	 */
	public function get_blog_options() {
		if ( $this->blog_options ) {
			return $this->blog_options;
		}

		$this->blog_options = [
			// Read only options
			'software_name'     => [
				'desc'          => __( 'Software Name' ),
				'readonly'      => true,
				'value'         => 'WordPress'
			],
			'software_version'  => [
				'desc'          => __( 'Software Version' ),
				'readonly'      => true,
				'value'         => get_bloginfo( 'version' )
			],
			'blog_url'          => [
				'desc'          => __( 'WordPress Address (URL)' ),
				'readonly'      => true,
				'option'        => 'siteurl'
			],
			'home_url'          => [
				'desc'          => __( 'Site Address (URL)' ),
				'readonly'      => true,
				'option'        => 'home'
			],
			'login_url'         => [
				'desc'          => __( 'Login Address (URL)' ),
				'readonly'      => true,
				'value'         => wp_login_url( )
			],
			'admin_url'          => [
				'desc'          => __( 'The URL to the admin area' ),
				'readonly'      => true,
				'value'         => get_admin_url( )
			],
			'image_default_link_type' => [
				'desc'          => __( 'Image default link type' ),
				'readonly'      => true,
				'option'        => 'image_default_link_type'
			],
			'image_default_size' => [
				'desc'          => __( 'Image default size' ),
				'readonly'      => true,
				'option'        => 'image_default_size'
			],
			'image_default_align' => [
				'desc'          => __( 'Image default align' ),
				'readonly'      => true,
				'option'        => 'image_default_align'
			],
			'template'          => [
				'desc'          => __( 'Template' ),
				'readonly'      => true,
				'option'        => 'template'
			],
			'stylesheet'        => [
				'desc'          => __( 'Stylesheet' ),
				'readonly'      => true,
				'option'        => 'stylesheet'
			],
			'post_thumbnail'    => [
				'desc'          => __( 'Post Thumbnail' ),
				'readonly'      => true,
				'value'         => current_theme_supports( 'post-thumbnails' )
			],

			// Updatable options
			'time_zone'         => [
				'desc'          => __( 'Time Zone' ),
				'readonly'      => false,
				'option'        => 'gmt_offset'
			],
			'blog_title'        => [
				'desc'          => __( 'Site Title' ),
				'readonly'      => false,
				'option'        => 'blogname'
			],
			'blog_tagline'      => [
				'desc'          => __( 'Site Tagline' ),
				'readonly'      => false,
				'option'        => 'blogdescription'
			],
			'date_format'       => [
				'desc'          => __( 'Date Format' ),
				'readonly'      => false,
				'option'        => 'date_format'
			],
			'time_format'       => [
				'desc'          => __( 'Time Format' ),
				'readonly'      => false,
				'option'        => 'time_format'
			],
			'users_can_register' => [
				'desc'          => __( 'Allow new users to sign up' ),
				'readonly'      => false,
				'option'        => 'users_can_register'
			],
			'thumbnail_size_w'  => [
				'desc'          => __( 'Thumbnail Width' ),
				'readonly'      => false,
				'option'        => 'thumbnail_size_w'
			],
			'thumbnail_size_h'  => [
				'desc'          => __( 'Thumbnail Height' ),
				'readonly'      => false,
				'option'        => 'thumbnail_size_h'
			],
			'thumbnail_crop'    => [
				'desc'          => __( 'Crop thumbnail to exact dimensions' ),
				'readonly'      => false,
				'option'        => 'thumbnail_crop'
			],
			'medium_size_w'     => [
				'desc'          => __( 'Medium size image width' ),
				'readonly'      => false,
				'option'        => 'medium_size_w'
			],
			'medium_size_h'     => [
				'desc'          => __( 'Medium size image height' ),
				'readonly'      => false,
				'option'        => 'medium_size_h'
			],
			'medium_large_size_w'   => [
				'desc'          => __( 'Medium-Large size image width' ),
				'readonly'      => false,
				'option'        => 'medium_large_size_w'
			],
			'medium_large_size_h' => [
				'desc'          => __( 'Medium-Large size image height' ),
				'readonly'      => false,
				'option'        => 'medium_large_size_h'
			],
			'large_size_w'      => [
				'desc'          => __( 'Large size image width' ),
				'readonly'      => false,
				'option'        => 'large_size_w'
			],
			'large_size_h'      => [
				'desc'          => __( 'Large size image height' ),
				'readonly'      => false,
				'option'        => 'large_size_h'
			],
			'default_comment_status' => [
				'desc'          => __( 'Allow people to post comments on new articles' ),
				'readonly'      => false,
				'option'        => 'default_comment_status'
			],
			'default_ping_status' => [
				'desc'          => __( 'Allow link notifications from other blogs (pingbacks and trackbacks) on new articles' ),
				'readonly'      => false,
				'option'        => 'default_ping_status'
			],
		];

		/**
		 * Filters the XML-RPC blog options property.
		 *
		 * @since 2.6.0
		 *
		 * @param array $blog_options An array of XML-RPC blog options.
		 */
		$this->blog_options = apply_filters( 'xmlrpc_blog_options', $this->blog_options );

		return $this->blog_options;
	}
}
