<?php
namespace WP;

function getApp( App $app = null ): App
{
	static $store = null;
	if ( $app ) {
		$store = $app;
	}

	if ( ! $store ) {
		$store = new App();
		$store->register( new Provider() );
		$store->register( new Template\Provider() );
		$store->register( new Symfony\Provider() );
		$store->register( new Dependency\Provider() );
	}

	return $store;
}

function render(): string
{
	static $mustache = null;
	if (null === $mustache) {
		$mustache = new Template\Mustache();
	}
	return call_user_func_array([$mustache, 'render'], func_get_args());
}
