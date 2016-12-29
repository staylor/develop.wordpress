<?php
namespace WP\Magic;

trait Data {
	protected $data = [];

	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __unset( $name ) {
		unset( $this->data[ $name ] );
	}

	public function __isset( $name )
	{
		return array_key_exists( $name, $this->data );
	}

	/**
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	public function setData( $data = [] ) {
		$this->data = array_merge( $this->data, $data );
	}
}
