<?php
namespace WP;
/**
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_get
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_post
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_request
 * @property-read Symfony\Component\HttpFoundation\ParameterBag $_server
 */
trait MagicRequest {
	public function __get( string $name ) {
		$app = getApp();

		switch ( $name ) {
		case '_get':
			return $app['request']->query;

		case '_post':
			return $app['request']->request;

		case '_request':
			return $app['request']->attributes;

		case '_server':
			return $app['request']->server;
		}
	}
}
