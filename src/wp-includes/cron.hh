<?hh
/**
 * WordPress Cron API
 *
 * PHP version 7
 *
 * @category Cron
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

use function WP\getApp;

/**
 * Schedules an event to run only once.
 *
 * Schedules an event which will execute once by the WordPress actions core at
 * a time which you specify. The action will fire off when someone visits your
 * WordPress site, if the schedule time has passed.
 *
 * Note that scheduling an event to occur within 10 minutes of an existing event
 * with the same action hook will be ignored unless you pass unique `$args` values
 * for each scheduled event.
 *
 * @param int    $timestamp Unix timestamp (UTC) for when to run the event.
 * @param string $hook      Action hook to execute when event is run.
 * @param array  $args      Optional. Arguments to pass to the hook's
 *                          callback function.
 *
 * @since 2.1.0
 * @link  https://codex.wordpress.org/Function_Reference/wp_schedule_single_event
 *
 * @return false|void False if the event does not get scheduled.
 */
function wp_schedule_single_event($timestamp, $hook, $args = []) // @codingStandardsIgnoreLine
{
    // Make sure timestamp is a positive integer
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        return false;
    }

    // Don't schedule a duplicate if there's already an identical
    // event due within 10 minutes of it
    $next = wp_next_scheduled($hook, $args);
    if ($next && abs($next - $timestamp) <= 10 * MINUTE_IN_SECONDS) {
        return false;
    }

    $crons = _get_cron_array();
    $_event = (object) [
        'hook' => $hook,
        'timestamp' => $timestamp,
        'schedule' => false,
        'args' => $args
    ];
    /**
     * Filters a single event before it is scheduled.
     *
     * @param stdClass $event {
     *     An object containing an event's data.
     *
     *     @type string       $hook      Action hook to execute when
     *                                   event is run.
     *     @type int          $timestamp Unix timestamp (UTC) for when to
     *                                   run the event.
     *     @type string|false $schedule  How often the event should recur.
     *                                   See `wp_get_schedules()`.
     *     @type array        $args      Arguments to pass to the hook's
     *                                   callback function.
     * }
     *
     * @since 3.1.0
     */
    $event = apply_filters('schedule_event', $_event);

    // A plugin disallowed this event
    if (!$event) {
        return false;
    }

    $key = md5(serialize($event->args));

    $crons[ $event->timestamp ][ $event->hook ][ $key ] = [
        'schedule' => $event->schedule,
        'args' => $event->args
    ];
    uksort($crons, 'strnatcasecmp');
    _set_cron_array($crons);
}

/**
 * Schedule a recurring event.
 *
 * Schedules a hook which will be executed by the WordPress actions core on a
 * specific interval, specified by you. The action will trigger when someone
 * visits your WordPress site, if the scheduled time has passed.
 *
 * Valid values for the recurrence are hourly, daily, and twicedaily. These can
 * be extended using the {@see 'cron_schedules'} filter in wp_get_schedules().
 *
 * Use wp_next_scheduled() to prevent duplicates
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to run the event.
 * @param string $recurrence How often the event should recur.
 * @param string $hook       Action hook to execute when event is run.
 * @param array  $args       Optional. Arguments to pass to the hook's
 *                           callback function.
 *
 * @since 2.1.0
 *
 * @return false|void False if the event does not get scheduled.
 */
function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) // @codingStandardsIgnoreLine
{
    // Make sure timestamp is a positive integer
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        return false;
    }

    $crons = _get_cron_array();
    $schedules = wp_get_schedules();

    if (!isset($schedules[ $recurrence ])) {
        return false;
    }

    $_event = (object) [
        'hook' => $hook,
        'timestamp' => $timestamp,
        'schedule' => $recurrence,
        'args' => $args,
        'interval' => $schedules[ $recurrence ]['interval']
    ];
    // This filter is documented in wp-includes/cron.hh
    $event = apply_filters('schedule_event', $_event);

    // A plugin disallowed this event
    if (!$event) {
        return false;
    }

    $key = md5(serialize($event->args));

    $crons[ $event->timestamp ][ $event->hook ][ $key ] = [
        'schedule' => $event->schedule,
        'args' => $event->args,
        'interval' => $event->interval
    ];
    uksort($crons, 'strnatcasecmp');
    _set_cron_array($crons);
}

/**
 * Reschedule a recurring event.
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to run the event.
 * @param string $recurrence How often the event should recur.
 * @param string $hook       Action hook to execute when event is run.
 * @param array  $args       Optional. Arguments to pass to the hook's
 *                           callback function.
 *
 * @since 2.1.0
 *
 * @return false|void False if the event does not get rescheduled.
 */
