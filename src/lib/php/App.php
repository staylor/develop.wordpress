<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $blog_id = 1;

	public $current_screen;

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

	public $theme = [
		'features' => [],
		'directories' => [],
		'default_headers' => [],
		'editor_styles' => [],
		'custom_image_header' => null,
		'custom_background' => null,
	];

	public $suspend_cache_invalidation;
	public $switched_stack = [];
	public $switched = false;
}
