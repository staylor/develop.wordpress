<?php
namespace WP;

use Pimple\Container;

class App extends Container {
	public $blog_id = 1;

	public $l10n = [];
	public $l10n_unloaded = [];

	public $current_screen;

	public $taxonomies = [];
	public $meta_boxes = [];
	public $meta_keys = [];
	public $post_statuses = [];
	public $shortcode_tags = [];
	public $importers = [];

	public $wpsmiliestrans = [];
	public $wp_smiliessearch;

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
		'placeholder' => -1,
		'selected_id' => 0,
	];

	public $theme = [
		'features' => [],
		'directories' => [],
		'default_headers' => [],
		'editor_styles' => [],
		'custom_image_header' => null,
		'custom_background' => null,
		'allowedtags' => [
			'a' => [ 'href' => [], 'title' => [], 'target' => [] ],
			'abbr' => [ 'title' => [] ], 'acronym' => ['title' => [] ],
			'code' => [], 'pre' => [], 'em' => [], 'strong' => [],
			'div' => [], 'p' => [], 'ul' => [], 'ol' => [], 'li' => [],
			'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
			'img' => ['src' => [], 'class' => [], 'alt' => [] ]
		],
		'field_defaults' => [
			'description' => true, 'sections' => false, 'tested' => true, 'requires' => true,
			'rating' => true, 'downloaded' => true, 'downloadlink' => true, 'last_updated' => true,
			'homepage' => true, 'tags' => true, 'num_ratings' => true
		],
	];

	public $show_admin_bar;

	public $switched_stack = [];
	public $switched = false;

	// admin menu
	public $menu = [];
	public $submenu = [];
	public $_wp_menu_nopriv = [];
	public $_wp_submenu_nopriv = [];

	// dashboard
	public $dashboard = [
		'control_callbacks' => [],
	];

	public $files = [
		'descriptions' => [],
		'allowed' => [],
	];

	public $_wp_admin_css_colors = [];

	// this is the mechanism we will use to store entries
	// that were previously global variables
	// pray 4 me.
	private $globals = [];

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get( string $name ) {
		if ( array_key_exists( $name, $this->globals ) ) {
			return $this->globals[ $name ];
		}
	}

	public function set( string $name, $value = null ) {
		$this->globals[ $name ] = $value;
	}

	public function remove( string $name ) {
		unset( $this->globals[ $name ] );
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
