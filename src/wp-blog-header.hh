<?hh
/**
 * Loads the WordPress environment and template.
 *
 * PHP version 7
 *
 * @category Bootstrap/Load
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

// Load the WordPress library.
require_once __DIR__ . '/wp-load.hh';

// Set up the WordPress query.
wp();

// Load the theme template.
require_once ABSPATH . WPINC . '/template-loader.hh';
