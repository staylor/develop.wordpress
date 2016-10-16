<?php
namespace WP\XMLRPC\Provider;

use WP\IXR\Error;
use WP\XMLRPC\{Server,Utils as ServerUtils};
use function WP\getApp;
/**
 * PingBack functions
 * specs on www.hixie.ch/specs/pingback/pingback
 */
class Pingback implements ProviderInterface {
	use ServerUtils;

	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			'pingback.ping' => [ $this, 'pingback_ping' ],
			'pingback.extensions.getPingbacks' => [ $this, 'pingback_extensions_getPingbacks' ],
		] );

		return $this;
	}

	/**
	 * Retrieves a pingback and registers it.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type string $pagelinkedfrom
	 *     @type string $pagelinkedto
	 * }
	 * @return string|Error
	 */
	public function pingback_ping( $args ) {
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'pingback.ping' );

		$this->escape( $args );

		$arg0 = str_replace( '&amp;', '&', $args[0] );
		$arg1 = str_replace( '&amp;', '&', $args[1] );
		$pagelinkedto = str_replace( '&', '&amp;', $arg1 );

		/**
		 * Filters the pingback source URI.
		 *
		 * @since 3.6.0
		 *
		 * @param string $pagelinkedfrom URI of the page linked from.
		 * @param string $pagelinkedto   URI of the page linked to.
		 */
		$pagelinkedfrom = apply_filters( 'pingback_ping_source_uri', $arg0, $pagelinkedto );
		if ( ! $pagelinkedfrom ) {
			return $this->pingback_error( 0, __( 'A valid URL was not provided.' ) );
		}

		// Check if the page linked to is in our site
		$pos1 = strpos( $pagelinkedto, str_replace(
			[
				'http://www.',
				'http://',
				'https://www.',
				'https://'
			],
			'',
			get_option( 'home' )
		) );

		if ( ! $pos1 ) {
			return $this->pingback_error( 0, __( 'Is there no link to us?' ) );
		}

		$app = getApp();
		$db = $app['db'];

		// let's find which post is linked to
		// FIXME: does url_to_postid() cover all these cases already?
		//        if so, then let's use it and drop the old code.
		$urltest = parse_url( $pagelinkedto );
		$post_ID = url_to_postid( $pagelinkedto );
		$match = null;

		if ( $post_ID ) {
			// $way
		} elseif ( isset( $urltest['path'] ) && preg_match( '#p/[0-9]{1,}#', $urltest['path'], $match ) ) {
			// the path defines the post_ID (archives/p/XXXX)
			$post_ID = (int) explode( '/', $match[0] )[1];
		} elseif ( isset( $urltest['query'] ) && preg_match( '#p=[0-9]{1,}#', $urltest['query'], $match ) ) {
			// the querystring defines the post_ID (?p=XXXX)
			$post_ID = (int) explode( '=', $match[0] )[1];
		} elseif ( isset( $urltest['fragment'] ) ) {
			// an #anchor is there, it's either...
			if ( (int) $urltest['fragment'] ) {
				// ...an integer #XXXX (simplest case)
				$post_ID = (int) $urltest['fragment'];
			} elseif ( preg_match( '/post-[0-9]+/', $urltest['fragment'] ) ) {
				// ...a post id in the form 'post-###'
				$post_ID = (int) preg_replace( '/[^0-9]+/', '', $urltest['fragment'] );
			} elseif ( is_string( $urltest['fragment'] ) ) {
				// ...or a string #title, a little more complicated
				$title = preg_replace( '/[^a-z0-9]/i', '.', $urltest['fragment'] );
				$sql = "SELECT ID FROM {$db->posts} WHERE post_title RLIKE %s";
				$post_ID = (int) $db->get_var( $db->prepare( $sql, $title ) );
			}

			if ( ! $post_ID  ) {
				// returning unknown error '0' is better than die()ing
				return $this->pingback_error( 0, '' );
			}

		} else {
			// TODO: Attempt to extract a post ID from the given URL
	  		return $this->pingback_error( 33, __( 'The specified target URL cannot be used as a target. It either doesn&#8217;t exist, or it is not a pingback-enabled resource.' ) );
		}

		$post = get_post( $post_ID );

		// Post_ID not found
		if ( ! $post ) {
	  		return $this->pingback_error( 33, __( 'The specified target URL cannot be used as a target. It either doesn&#8217;t exist, or it is not a pingback-enabled resource.' ) );
		}

		if ( $post_ID === url_to_postid( $pagelinkedfrom ) ) {
			return $this->pingback_error( 0, __( 'The source URL and the target URL cannot both point to the same resource.' ) );
		}

		// Check if pings are on
		if ( ! pings_open( $post ) ) {
	  		return $this->pingback_error( 33, __( 'The specified target URL cannot be used as a target. It either doesn&#8217;t exist, or it is not a pingback-enabled resource.' ) );
		}

		// Let's check that the remote site didn't already pingback this entry
		$sql = "SELECT * FROM {$db->comments} WHERE comment_post_ID = %d AND comment_author_url = %s";
		if ( $db->get_results( $db->prepare( $sql, $post_ID, $pagelinkedfrom ) ) ) {
			return $this->pingback_error( 48, __( 'The pingback has already been registered.' ) );
		}
		// very stupid, but gives time to the 'from' server to publish !
		sleep( 1 );

		$app = getApp();
		$remote_ip = preg_replace( '/[^0-9a-fA-F:., ]/', '', $app['request']->server->get( 'REMOTE_ADDR' ) );

		/** This filter is documented in wp-includes/class-http.php */
		$user_agent = apply_filters( 'http_headers_useragent', 'WordPress/' . $app['wp_version'] . '; ' . get_bloginfo( 'url' ) );

		// Let's check the remote site
		$request = wp_safe_remote_get( $pagelinkedfrom, [
			'timeout' => 10,
			'redirection' => 0,
			'limit_response_size' => 153600, // 150 KB
			'user-agent' => "{$user_agent}; verifying pingback from {$remote_ip}",
			'headers' => [
				'X-Pingback-Forwarded-For' => $remote_ip,
			],
		] );
		$source = $remote_source_original = wp_remote_retrieve_body( $request );

		if ( ! $source ) {
			return $this->pingback_error( 16, __( 'The source URL does not exist.' ) );
		}

		/**
		 * Filters the pingback remote source.
		 *
		 * @since 2.5.0
		 *
		 * @param string $source Response source for the page linked from.
		 * @param string $pagelinkedto  URL of the page linked to.
		 */
		$pre_remote_source = apply_filters( 'pre_remote_source', $source, $pagelinkedto );

		// Work around bug in strip_tags():
		$alter_doc = str_replace( '<!DOC', '<DOC', $pre_remote_source );
		// normalize spaces
		$no_extra_space = preg_replace( '/[\r\n\t ]+/', ' ', $alter_doc );
		$no_tags = preg_replace( "/<\/*(h1|h2|h3|h4|h5|h6|p|th|td|li|dt|dd|pre|caption|input|textarea|button|body)[^>]*>/", "\n\n", $no_extra_space );

		$matchtitle = null;
		preg_match( '|<title>([^<]*?)</title>|is', $no_tags, $matchtitle );
		$title = $matchtitle[1] ?? '';
		if ( empty( $title ) ) {
			return $this->pingback_error( 32, __( 'We cannot find a title on that page.' ) );
		}

		$remote_source = strip_tags( $no_tags, '<a>' ); // just keep the tag we need

		$p = explode( "\n\n", $remote_source );

		$preg_target = preg_quote( $pagelinkedto, '|' );

		foreach ( $p as $para ) {
			if ( false === strpos( $para, $pagelinkedto) ) {
				continue;
			}
			$context = null;

			// it exists, but is it a link?
			preg_match( "|<a[^>]+?".$preg_target."[^>]*>([^>]+?)</a>|", $para, $context );

			// If the URL isn't in a link context, keep looking
			if ( empty( $context ) ) {
				continue;
			}

			// We're going to use this fake tag to mark the context in a bit
			// the marker is needed in case the link text appears more than once in the paragraph
			$faked = preg_replace( '|\</?wpcontext\>|', '', $para );

			// prevent really long link text
			if ( strlen( $context[1] ) > 100 ) {
				$context[1] = substr( $context[1], 0, 100 ) . '&#8230;';
			}
			// set up our marker
			$marker = '<wpcontext>'.$context[1].'</wpcontext>';

			// swap out the link for our marker
			$swapped = str_replace( $context[0], $marker, $faked );

			// strip all tags but our context marker
			$stripped = strip_tags( $swapped, '<wpcontext>' );

			$replaced = preg_replace(
				"|.*?\s(.{0,100}" . preg_quote( $marker, '|' ) . ".{0,100})\s.*|s",
				'$1',
				trim( $stripped )
			);

			// YES, again, to remove the marker wrapper
			$excerpt = strip_tags( $replaced );
			break;
		}

		// Link to target not found
		if ( empty( $context ) ) {
			return $this->pingback_error( 17, __( 'The source URL does not contain a link to the target URL, and so cannot be used as a source.' ) );
		}

		$comment_content = '[&#8230;] ' . esc_html( $excerpt ) . ' [&#8230;]';
		$this->escape( $comment_content );

		$comment_post_ID = (int) $post_ID;

		$comment_author = $title;
		$comment_author_email = '';
		$this->escape( $comment_author );
		$replaced = str_replace( '&', '&amp;', $pagelinkedfrom );
		$comment_author_url = $this->escape( $replaced );

		$comment_type = 'pingback';

		$commentdata = compact(
			'comment_post_ID',
			'comment_author',
			'comment_author_url',
			'comment_author_email',
			'comment_content',
			'comment_type',
			'remote_source',
			'remote_source_original'
		);

		$comment_ID = wp_new_comment( $commentdata );

		/**
		 * Fires after a post pingback has been sent.
		 *
		 * @since 0.71
		 *
		 * @param int $comment_ID Comment ID.
		 */
		do_action( 'pingback_post', $comment_ID );

		/* translators: 1: URL of the page linked from, 2: URL of the page linked to */
		return sprintf( __( 'Pingback from %1$s to %2$s registered. Keep the web talking! :-)' ), $pagelinkedfrom, $pagelinkedto );
	}

	/**
	 * Retrieve array of URLs that pingbacked the given URL.
	 *
	 * Specs on http://www.aquarionics.com/misc/archives/blogite/0198.html
	 *
	 * @since 1.5.0
	 *
	 * @param string $url
	 * @return array|Error
	 */
	public function pingback_extensions_getPingbacks( $url ) {
		/** This action is documented in wp-includes/class-wp-xmlrpc-server.php */
		do_action( 'xmlrpc_call', 'pingback.extensions.getPingbacks' );

		$escaped_url = $this->escape( $url );

		$id = url_to_postid( $escaped_url );
		if ( ! $id ) {
			// We aren't sure that the resource is available and/or pingback enabled
	  		return $this->pingback_error( 33, __( 'The specified target URL cannot be used as a target. It either doesn&#8217;t exist, or it is not a pingback-enabled resource.' ) );
		}

		$post = get_post( $id, ARRAY_A );
		if ( ! $post ) {
			// No such post = resource not found
	  		return $this->pingback_error( 32, __( 'The specified target URL does not exist.' ) );
		}

		$app = getApp();
		$db = $app['db'];
		$sql = "SELECT comment_author_url, comment_content, comment_author_IP, comment_type FROM {$db->comments} WHERE comment_post_ID = %d";
		$comments = $db->get_results( $db->prepare( $sql, $id ) );

		if ( ! $comments ) {
			return [];
		}

		$pingbacks = [];
		foreach ( $comments as $comment ) {
			if ( 'pingback' === $comment->comment_type ) {
				$pingbacks[] = $comment->comment_author_url;
			}
		}

		return $pingbacks;
	}

	/**
	 * Sends a pingback error based on the given error code and message.
	 *
	 * @since 3.6.0
	 *
	 * @param int    $code    Error code.
	 * @param string $message Error message.
	 * @return Error Error object.
	 */
	protected function pingback_error( $code, $message ) {
		/**
		 * Filters the XML-RPC pingback error return.
		 *
		 * @since 3.5.1
		 *
		 * @param \WP\IXR\Error $error An Error object containing the error code and message.
		 */
		return apply_filters( 'xmlrpc_pingback_error', new Error( $code, $message ) );
	}
}
