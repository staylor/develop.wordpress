<?php
namespace WP;

function getApp( App $app = null ) {
	static $store = null;
	if ( $app ) {
		$store = $app;
	}

	if ( ! $store ) {
		$store = new App();
	}

	return $store;
}