<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Server {
	public $callbacks = [];

	protected $data;
	protected $message;
	protected $capabilities;

	public function __construct( $callbacks = [], $data = false, $wait = false ) {
		$this->setCapabilities();
		if ( ! empty( $callbacks ) ) {
			$this->callbacks = $callbacks;
		}
		$this->setCallbacks();

		if ( ! $wait ) {
			$this->serve( $data );
		}
	}

	private function serve( $data = false ) {
		if ( ! $data ) {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				if ( function_exists( 'status_header' ) ) {
					status_header( 405 ); // WP #20986
					header( 'Allow: POST' );
				}
				header( 'Content-Type: text/plain' ); // merged from WP #9093
				die( 'XML-RPC server accepts POST requests only.' );
			}

			$data = file_get_contents( 'php://input' );
		}
		$this->message = new Message( $data );
		if ( ! $this->message->parse() ) {
			$this->error( -32700, 'parse error. not well formed' );
		}
		if ( 'methodCall' !== $this->message->messageType ) {
			$this->error( -32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall') ;
		}
		$result = $this->call( $this->message->methodName, $this->message->params );

		// Is the result an error?
		if ( $result instanceof Error ) {
			$this->error( $result );
		}

		// Encode the result
		$r = new Value( $result );
		$resultxml = $r->getXml();

		// Create the XML
		$xml = "
			<methodResponse>
			<params>
			<param>
			  <value>
				{$resultxml}
			  </value>
			</param>
			</params>
			</methodResponse>
		";
		// Send it
		$this->output( $xml );
	}

	public function call( string $methodname, array $args )
	{
		if ( ! $this->hasMethod( $methodname ) ) {
			return new Error( -32601, "server error. requested method {$methodname} does not exist." );
		}
		$method = $this->callbacks[ $methodname ];

		// Perform the callback and send the response
		if ( 1 === count( $args ) ) {
			// If only one parameter just send that instead of the whole array
			$args = $args[0];
		}

		// Are we dealing with a function or a method?
		if ( is_string( $method ) && 'this:' === substr( $method, 0, 5 ) ) {
			// It's a class method - check it exists
			$method = substr( $method, 5 );
			if ( ! method_exists( $this, $method ) ) {
				return new Error( -32601, "server error. requested class method \"{$method}\" does not exist." );
			}

			// Call the method
			$result = $this->{$method}( $args );
		} else {
			// It's a function - does it exist?
			if ( is_array( $method ) && ! is_callable( $method ) ) {
				return new Error( -32601, "server error. requested object method \"{$method[1]}\" does not exist." );
			} elseif ( ! is_array( $method ) && ! function_exists( $method ) ) {
				return new Error( -32601, "server error. requested function \"{$method}\" does not exist." );
			}

			// Call the function
			$result = call_user_func( $method, $args );
		}
		return $result;
	}

	private function error( int $error, string $message = '' ) {
		// Accepts either an error object or an error code and message
		if ( $message && ! is_object( $error ) ) {
			$error = new Error( $error, $message );
		}
		$this->output( $error->getXml() );
	}

	private function output( string $xml ) {
		$charset = function_exists( 'get_option' ) ? get_option( 'blog_charset' ) : '';
		if ( $charset ) {
			$xml = '<?xml version="1.0" encoding="' . $charset . '"?>' . "\n" . $xml;
		} else {
			$xml = '<?xml version="1.0"?>' . "\n" . $xml;
		}

		header( 'Connection: close' );

		$type = [ 'Content-Type: text/xml' ];
		if ( $charset ) {
			$type[] = "charset={$charset}";
		}
		header( join( '; ', $type ) );
		header( 'Date: ' . date( 'r' ) );
		echo $xml;
		exit();
	}

	protected function hasMethod( string $method ): bool
	{
		return in_array( $method, array_keys( $this->callbacks ) );
	}

	protected function setCapabilities()
	{
		// Initialises capabilities array
		$this->capabilities = [
			'xmlrpc' => [
				'specUrl' => 'http://www.xmlrpc.com/spec',
				'specVersion' => 1
			],
			'faults_interop' => [
				'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
				'specVersion' => 20010516
			],
			'system.multicall' => [
				'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
				'specVersion' => 1
			],
		];
	}

	protected function getCapabilities(): array
	{
		return $this->capabilities;
	}

	protected function setCallbacks() {
		$this->callbacks['system.getCapabilities'] = 'this:getCapabilities';
		$this->callbacks['system.listMethods'] = 'this:listMethods';
		$this->callbacks['system.multicall'] = 'this:multiCall';
	}

	public function listMethods(): array
	{
		// Returns a list of methods - uses array_reverse to ensure user defined
		// methods are listed before server defined methods
		return array_reverse( array_keys( $this->callbacks ) );
	}

	public function multiCall( $methodcalls ): array
	{
		// See http://www.xmlrpc.com/discuss/msgReader$1208
		$return = [];
		foreach ( $methodcalls as $call ) {
			if ( 'system.multicall' === $call['methodName'] ) {
				$result = new Error( -32600, 'Recursive calls to system.multicall are forbidden' );
			} else {
				$result = $this->call( $call['methodName'], $call['params'] );
			}
			if ( $result instanceof Error ) {
				$return[] = [
					'faultCode' => $result->code,
					'faultString' => $result->message
				];
			} else {
				$return[] = [ $result ];
			}
		}
		return $return;
	}
}
