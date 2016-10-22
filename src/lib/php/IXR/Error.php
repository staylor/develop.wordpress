<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Error {

	public $code;
	public $message;

	public function __construct( int $code, string $message ) {
		$this->code = $code;
		$this->message = htmlspecialchars( $message );
	}

	public function getXml(): string
	{
		return '
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>' . $this->code . '</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>' . $this->message . '</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>
';
	}
}
