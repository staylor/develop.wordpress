<?hh
/**
 * Confirms that the activation key that is sent in an email after a user signs
 * up for a new site matches the key for that user and then displays confirmation.
 *
 * PHP version 7
 *
 * @category Registration
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

use WP\View;
use WP\Registration\L10N;

 const WP_INSTALLING = true;

// Sets up the WordPress Environment.
require __DIR__ . '/wp-load.hh';

if (! is_multisite()) {
    wp_redirect(wp_registration_url());
    die();
}

wp();

if (is_object($wp_object_cache)) {
    $wp_object_cache->cache_enabled = false;
}

// Fix for page title
$app[ 'wp' ]->current_query->is_404 = false;

$view = new View($app);
$view->l10n = new L10N();

/**
 * Fires before the Site Activation page is loaded.
 *
 * @since 3.0.0
 */
do_action('activate_header');

/**
 * Adds an action hook specific to this page.
 *
 * Fires on {@see 'wp_head'}.
 *
 * @since MU
 */
add_action(
    'wp_head',
    /**
     * Fires before the Site Activation page is loaded.
     *
     * Fires on the {@see 'wp_head'} action.
     *
     * @since 3.0.0
     */
    () ==> do_action('activate_wp_head')
);

/**
 * Loads styles specific to this page.
 *
 * @since MU
 */
add_action(
    'wp_head',
    () ==> {
        echo $view->render('activate/css');
    }
);

$key = $_get->get('key') ?
    $_get->get('key') :
    $_post->get('key');

$data = [
    'action' => network_site_url('wp-activate.hh'),
    'key' => $key,
];

$result = null;
$errorData = null;
if (!empty($key)) {
    $result = wpmu_activate_signup($key);
    if (is_wp_error($result)) {
        $data['errorMessage'] = $result->get_error_message();

        if (in_array($result->get_error_code(), ['already_active', 'blog_taken'])) {
            $errorData = $result->get_error_data();
            if ($signup->domain . $signup->path === '') {
                $data['accountUrl'] = sprintf(
                    /* translators:
                        1: login URL,
                        2: username,
                        3: user email,
                        4: lost password URL */
                    __('Your account has been activated. You may now <a href="%1$s">log in</a> to the site using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.'), // @codingStandardsIgnoreLine
                    network_site_url('wp-login.php', 'login'),
                    $signup->user_login,
                    $signup->user_email,
                    wp_lostpassword_url()
                );
            } else {
                $data['siteUrl'] = sprintf(
                    /* translators:
                        1: site URL,
                        2: site domain,
                        3: username,
                        4: user email,
                        5: lost password URL */
                    __('Your site at <a href="%1$s">%2$s</a> is active. You may now log in to your site using your chosen username of &#8220;%3$s&#8221;. Please check your email inbox at %4$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.'),// @codingStandardsIgnoreLine
                    'http://' . $signup->domain,
                    $signup->domain,
                    $signup->user_login,
                    $signup->user_email,
                    wp_lostpassword_url()
                );
            }
        }
    } else {
        $url = isset($result[ 'blog_id' ]) ?
            get_home_url((int) $result[ 'blog_id' ]) :
            '';
        $user = get_userdata((int) $result[ 'user_id' ]);

        $data['username'] = $user->user_login;
        $data['password'] = $result[ 'password' ];
        $blogUrl = $url && $url != network_home_url('', 'http');
        if ($blogUrl) {
            switch_to_blog((int) $result[ 'blog_id' ]);
            $login_url = wp_login_url();
            restore_current_blog();
            /* translators: 1: site URL, 2: login URL */
            $data['loginUrl'] = sprintf(
                // @codingStandardsIgnoreLine
                __('Your account is now activated. <a href="%1$s">View your site</a> or <a href="%2$s">Log in</a>'),
                $url,
                esc_url($login_url)
            );
        } else {
            /* translators: 1: login URL, 2: network home URL */
            $data['loginUrl'] = sprintf(
                // @codingStandardsIgnoreLine
                __('Your account is now activated. <a href="%1$s">Log in</a> or go back to the <a href="%2$s">homepage</a>.'),
                network_site_url('wp-login.php', 'login'),
                network_home_url()
            );
        }
    }
}

$data['errorData'] = $errorData;

$view->setData($data);

get_header('wp-activate');

echo $view->render('activate/content');

get_footer('wp-activate');