function wp_reschedule_event($timestamp, $recurrence, $hook, $args = []) // @codingStandardsIgnoreLine
{
    // Make sure timestamp is a positive integer
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        return false;
    }

    $crons = _get_cron_array();
    $schedules = wp_get_schedules();
    $key = md5(serialize($args));
    $interval = 0;

    // First we try to get it from the schedule
    if (isset($schedules[ $recurrence ])) {
        $interval = $schedules[ $recurrence ]['interval'];
    }
    // Now we try to get it from the saved interval in case the schedule disappears
    if (0 === $interval) {
        $interval = $crons[ $timestamp ][ $hook ][ $key ]['interval'];
    }
    // Now we assume something is wrong and fail to schedule
    if (0 === $interval) {
        return false;
    }

    $now = time();

    if ($timestamp >= $now) {
        $timestamp = $now + $interval;
    } else {
        $timestamp = $now + ($interval - (($now - $timestamp) % $interval));
    }

    wp_schedule_event($timestamp, $recurrence, $hook, $args);
}

/**
 * Unschedule a previously scheduled event.
 *
 * The $timestamp and $hook parameters are required so that the event can be
 * identified.
 *
 * @param int    $timestamp Unix timestamp (UTC) for when to run the event.
 * @param string $hook      Action hook, the execution of which will be unscheduled.
 * @param array  $args      Arguments to pass to the hook's callback function.
 * Although not passed to a callback function, these arguments are used
 * to uniquely identify the scheduled event, so they should be the same
 * as those used when originally scheduling the event.
 *
 * @since 2.1.0
 *
 * @return false|void False if the event does not get unscheduled.
 */
function wp_unschedule_event($timestamp, $hook, $args = []) // @codingStandardsIgnoreLine
{
    // Make sure timestamp is a positive integer
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        return false;
    }

    $crons = _get_cron_array();
    $key = md5(serialize($args));
    unset($crons[ $timestamp ][ $hook ][ $key ]);
    if (empty($crons[ $timestamp ][ $hook ])) {
        unset($crons[ $timestamp ][ $hook ]);
    }
    if (empty($crons[ $timestamp ])) {
        unset($crons[ $timestamp ]);
    }
    _set_cron_array($crons);
}

/**
 * Unschedule all events attached to the specified hook.
 *
 * @param string $hook Action hook, the execution of which will be unscheduled.
 * @param array  $args Optional. Arguments that were to be passed to the hook's
 *                     callback function.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_clear_scheduled_hook($hook, $args = []) // @codingStandardsIgnoreLine
{
    // Backward compatibility
    // Previously this function took the arguments as discrete vars rather
    // than an array like the rest of the API
    if (!is_array($args)) {
        _deprecated_argument(
            __FUNCTION__,
            '3.0.0',
            __('This argument has changed to an array to match the behavior of the other cron functions.') // @codingStandardsIgnoreLine
        );
        $args = array_slice(func_get_args(), 1);
    }

    // This logic duplicates wp_next_scheduled()
    // It's required due to a scenario where wp_unschedule_event()
    // fails due to update_option() failing, and, wp_next_scheduled() returns
    // the same schedule in an infinite loop.
    $crons = _get_cron_array();
    if (empty($crons)) {
        return;
    }

    $key = md5(serialize($args));
    foreach ($crons as $timestamp => $cron) {
        if (isset($cron[ $hook ][ $key ])) {
            wp_unschedule_event($timestamp, $hook, $args);
        }
    }
}

/**
 * Retrieve the next timestamp for an event.
 *
 * @param string $hook Action hook to execute when event is run.
 * @param array  $args Optional. Arguments to pass to the hook's callback function.
 *
 * @since 2.1.0
 *
 * @return false|int The Unix timestamp of the next time the scheduled
 *                   event will occur.
 */
function wp_next_scheduled($hook, $args = []) // @codingStandardsIgnoreLine
{
    $crons = _get_cron_array();
    $key = md5(serialize($args));
    if (empty($crons)) {
        return false;
    }
    foreach ($crons as $timestamp => $cron) {
        if (isset($cron[ $hook ][ $key ])) {
            return $timestamp;
        }
    }
    return false;
}

/**
 * Sends a request to run cron through HTTP request that doesn't halt page loading.
 *
 * @param int $gmt_time Optional. Unix timestamp (UTC).
 *                      Default 0 (current time is used).
 *
 * @since 2.1.0
 *
 * @return void
 */
