<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $widgets = [
		'registered' => [],
		'controls' => [],
		'updates' => [],
	];

	public $sidebars = [
		'registered' => [],
		'widgets' => [],
	];
}
