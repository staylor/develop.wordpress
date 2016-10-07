<?php
namespace WP\XMLRPC;

use WP\IXR\Server as IXRServer;
use WP\XMLRPC\Provider\ProviderInterface;
/**
 * XML-RPC protocol support for WordPress
 *
 * @package WordPress
 * @subpackage Publishing
 */

/**
 * WordPress XMLRPC server implementation.
 *
 * Implements compatibility for Blogger API, MetaWeblog API, MovableType, and
 * pingback. Additional WordPress API for managing comments, pages, posts,
 * options, etc.
 *
 * As of WordPress 3.5.0, XML-RPC is enabled by default. It can be disabled
 * via the {@see 'xmlrpc_enabled'} filter found in wp_xmlrpc_server::login().
 *
 * @package WordPress
 * @subpackage Publishing
 * @since 1.5.0
 */
class Server extends IXRServer implements ServerInterface {
	use Utils;

	/**
	 * Methods.
	 *
	 * @access public
	 * @var array
	 */
	public $methods = [];

	/**
	 * @access public
	 * @var WP\IXR\Error
	 */
	public $error;

	public function register( ProviderInterface $provider ): ProviderInterface
	{
		return $provider->register( $this );
	}

	public function addMethods( array $methods ) {
		$this->methods = array_merge( $this->methods, $methods );
	}

	/**
	 * Registers all of the XMLRPC methods that XMLRPC server understands.
	 *
	 * Sets up server and method property. Passes XMLRPC
	 * methods through the {@see 'xmlrpc_methods'} filter to allow plugins to extend
	 * or replace XML-RPC methods.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->register( new Provider\Demo() );
		$this->register( new Provider\WordPress() );
		$blogger = $this->register( new Provider\Blogger() );
		$mw = $this->register( new Provider\MetaWeblog() );
		$this->register( new Provider\MovableType() );
		$this->register( new Provider\Pingback() );

		$this->addMethods( [
			// WordPress API
			'wp.getCategories' => [ $mw, 'mw_getCategories' ],
			'wp.uploadFile'	=> [ $mw, 'mw_newMediaObject' ],

			// MetaWeblog API aliases for Blogger API
			// see http://www.xmlrpc.com/stories/storyReader$2460
			'metaWeblog.deletePost' => [ $blogger, 'blogger_deletePost' ],
			'metaWeblog.getUsersBlogs' => [ $blogger, 'blogger_getUsersBlogs' ],
		] );

		/**
		 * Filters the methods exposed by the XML-RPC server.
		 *
		 * This filter can be used to add new methods, and remove built-in methods.
		 *
		 * @since 1.5.0
		 *
		 * @param array $methods An array of XML-RPC methods.
		 */
		$this->methods = apply_filters( 'xmlrpc_methods', $this->methods );
	}

	/**
	 * Serves the XML-RPC request.
	 *
	 * @since 2.9.0
	 * @access public
	 */
	public function serve_request( $null, $data = false, $wait = false ) {
		parent::__construct( $this->methods, $data, $wait );
	}
}
