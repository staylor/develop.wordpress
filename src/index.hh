<?hh
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * PHP version 7
 *
 * @category Bootstrap/Load
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
const WP_USE_THEMES = true;

// Loads the WordPress Environment and Template
require __DIR__ . '/wp-blog-header.hh';
