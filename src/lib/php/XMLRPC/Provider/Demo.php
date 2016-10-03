<?php
namespace WP\XMLRPC\Provider;

use WP\XMLRPC\Server;

class Demo implements ProviderInterface {
	public function register( Server $server ): ProviderInterface
	{
		$server->addMethods( [
			'demo.sayHello' => [ $this, 'sayHello' ],
			'demo.addTwoNumbers' => [ $this, 'addTwoNumbers' ]
		] );

		return $this;
	}

	/**
	 * Test XMLRPC API by saying, "Hello!" to client.
	 *
	 * @since 1.5.0
	 *
	 * @return string Hello string response.
	 */
	public function sayHello() {
		return 'Hello!';
	}

	/**
	 * Test XMLRPC API by adding two numbers for client.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args {
	 *     Method arguments. Note: arguments must be ordered as documented.
	 *
	 *     @type integer $number1 A number to add.
	 *     @type integer $number2 A second number to add.
	 * }
	 * @return int Sum of the two given numbers.
	 */
	public function addTwoNumbers( $args ) {
		$number1 = $args[0];
		$number2 = $args[1];
		return $number1 + $number2;
	}
}