<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the wp-config.php file. The wp-config.php
 * file will then load the wp-settings.hh file, which
 * will then set up the WordPress environment.
 *
 * If the wp-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * wp-config.php file.
 *
 * Will also search for wp-config.php in WordPress' parent
 * directory to allow the WordPress directory to remain
 * untouched.
 *
 * PHP version 7
 *
 * @category Bootstrap/Load
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

require_once __DIR__ . '/vendor/autoload.php';

use function WP\getApp;

// Define ABSPATH as this file's directory
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

error_reporting(
    E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING
    | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR
);

/*
 * If wp-config.php exists in the WordPress root, or if it exists in the
 * root and wp-settings.hh doesn't, load wp-config.php. The secondary check
 * for wp-settings.hh has the added benefit of avoiding cases where the
 * current directory is a nested installation, e.g. / is WordPress(a)
 * and /blog/ is WordPress(b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 */
if (file_exists(ABSPATH . 'wp-config.php')) {

    // The config file resides in ABSPATH
    include_once ABSPATH . 'wp-config.php';

} elseif (@file_exists(dirname(ABSPATH) . '/wp-config.php')
    && ! @file_exists(dirname(ABSPATH) . '/wp-settings.hh')
) {

    // The config file resides one level above ABSPATH but
    // is not part of another install
    include_once dirname(ABSPATH) . '/wp-config.php';

} else {

    // A config file doesn't exist

    define('WPINC', 'wp-includes');

    include_once ABSPATH . WPINC . '/load.php';

    include_once ABSPATH . WPINC . '/functions.php';

    $path = wp_guess_url() . '/wp-admin/setup-config.php';

    $app = getApp();
    /*
     * We're going to redirect to setup-config.php. While this shouldn't result
     * in an infinite loop, that's a silly thing to assume, don't you think? If
     * we're traveling in circles, our last-ditch effort is "Need more help?"
     */
    if (false === strpos($app['request.uri'], 'setup-config')) {
        header('Location: ' . $path);
        exit;
    }

    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
    include_once ABSPATH . WPINC . '/version.php';

    wp_check_php_mysql_versions();
    wp_load_translations_early();

    // Die with an error message
    $die = sprintf(
        /* translators: %s: wp-config.php */
        __("There doesn't seem to be a %s file. I need this before we can get started."),// @codingStandardsIgnoreLine
        '<code>wp-config.php</code>'
    ) . '</p>';
    $die .= '<p>' . sprintf(
        /* translators: %s: Codex URL */
        __("Need more help? <a href='%s'>We got it</a>."),
        __('https://codex.wordpress.org/Editing_wp-config.php')
    ) . '</p>';
    $die .= '<p>' . sprintf(
        /* translators: %s: wp-config.php */
        __("You can create a %s file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file."),// @codingStandardsIgnoreLine
        '<code>wp-config.php</code>'
    ) . '</p>';
    $die .= '<p><a href="' . $path . '" class="button button-large">' .
        __('Create a Configuration File') . '</a>';

    wp_die($die, __('WordPress &rsaquo; Error'));
}
