<?php
namespace WP\IXR;
/**
 * @package WordPress
 * @since 3.1.0
 */
class HttpClient extends Client {
	public $scheme;
	/**
	 * @var Error
	 */
	public $error;

	/**
	 * @param string $server
	 * @param string $path
	 * @param int $port
	 * @param int $timeout
	 */
	public function __construct(
		$server,
		string $path = '',
		int $port = 80,
		int $timeout = 15
	) {
		parent::__construct( $server, $path, $port, $timeout );

		if ( ! $path ) {
			$bits = parse_url( $server );
			$this->scheme = $bits['scheme'];
		} else {
			$this->scheme = 'http';
		}
	}

	/**
	 * @return bool
	 */
	public function query(): bool
	{
		$args = func_get_args();
		$method = array_shift( $args );

		$request = new Request( $method, $args );
		$xml = $request->getXml();

		$port = $this->port ? ':' . $this->port : '';
		$url = $this->scheme . '://' . $this->server . $port . $this->path;
		$params = [
			'headers'    => [ 'Content-Type' => 'text/xml' ],
			'user-agent' => $this->useragent,
			'body'       => $xml,
		];

		// Merge Custom headers ala #8145
		foreach ( $this->headers as $header => $value ) {
			$params['headers'][ $header ] = $value;
		}

		/**
		 * Filters the headers collection to be sent to the XML-RPC server.
		 *
		 * @since 4.4.0
		 *
		 * @param array $headers Array of headers to be sent.
		 */
		$params['headers'] = apply_filters( 'wp_http_ixr_client_headers', $params['headers'] );

		if ( false !== $this->timeout ) {
			$params['timeout'] = $this->timeout;
		}

		// Now send the request
		if ( $this->debug ) {
			echo '<pre class="ixr_request">' . htmlspecialchars( $xml ) . "\n</pre>\n\n";
		}

		$response = wp_remote_post( $url, $params );

		if ( is_wp_error( $response ) ) {
			$errno    = $response->get_error_code();
			$errorstr = $response->get_error_message();
			$this->error = new Error( -32300, sprintf(
				'transport error: %d %s',
				$errno,
				$errorstr
			) );
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->error = new Error(
				-32301,
				'transport error - HTTP status code was not 200 (' . wp_remote_retrieve_response_code( $response ) . ')'
			);
			return false;
		}

		if ( $this->debug ) {
			echo '<pre class="ixr_response">' . htmlspecialchars( wp_remote_retrieve_body( $response ) ) . "\n</pre>\n\n";
		}

		// Now parse what we've got back
		$this->message = new Message( wp_remote_retrieve_body( $response ) );
		if ( ! $this->message->parse() ) {
			// XML error
			$this->error = new Error( -32700, 'parse error. not well formed' );
			return false;
		}

		// Is the message a fault?
		if ( 'fault' === $this->message->messageType ) {
			$this->error = new Error(
				$this->message->faultCode,
				$this->message->faultString
			);
			return false;
		}

		// Message must be OK
		return true;
	}
}