function spawn_cron($gmt_time = null) // @codingStandardsIgnoreLine
{
    $app = getApp();
    if (!$gmt_time) {
        $gmt_time = microtime(true);
    }
    $doing = $app['request']->query->get('doing_wp_cron');
    if (defined('DOING_CRON') || $doing) {
        return;
    }

    /*
     * Get the cron lock, which is a Unix timestamp of when the last cron was spawned
     * and has not finished running.
     *
     * Multiple processes on multiple web servers can run this code concurrently,
     * this lock attempts to make spawning as atomic as possible.
     */
    $lock = get_transient('doing_cron');

    if ($lock > $gmt_time + 10 * MINUTE_IN_SECONDS) {
        $lock = 0;
    }

    // don't run if another process is currently running it or
    // more than once every 60 sec.
    if ($lock + WP_CRON_LOCK_TIMEOUT > $gmt_time) {
        return;
    }

    //sanity check
    $crons = _get_cron_array();
    if (!is_array($crons)) {
        return;
    }

    $keys = array_keys($crons);
    if (isset($keys[0]) && $keys[0] > $gmt_time) {
        return;
    }

    if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
        $app = getApp();
        if ('GET' !== $app['request.method']
            || defined('DOING_AJAX')
            || defined('XMLRPC_REQUEST')
        ) {
            return;
        }

        $doing_wp_cron = sprintf('%.22F', $gmt_time);
        set_transient('doing_cron', $doing_wp_cron);

        $location = add_query_arg(
            'doing_wp_cron',
            $doing_wp_cron,
            wp_unslash($app['request.uri'])
        );

        ob_start();
        wp_redirect($location);
        echo ' ';

        // flush any buffers and send the headers
        while (ob_end_flush()) {
            continue;
        }
        flush();

        include_once ABSPATH . 'wp-cron.hh';
        return;
    }

    // Set the cron lock with the current unix timestamp,
    // when the cron is being spawned.
    $doing_wp_cron = sprintf('%.22F', $gmt_time);
    set_transient('doing_cron', $doing_wp_cron);

    /**
     * Filters the cron request arguments.
     *
     * @param array  $cron_request_array {
     *     An array of cron request URL arguments.
     *
     *     @type string $url  The cron request URL.
     *     @type int    $key  The 22 digit GMT microtime.
     *     @type array  $args {
     *         An array of cron request arguments.
     *
     *         @type int  $timeout   The request timeout in seconds.
     *                                Default .01 seconds.
     *         @type bool $blocking  Whether to set blocking for the request.
     *                                Default false.
     *         @type bool $sslverify Whether SSL should be verified for
     *                                the request. Default false.
     *     }
     * }
     * // @param string $doing_wp_cron The unix timestamp of the cron lock.
     *
     * @since 3.5.0
     * @since 4.5.0 The `$doing_wp_cron` parameter was added.
     */
    $cron_request = apply_filters(
        'cron_request',
        [
            'url'  => add_query_arg(
                'doing_wp_cron',
                $doing_wp_cron,
                site_url('wp-cron.hh')
            ),
            'key'  => $doing_wp_cron,
            'args' => [
                'timeout'   => 0.01,
                'blocking'  => false,
                // This filter is documented in wp-includes/class-wp-http-streams.php
                'sslverify' => apply_filters('https_local_ssl_verify', false)
            ]
        ],
        $doing_wp_cron
    );

    wp_remote_post($cron_request['url'], $cron_request['args']);
}

/**
 * Run scheduled callbacks or spawn cron for all scheduled events.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_cron() // @codingStandardsIgnoreLine
{
    $app = getApp();
    // Prevent infinite loops caused by lack of wp-cron.hh
    if (strpos($app['request.uri'], '/wp-cron.hh') !== false
        || (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)
    ) {
        return;
    }

    $crons = _get_cron_array();
    if (false === $crons) {
        return;
    }

    $gmt_time = microtime(true);
    $keys = array_keys($crons);
    if (isset($keys[0]) && $keys[0] > $gmt_time) {
        return;
    }

    $schedules = wp_get_schedules();
    foreach ($crons as $timestamp => $cronhooks) {
        if ($timestamp > $gmt_time) {
            break;
        }
        foreach (array_keys($cronhooks) as $hook) {
            if (isset($schedules[ $hook ]['callback'])
                && !call_user_func($schedules[ $hook ]['callback'])
            ) {
                continue;
            }
            spawn_cron($gmt_time);
            return;
        }
    }
}

/**
 * Retrieve supported event recurrence schedules.
 *
 * The default supported recurrences are 'hourly', 'twicedaily', and 'daily'.
 * A plugin may add more by hooking into the {@see 'cron_schedules'} filter.
 * The filter accepts an array of arrays. The outer array has a key that
 * is the name of the schedule or for example 'weekly'. The value is an array
 * with two keys, one is 'interval' and the other is 'display'.
 *
 * The 'interval' is a number in seconds of when the cron job should run. So for
 * 'hourly', the time is 3600 or 60*60. For weekly, the value would be
 * 60*60*24*7 or 604800. The value of 'interval' would then be 604800.
 *
 * The 'display' is the description. For the 'weekly' key, the 'display' would
 * be `__('Once Weekly')`.
 *
 * For your plugin, you will be passed an array. you can easily add your
 * schedule by doing the following.
 *
 *     // Filter parameter variable name is 'array'.
 *     $array['weekly'] = array(
 *         'interval' => 604800,
 *            'display'  => __('Once Weekly')
 *    );
 *
 * @since 2.1.0
 *
 * @return array
 */
