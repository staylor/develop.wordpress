<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	use Globals;

	// this is the mechanism we will use to store entries
	// that were previously global variables
	// pray 4 me.
	private $globals = [];

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get( string $name ) {
		if ( array_key_exists( $name, $this->globals ) ) {
			return $this->globals[ $name ];
		}
	}

	public function set( string $name, $value = null ) {
		$this->globals[ $name ] = $value;
	}

	public function remove( string $name ) {
		unset( $this->globals[ $name ] );
	}

	// wrap callables that produce output
	public function mute( callable $callback ) {
		return function () use ( $callback ) {
			ob_start();
			$return = call_user_func( $callback );
			$output = ob_get_clean();
			if ( $output ) {
				return $output;
			}

			return $return;
		};
	}
}
