<?hh
/**
 * Handle Trackbacks and Pingbacks Sent to WordPress
 *
 * PHP version 7
 *
 * @category Trackbacks
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 * @since    0.71
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

if (empty($app)) {
    // load WordPress
    include_once __DIR__ . '/wp-load.hh';

    wp([ 'tb' => '1' ]);
}

$charset = $_post->get('charset', '');
if ($charset) {
    $charset = str_replace([ ',', ' ' ], '', strtoupper(trim($charset)));
} else {
    $charset = 'ASCII, UTF-8, ISO-8859-1, JIS, EUC-JP, SJIS';
}
// No valid uses for UTF-7.
if (false !== strpos($charset, 'UTF-7')) {
    exit();
}

/**
 * Response to a trackback.
 *
 * Responds with an error or success XML message.
 *
 * @param mixed  $error         Whether there was an error.
 *                              Default '0'. Accepts '0' or '1', true or false.
 * @param string $error_message Error message if an error occurred.
 *
 * @since 0.71
 */
$trackback_response = function ($error = 0, $error_message = '') use (
    $app,
    $response
) {
    $response->setCharset($app['charset']);
    $response->headers->set('Content-Type', 'text/xml');

    $xml = $app['mustache']->render(
        'trackback/response',
        [
            'error' => $error ? $error_message : ''
        ]
    );

    $response->setContent($xml);
    $response->send();
};

$tb_id = null;
if (! $_get->get('tb_id')) {
    $parts = explode('/', $app['request.uri']);
    $tb_id = (int) end($parts);
}

if (is_single() || is_page()) {
    $tb_id = (int) $posts[0]->ID;
}

if (! $tb_id ) {
    $trackback_response(1, __('I really need an ID for this to work.'));
    exit();
}

$tb_url = $_post->get('url', '');

// These three are stripslashed here so they
// can be properly escaped after mb_convert_encoding().
$_title = wp_unslash($_post->get('title', ''));
$_excerpt = wp_unslash($_post->get('excerpt', ''));
$_blog_name = wp_unslash($_post->get('blog_name', ''));

// For international trackbacks.
if (function_exists('mb_convert_encoding') ) {
    $_title = mb_convert_encoding($_title, $app['charset'], $charset);
    $_excerpt = mb_convert_encoding($_excerpt, $app['charset'], $charset);
    $_blog_name = mb_convert_encoding($_blog_name, $app['charset'], $charset);
}

// Now that mb_convert_encoding() has been given a swing,
// we need to escape these three.
$title = wp_slash($_title);
$excerpt = wp_slash($_excerpt);
$blog_name = wp_slash($_blog_name);

if (empty($title) && empty($tb_url) && empty($blog_name)) {
    // If it doesn't look like a trackback at all.
    $location = get_permalink($tb_id);
    RedirectResponse::create($location)->send();
    exit();
}

if (!empty($tb_url) && !empty($title)) {
    /**
     * Fires before the trackback is added to a post.
     *
     * @param int    $tb_id     Post ID related to the trackback.
     * @param string $tb_url    Trackback URL.
     * @param string $charset   Character Set.
     * @param string $title     Trackback Title.
     * @param string $excerpt   Trackback Excerpt.
     * @param string $blog_name Blog Name.
     *
     * @since 4.7.0
     */
    do_action(
        'pre_trackback_post',
        $tb_id,
        $tb_url,
        $charset,
        $title,
        $excerpt,
        $blog_name
    );

    if (!pings_open($tb_id)) {
        $trackback_response(1, __('Sorry, trackbacks are closed for this item.'));
        exit();
    }

    $wpdb = $app['db'];
    $sql = 'SELECT * FROM ' . $wpdb->comments .
        ' WHERE comment_post_ID = %d AND comment_author_url = %s';
    $dupe = $wpdb->get_results($wpdb->prepare($sql, $tb_id, $tb_url));
    if ($dupe) {
        $trackback_response(
            1,
            __('We already have a ping from that URL for this post.')
        );
        exit();
    }

    $commentdata = [
        'comment_post_ID' => $tb_id,
        'comment_author' => $blog_name,
        'comment_author_email' => '',
        'comment_author_url' => $tb_url,
        'comment_content' => sprintf(
            "<strong>%s</strong>\n\n%s",
            wp_html_excerpt($title, 250, '&#8230;'),
            wp_html_excerpt($excerpt, 252, '&#8230;')
        ),
        'comment_type' => 'trackback',
    ];

    $trackback_id = wp_new_comment($commentdata);

    /**
     * Fires after a trackback is added to a post.
     *
     * @param int $trackback_id Trackback ID.
     *
     * @since 1.2.0
     */
    do_action('trackback_post', $trackback_id);
    $trackback_response(0);
    exit();
}
