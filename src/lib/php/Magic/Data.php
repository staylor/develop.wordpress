<?php
namespace WP\Magic;

trait Data {
	protected $data = [];

	public function __set( string $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __unset( string $name ) {
		unset( $this->data[ $name ] );
	}

	public function __isset( string $name ): bool
	{
		return array_key_exists( $name, $this->data );
	}

	/**
	 * @return mixed
	 */
	public function __get( string $name ) {
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