<?hh
/**
 * Loads the correct template based on the visitor's url
 *
 * PHP version 7
 *
 * @category Bootstrap/Load
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */
if (defined('WP_USE_THEMES') && WP_USE_THEMES) {
    /**
     * Fires before determining which template to load.
     *
     * @since 1.5.0
     */
    do_action('template_redirect');
}

/**
 * Filters whether to allow 'HEAD' requests to generate content.
 *
 * Provides a significant performance bump by exiting before the page
 * content loads for 'HEAD' requests. See #14348.
 *
 * @param bool $exit Whether to exit without generating any content
 *                   for 'HEAD' requests. Default true.
 *
 * @since 3.5.0
 */
if ('HEAD' === $app[ 'request.method' ]
    && apply_filters('exit_on_http_head', true)
) {
    return;
}

// Process feeds and trackbacks even if not using themes.
if (is_robots()) {
    /**
     * Fired when the template loader determines a robots.txt request.
     *
     * @since 2.1.0
     */
    do_action('do_robots');
    return;
} elseif (is_feed()) {
    do_feed();
    return;
} elseif (is_trackback()) {
    include ABSPATH . 'wp-trackback.hh';
    return;
}

if (!defined('WP_USE_THEMES') || !WP_USE_THEMES) {
    return;
}

$template = '';

$templates = [
    [ 'is_embed', 'get_embed_template' ],
    [ 'is_404', 'get_404_template' ],
    [ 'is_search', 'get_search_template' ],
    [ 'is_front_page', 'get_front_page_template' ],
    [ 'is_home', 'get_home_template' ],
    [ 'is_post_type_archive', 'get_post_type_archive_template' ],
    [ 'is_tax', 'get_taxonomy_template' ],
    [ 'is_attachment', 'get_attachment_template', function () {
        remove_filter('the_content', 'prepend_attachment');
    } ],
    [ 'is_single', 'get_single_template' ],
    [ 'is_page', 'get_page_template' ],
    [ 'is_singular', 'get_singular_template' ],
    [ 'is_category' , 'get_category_template' ],
    [ 'is_tag' , 'get_tag_template' ],
    [ 'is_author' , 'get_author_template' ],
    [ 'is_date' , 'get_date_template' ],
    [ 'is_archive' , 'get_archive_template' ],
];

while (empty($template) && count($templates) > 0) {
    list($check, $get_template, $func) = array_shift($templates);
    if ($check()) {
        $template = $get_template();
        if (is_callable($func)) {
            $func();
        }
    }
}

if (empty($template)) {
    $template = get_index_template();
}

$template = apply_filters('template_include', $template);
/**
 * Filters the path of the current template before including it.
 *
 * @param string $template The path of the template to include.
 *
 * @since 3.0.0
 */
if (!empty($template)) {
    include $template;
} elseif (current_user_can('switch_themes')) {
    $theme = wp_get_theme();
    if ($theme->errors()) {
        wp_die($theme->errors());
    }
}
