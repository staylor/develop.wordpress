<?hh
/**
 * Signup code
 *
 * PHP version 7
 *
 * @category Registration
 * @package  WordPress
 * @author   WordPress dot org <contact@wordpress.org>
 * @license  http://opensource.org/licenses/gpl-license.php GPL
 * @link     http://wordpress.org
 */

use WP\{View,Error};

// Sets up the WordPress Environment.
require __DIR__ . '/wp-load.hh';

if (! is_multisite()) {
    wp_redirect(wp_registration_url());
    die();
}

if (! is_main_site()) {
    wp_redirect(network_site_url('wp-signup.hh'));
    die();
}

$new = $_get->get('new');
if (is_array(get_site_option('illegal_names'))
    && $new
    && in_array($new, get_site_option('illegal_names'))
) {
    wp_redirect(network_home_url());
    die();
}

$view = new View($app);

require __DIR__ . '/wp-signup-functions.hh';

// Set up the WordPress query.
wp();

add_action('wp_head', 'wp_no_robots');
/**
 * Fires within the head section of the site sign-up screen.
 *
 * @since 3.0.0
 */
add_action('wp_head', () ==> do_action('signup_header'));

// Load the theme template.
require_once ABSPATH . WPINC . '/template-loader.php';

// Fix for page title
$app['wp']->current_query->is_404 = false;

/**
 * Fires before the Site Signup page is loaded.
 *
 * @since 4.4.0
 */
do_action('before_signup_header');

/**
 * Prints styles for front-end Multisite signup pages
 *
 * @since MU
 */
add_action('wp_head', () ==> {
    echo $view->render('signup/css');
});

get_header('wp-signup');

/**
 * Fires before the site sign-up form.
 *
 * @since 3.0.0
 */
do_action('before_signup_form');

// Main
$active_signup = get_site_option('registration', 'none');

/**
 * Filters the type of site sign-up.
 *
 * @param string $active_signup String that returns registration type.
 *                              The value can be:
 *                              'all', 'none', 'blog', or 'user'.
 *
 * @since 3.0.0
 */
$active_signup = apply_filters('wpmu_active_signup', $active_signup);
$app->set('active_signup', $active_signup);

// Make the signup type translatable.
$i18n_signup['all'] = _x('all', 'Multisite active signup type');
$i18n_signup['none'] = _x('none', 'Multisite active signup type');
$i18n_signup['blog'] = _x('blog', 'Multisite active signup type');
$i18n_signup['user'] = _x('user', 'Multisite active signup type');

if (is_super_admin()) {
    /* translators: 1: type of site sign-up; 2: network settings URL */
    echo '<div class="mu_alert">' .
        // @codingStandardsIgnoreLine
        sprintf(__('Greetings Site Administrator! You are currently allowing &#8220;%s&#8221; registrations. To change or disable registration go to your <a href="%s">Options page</a>.'), $i18n_signup[$active_signup], esc_url(network_admin_url('settings.php'))) . '</div>';
}

$newblogname = $_get->get('new') ?
    strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_get->get('new'))) :
    null;

$current_user = wp_get_current_user();
if ($active_signup == 'none') {
    _e('Registration has been disabled.');
} elseif ($active_signup == 'blog' && !is_user_logged_in()) {
    $login_url = wp_login_url(network_site_url('wp-signup.php'));
    /* translators: %s: login URL */
    printf(__('You must first <a href="%s">log in</a>, and then you can create a new site.'), $login_url);// @codingStandardsIgnoreLine
} else {
    $stage = $_post->get('stage', 'default');
    switch ($stage) {
    case 'validate-user-signup' :
        if ($active_signup == 'all'
            || $_post->get('signup_for') === 'blog'
            && $active_signup == 'blog'
            || $_post->get('signup_for') === 'user'
            && $active_signup === 'user'
        ) {
            validate_user_signup();
        } else {
            _e('User registration has been disabled.');
        }
        break;
    case 'validate-blog-signup':
        if ($active_signup === 'all' || $active_signup === 'blog') {
            validate_blog_signup();
        } else {
            _e('Site registration has been disabled.');
        }
        break;
    case 'gimmeanotherblog':
        validate_another_blog_signup();
        break;
    case 'default':
    default :
        $user_email = $_post->get('user_email', '');
        /**
         * Fires when the site sign-up form is sent.
         *
         * @since 3.0.0
         */
        do_action('preprocess_signup_form');
        if (is_user_logged_in()
            && ($active_signup == 'all' || $active_signup == 'blog')
        ) {
            signup_another_blog($newblogname);
        } elseif (! is_user_logged_in()
            && ($active_signup == 'all' || $active_signup == 'user')
        ) {
            signup_user($newblogname, $user_email);
        } elseif (! is_user_logged_in() && ($active_signup == 'blog')) {
            _e('Sorry, new registrations are not allowed at this time.');
        } else {
            _e('You are logged in already. No need to register again!');
        }

        if (null !== $newblogname) {
            $newblog = get_blogaddress_by_name($newblogname);

            if ($active_signup == 'blog' || $active_signup == 'all') {
                /* translators: %s: site address */
                printf(
                    '<p><em>' . __('The site you were looking for, %s, does not exist, but you can create it now!') . '</em></p>', // @codingStandardsIgnoreLine
                    '<strong>' . $newblog . '</strong>'
                );
            } else {
                /* translators: %s: site address */
                printf(
                    '<p><em>' . __('The site you were looking for, %s, does not exist.') . '</em></p>',// @codingStandardsIgnoreLine
                    '<strong>' . $newblog . '</strong>'
                );
            }
        }
        break;
    }
}

echo $view->render('signup/content');

/**
 * Fires after the sign-up forms, before wp_footer.
 *
 * @since 3.0.0
 */
do_action('after_signup_form');

get_footer('wp-signup');
