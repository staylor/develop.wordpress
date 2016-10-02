<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Base64 {

    private $data;

    public function __construct( string $data ) {
        $this->data = $data;
    }

    public function getXml(): string
    {
        return '<base64>' . base64_encode( $this->data ) . '</base64>';
    }
}
