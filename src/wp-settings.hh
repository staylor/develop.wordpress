<?hh
/**
 * WordPress Bootstrap
 *
 * Used to set up and fix common variables and include the WordPress procedural
 * and class library. Allows for some configuration in
 * wp-config.php (see default-constants.php). Stores the location of the
 * WordPress directory of functions, classes, and core content.
 *
 * PHP version 7
 *
 * @category Bootstrap/Load
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 * @since    1.0.0
 */
require_once ABSPATH . 'vendor/autoload.php';

const WPINC = 'wp-includes';

$app = WP\getApp();
// Response singleton
$response = $app['response'];

// use instead of superglobals
$_request = $app['request']->attributes;
$_post = $app['request']->request;
$_get = $app['request']->query;
$_cookie = $app['request']->cookies;
$_server = $app['request']->server;
$_files = $app['request']->files;

// Set initial default constants including WP_MEMORY_LIMIT, WP_MAX_MEMORY_LIMIT,
// WP_DEBUG, SCRIPT_DEBUG, WP_CONTENT_DIR and WP_CACHE.
wp_initial_constants();

// Check for the required PHP version and for the
// MySQL extension or a database drop-in.
wp_check_php_mysql_versions();

// Disable magic quotes at runtime. Magic quotes are added
// using wpdb later in wp-settings.php.
ini_set('magic_quotes_runtime', 0);
ini_set('magic_quotes_sybase',  0);

// WordPress calculates offsets from UTC.
date_default_timezone_set('UTC');

// Check if we have received a request due to missing favicon.ico
wp_favicon_request();

// Check if we're in maintenance mode.
wp_maintenance();

// Start loading timer.
timer_start();

// Check if we're in WP_DEBUG mode.
wp_debug_mode();

/**
 * Filters whether to enable loading of the advanced-cache.php drop-in.
 *
 * This filter runs before it can be used by plugins. It is designed for non-web
 * run-times. If false is returned, advanced-cache.php will never be loaded.
 *
 * @param bool $enable_advanced_cache Whether to enable loading
 *                                    advanced-cache.php (if present).
 *                                    Default true.
 *
 * @since 4.6.0
 */
if (WP_CACHE && apply_filters('enable_loading_advanced_cache_dropin', true)) {
    // For an advanced caching plugin to use.
    // Uses a static drop-in because you would only want one.
    if (WP_DEBUG) {
        include WP_CONTENT_DIR . '/advanced-cache.php';
    } else {
        @include WP_CONTENT_DIR . '/advanced-cache.php';
    }

    // Re-initialize any hooks added manually by advanced-cache.php
    if ($wp_filter) {
        $wp_filter = WP_Hook::build_preinitialized_hooks($wp_filter);
    }
}

// Define WP_LANG_DIR if not set.
wp_set_lang_dir();

// Load early WordPress files.
require ABSPATH . WPINC . '/compat.php';
require ABSPATH . WPINC . '/functions.php';

// Include the wpdb class and, if present, a db.php database drop-in.
require_wp_db();

wp_set_wpdb_vars();

// Start the WordPress object cache, or an external object cache
// if the drop-in is present.
wp_start_object_cache();

// Attach the default filters.
require ABSPATH . WPINC . '/default-filters.php';

// Initialize multisite if enabled.
if (is_multisite()) {
    include ABSPATH . WPINC . '/ms-blogs.php';
    include ABSPATH . WPINC . '/ms-settings.php';
} elseif (! defined('MULTISITE')) {
    define('MULTISITE', false);
}

register_shutdown_function('shutdown_action_hook');

// Stop most of WordPress from being loaded if we just want the basics.
if (SHORTINIT) {
    return false;
}

// Load the L10n library.
require_once ABSPATH . WPINC . '/l10n.php';

// Run the installer if WordPress is not installed.
wp_not_installed();

// Load most of WordPress.
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/capabilities.php';
require ABSPATH . WPINC . '/query.php';
require ABSPATH . WPINC . '/theme.php';
require ABSPATH . WPINC . '/template.php';
require ABSPATH . WPINC . '/user.php';
require ABSPATH . WPINC . '/meta.php';
require ABSPATH . WPINC . '/general-template.php';
require ABSPATH . WPINC . '/link-template.php';
require ABSPATH . WPINC . '/author-template.php';
require ABSPATH . WPINC . '/post.php';
require ABSPATH . WPINC . '/post-template.php';
require ABSPATH . WPINC . '/revision.php';
require ABSPATH . WPINC . '/post-formats.php';
require ABSPATH . WPINC . '/post-thumbnail-template.php';
require ABSPATH . WPINC . '/category.php';
require ABSPATH . WPINC . '/category-template.php';
require ABSPATH . WPINC . '/comment.php';
require ABSPATH . WPINC . '/comment-template.php';
require ABSPATH . WPINC . '/rewrite.php';
require ABSPATH . WPINC . '/feed.php';
require ABSPATH . WPINC . '/bookmark.php';
require ABSPATH . WPINC . '/bookmark-template.php';
require ABSPATH . WPINC . '/kses.php';
require ABSPATH . WPINC . '/cron.php';
require ABSPATH . WPINC . '/script-loader.php';
require ABSPATH . WPINC . '/taxonomy.php';
require ABSPATH . WPINC . '/update.php';
require ABSPATH . WPINC . '/canonical.php';
require ABSPATH . WPINC . '/shortcodes.php';
require ABSPATH . WPINC . '/embed.php';
require ABSPATH . WPINC . '/media.php';
require ABSPATH . WPINC . '/http.php';
require ABSPATH . WPINC . '/widgets.php';
require ABSPATH . WPINC . '/nav-menu.php';
require ABSPATH . WPINC . '/nav-menu-template.php';
require ABSPATH . WPINC . '/admin-bar.php';
require ABSPATH . WPINC . '/rest-api.php';

