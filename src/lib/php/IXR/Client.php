<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Client {
	public $server;

    public $port;
    public $path;

    protected $useragent;
    protected $response;
    protected $message = false;
    protected $debug = false;
    protected $timeout;
    protected $headers = [];

    // Storage place for an error message
    protected $error = false;

    public function __construct(
		$server,
		string $path = '',
		int $port = 80,
		int $timeout = 15
	) {
        if ( ! $path ) {
            // Assume we have been given a URL instead
            $bits = parse_url( $server );
            $this->server = $bits['host'];
            $this->port = $bits['port'] ?? $port;
            $this->path = $bits['path'] ?? '/';

            // Make absolutely sure we have a path
            if ( ! $this->path ) {
                $this->path = '/';
            }

            if ( ! empty( $bits['query'] ) ) {
                $this->path .= '?' . $bits['query'];
            }
        } else {
            $this->server = $server;
            $this->path = $path;
            $this->port = $port;
        }

        $this->useragent = 'The Incutio XML-RPC PHP Library';
        $this->timeout = $timeout;
    }

    public function query(): bool
	{
        $args = func_get_args();
        $method = array_shift( $args );

        $r = new Request( $method, $args );
        $length = $r->getLength();
        $xml = $r->getXml();

        $nl = "\r\n";
        $request  = 'POST ' . $this->path . ' HTTP/1.0' . $nl;

        // Merged from WP #8145 - allow custom headers
        $this->headers['Host']          = $this->server;
        $this->headers['Content-Type']  = 'text/xml';
        $this->headers['User-Agent']    = $this->useragent;
        $this->headers['Content-Length']= $length;

        foreach ( $this->headers as $header => $value ) {
            $request .= sprintf( '%s: %s%s', $header, $value, $nl );
        }
        $request .= $nl;
        $request .= $xml;

        // Now send the request
        if ( $this->debug ) {
            echo '<pre class="ixr_request">' . htmlspecialchars( $request ) . "\n</pre>\n\n";
        }

        if ( $this->timeout ) {
            $fp = fsockopen( $this->server, $this->port, null, null, $this->timeout );
        } else {
            $fp = fsockopen( $this->server, $this->port );
        }

        if ( ! $fp ) {
            $this->error = new Error( -32300, 'transport error - could not open socket' );
            return false;
        }
        fputs( $fp, $request );

        $contents = '';
        $debugContents = '';
        $gotFirstLine = false;
        $gettingHeaders = true;

		while ( ! feof( $fp ) ) {
            $line = fgets( $fp, 4096 );
            if ( ! $gotFirstLine ) {
                // Check line for '200'
                if ( false === strstr( $line, '200' ) ) {
                    $this->error = new Error( -32300, 'transport error - HTTP status code was not 200' );
                    return false;
                }
                $gotFirstLine = true;
            }

            if ( '' === trim( $line ) ) {
                $gettingHeaders = false;
            }

            if ( ! $gettingHeaders ) {
            	// merged from WP #12559 - remove trim
                $contents .= $line;
            }

            if ( $this->debug ) {
            	$debugContents .= $line;
            }
        }

        if ( $this->debug ) {
            echo '<pre class="ixr_response">' . htmlspecialchars( $debugContents ) . "\n</pre>\n\n";
        }

        // Now parse what we've got back
        $this->message = new Message( $contents );
        if ( ! $this->message->parse() ) {
            // XML error
            $this->error = new Error( -32700, 'parse error. not well formed' );
            return false;
        }

        // Is the message a fault?
        if ( 'fault' === $this->message->messageType ) {
            $this->error = new Error( $this->message->faultCode, $this->message->faultString );
            return false;
        }

        // Message must be OK
        return true;
    }

    public function getResponse() {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    public function isError()
    {
        return is_object( $this->error );
    }

    public function getErrorCode(): int
    {
        return $this->error->code;
    }

    public function getErrorMessage(): string
    {
        return $this->error->message;
    }
}
