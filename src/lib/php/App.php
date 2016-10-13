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

	public $nav_menus = [
		'max_depth' => 0,
	];

	public $theme = [
		'custom_image_header' => null,
		'custom_background' => null,
	];

	public $show_admin_bar;

	public $suspend_cache_invalidation;
	// switch to blog
	public $switched_stack = [];
	public $switched = false;

	public $xmlrpc = [
		'post_default_title' => '',
	];

	// admin
	public $title;

	// admin menu
	public $menu = [];
	public $submenu = [];
	public $_wp_menu_nopriv = [];
	public $_wp_submenu_nopriv = [];
	public $plugin_page;
	public $parent_file;
	public $submenu_file;

	private $globals = [];

	public function mute( callable $callback ) {
		return function () use ( $callback ) {
			ob_start();
			call_user_func( $callback );
			return ob_get_clean();
		};
	}

	public function __construct( array $values = [] ) {
		parent::__construct( $values );

		// known global arrays that need to maintain reference to only one instance
		$arrays = [
			// widgets
			'registered_widgets',
			'widget_controls',
			'widget_updates',
			'deprecated_widget_callbacks',

			// sidebars
			'registered_sidebars',
			'sidebar_widgets',
			'_sidebar_widgets',

			// theme
			'theme_features',
			'theme_directories',
			'theme_default_headers',
			'theme_editor_styles',

			// nav menus
			'registered_nav_menus',
		];

		foreach ( $arrays as $name ) {
			$this->set( $name, new \ArrayObject() );
		}
	}

	public function get( string $name ) {
		if ( array_key_exists( $name, $this->globals ) ) {
			return $this->globals[ $name ];
		}
	}

	public function set( string $name, $value ) {
		$this->globals[ $name ] = $value;
	}
}
