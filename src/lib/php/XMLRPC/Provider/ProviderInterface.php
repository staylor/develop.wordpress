<?php
namespace WP\XMLRPC\Provider;

use WP\XMLRPC\Server;

interface ProviderInterface {
	public function register( Server $server ): ProviderInterface;
}
