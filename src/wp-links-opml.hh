<?hh
/**
 * Outputs the OPML XML format for getting the links defined in the link
 * administration. This can be used to export links from one blog over to
 * another. Links aren't exported by the WordPress export, so this file handles
 * that.
 *
 * This file is not added by default to WordPress theme pages when outputting
 * feed links. It will have to be added manually for browsers and users to pick
 * up that this file exists.
 *
 * PHP version 7
 *
 * @category OPML
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

use WP\View;

require_once __DIR__ . '/wp-load.hh';

$link_cat = '';
if (!empty($_get->get('link_cat'))) {
    $link_cat = $_get->get('link_cat');
    if (!in_array($link_cat, [ 'all', '0' ])) {
        $link_cat = absint(urldecode($link_cat));
    }
}

$args = [ 'taxonomy' => 'link_category', 'hierarchical' => 0 ];
if (!empty($link_cat)) {
    $args['include'] = $link_cat;
}

$cats = get_categories($args);
$categories = [];
foreach ($cats as $cat) {
    $category = [];
    /**
     * Filters the OPML outline link category name.
     *
     * @param string $catname The OPML outline category name.
     *
     * @since 2.2.0
     */
    $category['name'] = apply_filters('link_category', $cat->name);
    $category['bookmarks'] = [];
    $bookmarks = get_bookmarks([ 'category' => $cat->term_id ]);
    foreach ($bookmarks as $bookmark) {
        $data = [ 'updated' => '' ];
        /**
         * Filters the OPML outline link title text.
         *
         * @param string $title The OPML outline title text.
         *
         * @since 2.2.0
         */
        $data['title'] = apply_filters('link_title', $bookmark->link_name);
        $data['rss'] = $bookmark->link_rss;
        $data['url'] = $bookmark->link_url;
        if ('0000-00-00 00:00:00' !== $bookmark->link_updated) {
            $data['updated'] = $bookmark->link_updated;
        }
        $category['bookmarks'][] = $data;
    }
    $categories[] = $category;
}

$response->setCharset($app['charset']);
$response->headers->set('Content-Type', 'text/xml', true);

$view = new View($app);

$view->setData(
    [
        /* translators: 1: Site name */
        'title' => sprintf(
            __('Links for %s'),
            get_bloginfo('name', 'display')
        ),
        'dateCreated' => gmdate("D, d M Y H:i:s"),
        'categories' => $categories,
        'charset' => $app['charset'],
    ]
);

$view->setActions(
    [
        /**
         * Fires in the OPML header.
         *
         * @since 3.0.0
         */
        'opml_head' => [],
    ]
);

$xml = $view->render('opml/links');
$response->setContent($xml);
$response->send();
