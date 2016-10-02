<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Request {

    private $args;
    private $xml;

    public function __construct( string $method, array $args ) {
        $this->args = $args;
        $this->xml = "
<?xml version=\"1.0\"?>
<methodCall>
<methodName>{$method}</methodName>
<params>
";
        foreach ( $this->args as $arg ) {
            $this->xml .= '<param><value>';
            $v = new Value( $arg );
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }
        $this->xml .= '</params></methodCall>';
    }

    public function getLength(): int
    {
        return strlen( $this->xml );
    }

    public function getXml(): string
    {
        return $this->xml;
    }
}