$GLOBALS['wp_embed'] = new WP_Embed();

// Load multisite-specific files.
if (is_multisite()) {
    include ABSPATH . WPINC . '/ms-functions.php';
    include ABSPATH . WPINC . '/ms-default-filters.php';
}

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden
// in the sunrise.php drop-in.
wp_plugin_directory_constants();

$GLOBALS['wp_plugin_paths'] = [];

// Load must-use plugins.
foreach ( wp_get_mu_plugins() as $mu_plugin) {
    include_once $mu_plugin ;
}
unset($mu_plugin);

// Load network activated plugins.
if (is_multisite()) {
    foreach ( wp_get_active_network_plugins() as $network_plugin) {
        wp_register_plugin_realpath($network_plugin);
        include_once $network_plugin;
    }
    unset($network_plugin);
}

/**
 * Fires once all must-use and network-activated plugins have loaded.
 *
 * @since 2.8.0
 */
do_action('muplugins_loaded');

if (is_multisite()) {
    ms_cookie_constants();
}

// Define constants after multisite is loaded.
wp_cookie_constants();

// Define and enforce our SSL constants
wp_ssl_constants();

// Create common globals.
require ABSPATH . WPINC . '/vars.php';

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
create_initial_taxonomies();
create_initial_post_types();

// Register the default theme directory root
register_theme_directory(get_theme_root());

// Load active plugins.
foreach ( wp_get_active_and_valid_plugins() as $plugin) {
    wp_register_plugin_realpath($plugin);
    include_once $plugin;
}
unset($plugin);

// Load pluggable functions.
require ABSPATH . WPINC . '/pluggable.php';

// Set internal encoding.
wp_set_internal_encoding();

// Run wp_cache_postload() if object cache is enabled and the function exists.
if (WP_CACHE && function_exists('wp_cache_postload')) {
    wp_cache_postload();
}

/**
 * Fires once activated plugins have loaded.
 *
 * Pluggable functions are also available at this point in the loading order.
 *
 * @since 1.5.0
 */
do_action('plugins_loaded');

// Define constants which affect functionality if not already defined.
wp_functionality_constants();

// Add magic quotes and set up $_REQUEST ($_GET + $_POST )
wp_magic_quotes();

/**
 * Fires when comment cookies are sanitized.
 *
 * @since 2.0.11
 */
do_action('sanitize_comment_cookies');

$wp_rewrite = $app['rewrite'];
$wp_rewrite->attach($app['wp']);

/**
 * Fires before the theme is loaded.
 *
 * @since 2.6.0
 */
do_action('setup_theme');

// Define the template related constants.
wp_templating_constants();

// Load the default text localization domain.
load_default_textdomain();

$locale = get_locale();
$locale_file = WP_LANG_DIR . '/' . $locale . '.php';
if ((0 === validate_file($locale)) && is_readable($locale_file)) {
    include $locale_file;
}
unset($locale_file);

// Load the functions for the active theme,
// for both parent and child theme if applicable.
if (! wp_installing() || 'wp-activate.php' === $app['pagenow']) {
    if (TEMPLATEPATH !== STYLESHEETPATH
        && file_exists(STYLESHEETPATH . '/functions.php')
    ) {
        include STYLESHEETPATH . '/functions.php';
    }
    if (file_exists(TEMPLATEPATH . '/functions.php')) {
        include TEMPLATEPATH . '/functions.php';
    }
}

/**
 * Fires after the theme is loaded.
 *
 * @since 3.0.0
 */
do_action('after_setup_theme');

// Set up current user.
$app['wp']->init();

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Most of WP is loaded at this stage, and the user is authenticated. WP continues
 * to load on the {@see 'init'} hook that follows (e.g. widgets),
 * and many plugins instantiate themselves on it for all sorts of reasons
 * (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once WP is loaded,
 * use the {@see 'wp_loaded'} hook below.
 *
 * @since 1.5.0
 */
do_action('init');

// Check site status
if (is_multisite()) {
    $file = ms_site_check();
    if (true !== $file) {
        include $file;
        exit();
    }
    unset($file);
}

/**
 * This hook is fired once WP, all plugins, and the theme are
 * fully loaded and instantiated.
 *
 * Ajax requests should use wp-admin/admin-ajax.php. admin-ajax.php can
 * handle requests for users not logged in.
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 *
 * @since 3.0.0
 */
do_action('wp_loaded');