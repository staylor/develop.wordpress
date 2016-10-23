<?php
namespace WP;

trait Globals {
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
}
