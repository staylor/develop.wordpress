<?php
namespace WP;

function getApp( App $app = null ) {
	static $store = null;
	if ( $app ) {
		$store = $app;
	}

	if ( ! $store ) {
		$store = new App();
		$store->register( new Provider() );
		$store->register( new Symfony\Provider() );
	}

	return $store;
}