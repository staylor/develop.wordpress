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

if (is_embed()) {
    $template = get_embed_template();
}

if (empty($template) && is_404()) {
    $template = get_404_template();
}

if (empty($template) && is_search()) {
    $template = get_search_template();
}

if (empty($template) && is_front_page()) {
    $template = get_front_page_template();
}

if (empty($template) && is_home()) {
    $template = get_home_template();
}

if (empty($template) && is_post_type_archive()) {
    $template = get_post_type_archive_template();
}

if (empty($template) && is_tax()) {
    $template = get_taxonomy_template();
}

if (empty($template) && is_attachment()) {
    $template = get_attachment_template();
    remove_filter('the_content', 'prepend_attachment');
}

if (empty($template) && is_single()) {
    $template = get_single_template();
}

if (empty($template) && is_page()) {
    $template = get_page_template();
}

if (empty($template) && is_singular()) {
    $template = get_singular_template();
}

if (empty($template) && is_category()) {
    $template = get_category_template();
}

if (empty($template) && is_tag()) {
    $template = get_tag_template();
}

if (empty($template) && is_author()) {
    $template = get_author_template();
}

if (empty($template) && is_date()) {
    $template = get_date_template();
}

if (empty($template) && is_archive()) {
    $template = get_archive_template();
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
