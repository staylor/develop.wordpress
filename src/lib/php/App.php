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

	public $nav_menus = [
		'registered' => []
	];

	public $theme_features = [];
	public $theme_directories = [];
	public $default_headers = [];

	public $suspend_cache_invalidation;
}
