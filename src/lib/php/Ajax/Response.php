<?php
namespace WP\Ajax;

use function WP\getApp;
/**
 * Send XML response back to Ajax request.
 *
 * @package WordPress
 * @since 2.1.0
 */
class Response {
	/**
	 * Store XML responses to send.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $responses = [];

	/**
	 * Constructor - Passes args to Response::add().
	 *
	 * @since 2.1.0
	 * @see Response::add()
	 *
	 * @param array $args Optional. Will be passed to add() method.
	 */
	public function __construct( $args = [] ) {
		if ( ! empty( $args ) ) {
			$this->add( $args );
		}
	}

	/**
	 * Appends data to an XML response based on given arguments.
	 *
	 * With `$args` defaults, extra data output would be:
	 *
	 *     <response action='{$action}_$id'>
	 *      <$what id='$id' position='$position'>
	 *          <response_data><![CDATA[$data]]></response_data>
	 *      </$what>
	 *     </response>
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @param array $args {
	 *     Optional. An array or string of XML response arguments.
	 *
	 *     @type string          $what         XML-RPC response type. Used as a child element of `<response>`.
	 *                                         Default 'object' (`<object>`).
	 *     @type string|false    $action       Value to use for the `action` attribute in `<response>`. Will be
	 *                                         appended with `_$id` on output. If false, `$action` will default to
	 *                                         the value of `$_POST['action']`. Default false.
	 *     @type int|Error    $id           The response ID, used as the response type `id` attribute. Also
	 *                                         accepts an `Error` object if the ID does not exist. Default 0.
	 *     @type int|false       $old_id       The previous response ID. Used as the value for the response type
	 *                                         `old_id` attribute. False hides the attribute. Default false.
	 *     @type string          $position     Value of the response type `position` attribute. Accepts 1 (bottom),
	 *                                         -1 (top), html ID (after), or -html ID (before). Default 1 (bottom).
	 *     @type string|Error $data         The response content/message. Also accepts a Error object if the
	 *                                         ID does not exist. Default empty.
	 *     @type array           $supplemental An array of extra strings that will be output within a `<supplemental>`
	 *                                         element as CDATA. Default empty array.
	 * }
	 * @return array
	 */
	public function add( array $args = [] ) {
		$defaults = [
			'what' => 'object',
			'action' => false,
			'id' => '0',
			'old_id' => false,
			'position' => 1,
			'data' => '',
			'supplemental' => []
		];

		$r = wp_parse_args( $args, $defaults );

		$data = $r['data'];

		if ( is_wp_error( $r['id'] ) ) {
			$data = $r['id'];
			$r['id'] = 0;
		}

		$errors = [];

		if ( is_wp_error( $data ) ) {
			foreach ( (array) $data->get_error_codes() as $code ) {
				$error = [
					'code' => $code,
					'message' => $data->get_error_message( $code ),
				];

				if ( ! $error_data = $data->get_error_data( $code ) ) {
					$errors[] = $error;
					continue;
				}

				if ( is_object( $error_data ) ) {
					$error['class'] = get_class( $error_data );
					$vars = get_object_vars( $error_data );
					$map = [];
					foreach ( $vars as $key => $value ) {
						$map[] = [
							'key' => $key,
							'value' => $value,
						];
					}
				} else {
					$map = [
						[
							'key' => '',
							'value' => $error_data,
						]
					];
				}

				$errors['data'] = $map;
				$errors[] = $error;
			}
		}

		$supplemental = [];
		if ( is_array( $r['supplemental'] ) ) {
			foreach ( $r['supplemental'] as $k => $v ) {
				$supplemental[] = [
					'key' => $k,
					'value' => $v,
				];
			}
		}

		if ( false === $r['action'] ) {
			$app = getApp();
			$r['action'] = $app['request']->request->get( 'action' );
		}

		$response = [
			'action' => $r['action'],
			'id' => $r['id'],
			'what' => $r['what'],
			'old_id' => $r['old_id'],
			'position' => preg_replace( '/[^a-z0-9:_-]/i', '', $r['position'] ),
			'errors' => $errors,
			'response' => $data,
			'supplemental' => $supplemental,
		];

		$this->responses[] = $response;

		return $response;
	}

	/**
	 * Display XML formatted responses.
	 *
	 * Sets the content type header to text/xml.
	 *
	 * @since 2.1.0
	 */
	public function send() {
		$charset = get_option( 'blog_charset' );

		$app = getApp();
		$response = $app['response'];

		$response->setCharset( $charset );
		$response->headers->set( 'Content-Type', 'text/xml; charset=' . $charset );

		$xml = $app['mustache']->render( 'ajax/response', [
			'charset' => $charset,
			'responses' => $this->responses,
		] );

		$stripped = preg_replace( "#[\n\t\r]#", '', $xml );
		$response->setContent( $stripped );
		$response->send();

		if ( wp_doing_ajax() ) {
			wp_die();
		} else {
			die();
		}
	}
}
