<?php
namespace WP\Template;

use Pimple\{Container,ServiceProviderInterface};

class Provider implements ServiceProviderInterface {
	public function register( Container $app ) {
		$app['mustache'] = function () {
			new Mustache();
		};
	}
}