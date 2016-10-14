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

	// admin menu
	public $menu = [];
	public $submenu = [];
	public $_wp_menu_nopriv = [];
	public $_wp_submenu_nopriv = [];

	// this is the mechanism we will use to store entries
	// that were previously global variables
	// pray 4 me.
	private $globals = [];

	public function get( string $name ) {
		if ( array_key_exists( $name, $this->globals ) ) {
			return $this->globals[ $name ];
		}
	}

	public function set( string $name, $value = null ) {
		$this->globals[ $name ] = $value;
	}

	// wrap callables that produce output
	public function mute( callable $callback ) {
		return function () use ( $callback ) {
			ob_start();
			$return = call_user_func( $callback );
			$output = ob_get_clean();
			if ( $output ) {
				return $output;
			}

			return $return;
		};
	}
}
