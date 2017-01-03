<?hh
/**
 * WordPress Cron Implementation for hosts, which do not offer CRON or for which
 * the user has not set up a CRON job pointing to this file.
 *
 * The HTTP request to this file will not slow down the visitor who happens to
 * visit when the cron job is needed to run.
 *
 * PHP version 7
 *
 * @category Cron
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = WP\getApp();
$_post = $app['request']->request;

ignore_user_abort(true);

if (!empty($_post->all()) || defined('DOING_AJAX') || defined('DOING_CRON')) {
    die();
}

/**
 * Tell WordPress we are doing the CRON task.
 *
 * @var bool
 */
const DOING_CRON = true;

if (!defined('ABSPATH')) {
    // Set up WordPress environment
    include_once __DIR__ . '/wp-load.hh';
}

if (false === $crons = _get_cron_array()) {
    die();
}

$keys = array_keys($crons);
$gmt_time = microtime(true);

if (isset($keys[0]) && $keys[0] > $gmt_time) {
    die();
}


// The cron lock: a unix timestamp from when the cron was spawned.
$doing_cron_transient = get_transient('doing_cron');

// Use global $doing_wp_cron lock otherwise use the GET lock.
// If no lock, trying grabbing a new lock.
if (empty($doing_wp_cron)) {
    if (empty($_get->get('doing_wp_cron'))) {
        // Called from external script/job. Try setting a lock.
        if ($doing_cron_transient
            && ($doing_cron_transient + WP_CRON_LOCK_TIMEOUT > $gmt_time)
        ) {
            return;
        }
        $doing_cron_transient = $doing_wp_cron = sprintf('%.22F', microtime(true));
        set_transient('doing_cron', $doing_wp_cron);
    } else {
        $doing_wp_cron = $_get->get('doing_wp_cron');
    }
}

/*
 * The cron lock (a unix timestamp set when the cron was spawned),
 * must match $doing_wp_cron (the "key").
 */
if ($doing_cron_transient !== $doing_wp_cron) {
    return;
}

foreach ($crons as $timestamp => $cronhooks) {
    if ($timestamp > $gmt_time) {
        break;
    }

    foreach ($cronhooks as $hook => $keys) {

        foreach ($keys as $k => $v) {

            $schedule = $v['schedule'];

            if ($schedule != false) {
                $new_args = array($timestamp, $schedule, $hook, $v['args']);
                call_user_func_array('wp_reschedule_event', $new_args);
            }

            wp_unschedule_event($timestamp, $hook, $v['args']);

            /**
             * Fires scheduled events.
             *
             * @param string $hook Name of the hook that was scheduled to be fired.
             * @param array  $args The arguments to be passed to the hook.
             *
             * @since 2.1.0
             */
             do_action_ref_array($hook, $v['args']);

            // If the hook ran too long and another cron process
            // stole the lock, quit.
            if (_get_cron_lock() !== $doing_wp_cron) {
                return;
            }
        }
    }
}

if (_get_cron_lock() === $doing_wp_cron) {
    delete_transient('doing_cron');
}

die();
