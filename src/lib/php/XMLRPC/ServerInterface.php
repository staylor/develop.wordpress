<?php
namespace WP\XMLRPC;

interface ServerInterface {

	/**
	 * @return void
	 */
	public function serve_request();
}