<?hh
/**
 * XML-RPC protocol support for WordPress
 *
 * @package WordPress
 */

use WP\View;
use WP\XMLRPC\{Server,ServerInterface,ServerException};

/**
 * Whether this is an XML-RPC Request
 *
 * @var bool
 */
const XMLRPC_REQUEST = true;

/** Include the bootstrap for setting up WordPress environment */
require_once( __DIR__ . '/wp-load.php' );

// Some browser-embedded clients send cookies. We don't want them.
$_cookie->replace( [] );

// http://cyber.law.harvard.edu/blogs/gems/tech/rsd.html
if ( $_get->has( 'rsd' ) ) {

	$charset = get_option( 'blog_charset' );

	$response->setCharset( $charset );
	$response->headers->set( 'Content-Type', 'text/xml', true );

	$view = new View( $app );

	$view->setData( [
		'url' => get_bloginfo_rss( 'url' ),
		'xmlrpc_url' => site_url( 'xmlrpc.php', 'rpc' ),
		'charset' => $charset,
	] );

	$xml = $view->render( 'xmlrpc/rsd', $view );
	$response->setContent( $xml );
	$response->send();
	return;
}

require_once( ABSPATH . 'wp-admin/includes/admin.php' );

/**
 * Posts submitted via the XML-RPC interface get that title
 * @var string
 */
$app->set( 'post_default_title', '' );

/**
 * Filters the class used for handling XML-RPC requests.
 *
 * @since 3.1.0
 *
 * @param string $class The name of the XML-RPC server class.
 */
$wp_xmlrpc_server_class = apply_filters( 'wp_xmlrpc_server_class', Server::class );
$wp_xmlrpc_server = new $wp_xmlrpc_server_class();
if ( ! ( $wp_xmlrpc_server instanceof ServerInterface ) ) {
	throw new ServerException( 'XMLRPC Server must implement ' . ServerInterface::class );
}

// Fire off the request
$wp_xmlrpc_server->serve_request();
