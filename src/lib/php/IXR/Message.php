<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 *
 */
class Message {

	private $message;

	// methodCall / methodResponse / fault
	public $messageType;
	public $methodName;
	public $params;
	public $faultCode;
	public $faultString;

	// Current variable stacks
	private $_arraystructs = [];   // The stack used to keep track of the current array/struct
	private $_arraystructstypes = []; // Stack keeping track of if things are structs or array
	private $_currentStructName = [];  // A stack as well
	private $_currentTagContents;
	// The XML parser
	private $_parser;

	public function __construct( $message ) {
		$this->message =& $message;
	}

	public function parse(): bool
	{
		// first remove the XML declaration
		// merged from WP #10698 - this method avoids the RAM usage of preg_replace on very large messages
		$xml = preg_replace( '/<\?xml.*?\?'.'>/s', '', substr( $this->message, 0, 100 ), 1 );
		$this->message = trim( substr_replace( $this->message, $xml, 0, 100 ) );
		if ( '' == $this->message ) {
			return false;
		}

		// Then remove the DOCTYPE
		$doctype = preg_replace( '/^<!DOCTYPE[^>]*+>/i', '', substr( $this->message, 0, 200 ), 1 );
		$this->message = trim( substr_replace( $this->message, $doctype, 0, 200 ) );
		if ( '' == $this->message ) {
			return false;
		}

		// Check that the root tag is valid
		$root_tag = substr( $this->message, 0, strcspn( substr( $this->message, 0, 20 ), "> \t\r\n" ) );
		if ( '<!DOCTYPE' === strtoupper( $root_tag ) ) {
			return false;
		}

		if ( ! in_array( $root_tag, [ '<methodCall', '<methodResponse', '<fault' ] ) ) {
			return false;
		}

		// Bail if there are too many elements to parse
		$element_limit = 30000;
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the number of elements to parse in an XML-RPC response.
			 *
			 * @since 4.0.0
			 *
			 * @param int $element_limit Default elements limit.
			 */
			$element_limit = apply_filters( 'xmlrpc_element_limit', $element_limit );
		}
		if ( $element_limit && 2 * $element_limit < substr_count( $this->message, '<' ) ) {
			return false;
		}

		$this->_parser = xml_parser_create();
		// Set XML parser to take the case of tags in to account
		xml_parser_set_option( $this->_parser, XML_OPTION_CASE_FOLDING, false );
		// Set XML parser callback functions
		xml_set_object( $this->_parser, $this );
		xml_set_element_handler( $this->_parser, 'tag_open', 'tag_close' );
		xml_set_character_data_handler( $this->_parser, 'cdata' );

		// 256Kb, parse in chunks to avoid the RAM usage on very large messages
		$xmlrpc_chunk_parsing_size = 262144;

		/**
		 * Filters the chunk size that can be used to parse an XML-RPC reponse message.
		 *
		 * @since 4.4.0
		 *
		 * @param int $xmlrpc_chunk_parsing_size Chunk size to parse in bytes.
		 */
		$chunk_size = apply_filters( 'xmlrpc_chunk_parsing_size', $xmlrpc_chunk_parsing_size );

		$final = false;
		do {
			if ( strlen( $this->message ) <= $chunk_size ) {
				$final = true;
			}
			$part = substr( $this->message, 0, $chunk_size );
			$this->message = substr( $this->message, $chunk_size );
			if ( ! xml_parse( $this->_parser, $part, $final ) ) {
				return false;
			}

			if ( $final ) {
				break;
			}
		} while ( true );
		xml_parser_free( $this->_parser );

		// Grab the error messages, if any
		if ( 'fault' === $this->messageType ) {
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}

	public function tag_open( $parser, $tag, $attr ) {
		$this->_currentTagContents = '';

		switch( $tag ) {
		case 'methodCall':
		case 'methodResponse':
		case 'fault':
			$this->messageType = $tag;
			break;

		/* Deal with stacks of arrays and structs */
		case 'data':    // data is to all intents and puposes more interesting than array
			$this->_arraystructstypes[] = 'array';
			$this->_arraystructs[] = [];
			break;

		case 'struct':
			$this->_arraystructstypes[] = 'struct';
			$this->_arraystructs[] = [];
			break;
		}
	}

	public function cdata( $parser, $cdata ) {
		$this->_currentTagContents .= $cdata;
	}

	public function tag_close( $parser, $tag ) {
		$value = null;
		$valueFlag = false;

		switch( $tag ) {
		case 'int':
		case 'i4':
			$value = (int) trim( $this->_currentTagContents );
			$valueFlag = true;
			break;

		case 'double':
			$value = (double) trim( $this->_currentTagContents );
			$valueFlag = true;
			break;

		case 'string':
			$value = (string) trim( $this->_currentTagContents );
			$valueFlag = true;
			break;

		case 'dateTime.iso8601':
			$value = new Date( trim( $this->_currentTagContents ) );
			$valueFlag = true;
			break;

		case 'value':
			// "If no type is indicated, the type is string."
			if ( trim( $this->_currentTagContents ) ) {
				$value = (string) $this->_currentTagContents;
				$valueFlag = true;
			}
			break;

		case 'boolean':
			$value = (boolean) trim( $this->_currentTagContents );
			$valueFlag = true;
			break;

		case 'base64':
			$value = base64_decode( $this->_currentTagContents );
			$valueFlag = true;
			break;

		/* Deal with stacks of arrays and structs */
		case 'data':
		case 'struct':
			$value = array_pop( $this->_arraystructs );
			array_pop( $this->_arraystructstypes );
			$valueFlag = true;
			break;

		case 'member':
			array_pop( $this->_currentStructName );
			break;

		case 'name':
			$this->_currentStructName[] = trim( $this->_currentTagContents );
			break;

		case 'methodName':
			$this->methodName = trim( $this->_currentTagContents );
			break;
		}

		if ( $valueFlag ) {
			$count = count( $this->_arraystructs );
			if ( $count > 0 ) {
				$index = $count - 1;
				$structIndex = count( $this->_currentStructName ) - 1;

				// Add value to struct or array
				if ( $this->_arraystructstypes[ $index ] == 'struct') {
					// Add to struct
					$this->_arraystructs[ $index ][ $this->_currentStructName[ $structIndex ] ] = $value;
				} else {
					// Add to array
					$this->_arraystructs[ $index ][] = $value;
				}
			} else {
				// Just add as a parameter
				$this->params[] = $value;
			}
		}
		$this->_currentTagContents = '';
	}
}
