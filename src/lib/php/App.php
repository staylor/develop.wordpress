<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $taxonomies = [];

	public $shortcode_tags = [];

	public $widgets = [
		'registered' => [],
		'controls' => [],
		'updates' => [],
	];

	public $sidebars = [
		'registered' => [],
		'widgets' => [],
		'_widgets' => [],
	];
}
