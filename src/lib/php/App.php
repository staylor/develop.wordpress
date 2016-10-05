<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $post_types = [];
	public $taxonomies = [];

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
