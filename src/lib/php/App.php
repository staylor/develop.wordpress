<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $blog_id = 1;

	public $l10n = [];
	public $l10n_unloaded = [];

	public $current_screen;

	public $taxonomies = [];

	public $post_statuses = [];

	public $shortcode_tags = [];

	public $widgets = [
		'registered' => [],
		'controls' => [],
		'updates' => [],
		'deprecated_callbacks' => [],
	];

	public $sidebars = [
		'registered' => [],
		'widgets' => [],
		'_widgets' => [],
	];

	public $nav_menus = [
		'registered' => [],
		'max_depth' => 0,
	];

	public $theme = [
		'features' => [],
		'directories' => [],
		'default_headers' => [],
		'editor_styles' => [],
		'custom_image_header' => null,
		'custom_background' => null,
	];

	public $show_admin_bar;

	public $suspend_cache_invalidation;
	public $switched_stack = [];
	public $switched = false;

	public $xmlrpc = [
		'post_default_title' => '',
	];

	public function mute( callable $callback ) {
		return function () use ( $callback ) {
			ob_start();
			call_user_func( $callback );
			return ob_get_clean();
		};
	}
}