function wp_get_schedules() // @codingStandardsIgnoreLine
{
    $schedules = [
        'hourly'     => [
            'interval' => HOUR_IN_SECONDS,
            'display' => __('Once Hourly'),
        ],
        'twicedaily' => [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily'),
        ],
        'daily'      => [
            'interval' => DAY_IN_SECONDS,
            'display' => __('Once Daily'),
        ],
    ];
    /**
     * Filters the non-default cron schedules.
     *
     * @param array $new_schedules An array of non-default cron schedules.
     *                             Default empty.
     *
     * @since 2.1.0
     */
    return array_merge(apply_filters('cron_schedules', []), $schedules);
}

/**
 * Retrieve the recurrence schedule for an event.
 *
 * @param string $hook Action hook to identify the event.
 * @param array  $args Optional. Arguments passed to the event's callback function.
 *
 * @see   wp_get_schedules() for available schedules.
 * @since 2.1.0
 *
 * @return string|false False, if no schedule. Schedule name on success.
 */
function wp_get_schedule($hook, $args = []) // @codingStandardsIgnoreLine
{
    $crons = _get_cron_array();
    $key = md5(serialize($args));
    if (empty($crons)) {
        return false;
    }
    foreach ($crons as $cron) {
        if (isset($cron[ $hook ][ $key ])) {
            return $cron[ $hook ][ $key ]['schedule'];
        }
    }
    return false;
}

//
// Private functions
//

/**
 * Retrieves the cron lock.
 *
 * Returns the uncached `doing_cron` transient.
 *
 * @since 3.3.0
 *
 * @return string|false Value of the `doing_cron` transient, 0|false otherwise.
 */
function _get_cron_lock() // @codingStandardsIgnoreLine
{
    $app = getApp();
    $wpdb = $app['db'];

    $value = 0;
    if (wp_using_ext_object_cache()) {
        /*
         * Skip local cache and force re-fetch of doing_cron transient
         * in case another process updated the cache.
         */
        $value = wp_cache_get('doing_cron', 'transient', true);
    } else {
        $table = $wpdb->options;
        $sql = "SELECT option_value FROM {$table} WHERE option_name = %s LIMIT 1";
        $row = $wpdb->get_row($wpdb->prepare($sql, '_transient_doing_cron'));
        if (is_object($row)) {
            $value = $row->option_value;
        }
    }

    return $value;
}

/**
 * Retrieve cron info array option.
 *
 * @since  2.1.0
 * @access private
 *
 * @return false|array CRON info array.
 */
function _get_cron_array() // @codingStandardsIgnoreLine
{
    $cron = get_option('cron');
    if (!is_array($cron)) {
        return false;
    }

    if (!isset($cron['version'])) {
        $cron = _upgrade_cron_array($cron);
    }

    unset($cron['version']);

    return $cron;
}

/**
 * Updates the CRON option with the new CRON array.
 *
 * @param array $cron Cron info array from _get_cron_array().
 *
 * @since  2.1.0
 * @access private
 *
 * @return void
 */
function _set_cron_array($cron) // @codingStandardsIgnoreLine
{
    $cron['version'] = 2;
    update_option('cron', $cron);
}

/**
 * Upgrade a Cron info array.
 *
 * This function upgrades the Cron info array to version 2.
 *
 * @param array $cron Cron info array from _get_cron_array().
 *
 * @since  2.1.0
 * @access private
 *
 * @return array An upgraded Cron info array.
 */
function _upgrade_cron_array($cron) // @codingStandardsIgnoreLine
{
    if (isset($cron['version']) && 2 == $cron['version']) {
        return $cron;
    }

    $new_cron = [];

    foreach ((array) $cron as $timestamp => $hooks) {
        foreach ((array) $hooks as $hook => $args) {
            $key = md5(serialize($args['args']));
            $new_cron[ $timestamp ][ $hook ][ $key ] = $args;
        }
    }

    $new_cron['version'] = 2;
    update_option('cron', $new_cron);
    return $new_cron;
}
