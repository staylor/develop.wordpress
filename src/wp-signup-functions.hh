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
use WP\Registration\L10N;
use function WP\{getApp,render};

/**
 * Generates and displays the Signup and Create Site forms
 *
 * @param string $blogname   The new site name.
 * @param string $blog_title The new site title.
 * @param Error  $errors     A Error object containing existing errors.
 *
 * @since MU
 */
function show_blog_form( // @codingStandardsIgnoreLine
    $blogname = '',
    $blog_title = '',
    $errors = null
) {
    $app = getApp();
    $_post = $app['request']->request;

    $nameError = null;
    $titleError = null;

    if (!($errors && $errors instanceof Error)) {
        $errors = new Error();
    } else {
        $nameError = $errors->get_error_message('blogname');
        $titleError = $errors->get_error_message('blog_title');
    }

    $current_network = get_network();
    $site_domain = preg_replace('|^www\.|', '', $current_network->domain);

    $yourAddress = '';
    if (! is_user_logged_in()) {
        if (! is_subdomain_install()) {
            $site = $current_network->domain . $current_network->path .
                __('sitename');
        } else {
            $site = __('domain') . '.' . $site_domain . $current_network->path;
        }

        /* translators: %s: site address */
        $yourAddress = sprintf(__('Your address will be %s.'), $site);
    }

    // Site Language.
    $languages = signup_get_available_languages();

    $dropdown = null;
    if (! empty($languages)) {
        // Network default.
        $lang = $_post->get('WPLANG', get_site_option('WPLANG'));

        // Use US English if the default isn't available.
        if (! in_array($lang, $languages)) {
            $lang = '';
        }
        $dropdown = wp_dropdown_languages(
            [
                'name' => 'WPLANG',
                'id' => 'site-language',
                'selected' => $lang,
                'languages' => $languages,
                'show_available_translations' => false,
                'echo' => false,
            ]
        );
    } // Languages.

    echo render('signup/blog-form', [
        'loggedIn' => is_user_logged_in(),
        'currentNetwork' => $current_network,
        'siteDomain' => $site_domain,
        'blogname' => $blogname,
        'blogTitle' => $blog_title,
        'languageDropdown' => $dropdown,
        'isSubdomainInstall' => is_subdomain_install(),
        'errors' => [
            'name' => $nameError,
            'title' => $titleError,
        ],
        'l10n' => [
            'no' => __('No'),
            'yes' => __('Yes'),
            'privacy' => __('Privacy:'),
            // @codingStandardsIgnoreLine
            'allow_search_engines' => __('Allow search engines to index this site.'),
            'blogname_label' => is_subdomain_install() ?
                __('Site Domain:') :
                __('Site Name:'),
            'your_address' => $yourAddress,
            // @codingStandardsIgnoreLine
            'name_rules' => __('Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!'),
            'site_title' => __('Site Title:'),
            'site_language' => __('Site Language:'),
        ],
        'yesChecked' => checked(
            ! $_post->has('blog_public') || $_post->get('blog_public') == '1',
            true,
            false
        ),
        'noChecked' => checked(
            $_post->has('blog_public') && $_post->get('blog_public') == '0',
            true,
            false
        ),
    ]);

    /**
     * Fires after the site sign-up form.
     *
     * @param Error $errors A Error object possibly containing
     *                      'blogname' or 'blog_title' errors.
     *
     * @since 3.0.0
     */
    do_action('signup_blogform', $errors);
}

/**
 * Validate the new site signup
 *
 * @since MU
 *
 * @return array Contains the new site data and error messages.
 */
function validate_blog_form() // @codingStandardsIgnoreLine
{
    $app = getApp();
    $_post = $app['request']->request;

    return wpmu_validate_blog_signup(
        $_post->get('blogname'),
        $_post->get('blog_title'),
        is_user_logged_in() ? wp_get_current_user() : ''
    );
}

/**
 * Display user registration form
 *
 * @param string $user_name  The entered username.
 * @param string $user_email The entered email address.
 * @param Error  $errors     A Error object containing existing errors.
 *
 * @since MU
 */
function show_user_form( // @codingStandardsIgnoreLine
    $user_name = '',
    $user_email = '',
    $errors = null
) {
    $nameError = null;
    $emailError = null;
    $genericError = null;

    if (null === $errors || ! is_wp_error($errors)) {
        $errors = new Error();
    } else {
        $nameError = $errors->get_error_message('user_name');
        $emailError = $errors->get_error_message('user_email');
        $genericError = $errors->get_error_message('generic');
    }

    echo render('signup/user-form', [
        'userName' => $user_name,
        'userEmail' => $user_email,
        'errors' => [
            'name' => $nameError,
            'email' => $emailError,
            'generic' => $genericError,
        ],
        'l10n' => new L10N(),
    ]);
    /**
     * Fires at the end of the user registration form on the site sign-up form.
     *
     * @param Error $errors A Error object containing containing
     *                      'user_name' or 'user_email' errors.
     *
     * @since 3.0.0
     */
    do_action('signup_extra_fields', $errors);
}

/**
 * Validate user signup name and email
 *
 * @since MU
 *
 * @return array Contains username, email, and error messages.
 */
function validate_user_form() // @codingStandardsIgnoreLine
{
    $app = getApp();
    $_post = $app['request']->request;
    return wpmu_validate_user_signup(
        $_post->get('user_name'),
        $_post->get('user_email')
    );
}

/**
 * Allow returning users to sign up for another site
 *
 * @param string $blogname   The new site name
 * @param string $blog_title The new site title.
 * @param Error  $errors     A Error object containing existing errors.
 *
 * @since MU
 */
function signup_another_blog( // @codingStandardsIgnoreLine
    $blogname = '',
    $blog_title = '',
    $errors = null
) {
    $current_user = wp_get_current_user();
    $l10n = new L10N();

    if (! is_wp_error($errors)) {
        $errors = new Error();
    }

    $defaults = [
        'blogname'   => $blogname,
        'blog_title' => $blog_title,
        'errors'     => $errors
    ];

    /**
     * Filters the default site sign-up variables.
     *
     * @param array $defaults {
     *     An array of default site sign-up variables.
     *
     *     @type string $blogname   The site blogname.
     *     @type string $blog_title The site title.
     *     @type Error  $errors     A Error object possibly containing
     *                              'blogname' or 'blog_title' errors.
     * }
     *
     * @since 3.0.0
     */
    $filtered = apply_filters('signup_another_blog_init', $defaults);

    $strings = [
        'get_another_site' => sprintf(
            $l10n->get_another_site,
            get_network()->site_name
        ),
        'welcome_back' => sprintf(
            $l10n->welcome_back,
            $current_user->display_name
        ),
    ];

    $blogs = [];
    $userBlogs = get_blogs_of_user($current_user->ID);
    foreach ($userBlogs as $userBlog) {
        $url = get_home_url($userBlog->userblog_id);
        $blogs[] = [
            'url' => $url,
            'escUrl' => esc_url($url),
        ];
    }

    $app = getApp();
    $view = new View($app);
    $view->setActions([
        /**
         * Hidden sign-up form fields output when creating another site or user.
         *
         * @param string $context A string describing the steps of the
         *                        sign-up process. The value can be:
         *                        'create-another-site', 'validate-user', or
         *                        'validate-site'.
         *
         * @since MU
         */
        'signup_hidden_fields' => ['create-another-site'],
    ]);
    $view->setData([
        'blogForm' => $app->mute(() ==> {
            show_blog_form(
                $filtered['blogname'],
                $filtered['blog_title'],
                $filtered['errors']
            );
        }),
        'hasBlogs' => count($blogs) > 0,
        'blogs' => $blogs,
        'errors' => $filtered['errors']->get_error_code(),
        'l10n' => array_merge($l10n->getData(), $strings),
    ]);

    echo $view->render('signup/another-blog');
}

/**
 * Validate a new site signup.
 *
 * @since MU
 *
 * @return null|bool True if site signup was validated, false if error.
 *                   The function halts all execution if the user is not logged in.
 */
function validate_another_blog_signup() // @codingStandardsIgnoreLine
{
    $app = getApp();
    $_post = $app['request']->request;
    $wpdb = $app['db'];

    $current_user = wp_get_current_user();
    if (! is_user_logged_in()) {
        die();
    }

    $result = validate_blog_form();

    if ($result['errors']->get_error_code()) {
        signup_another_blog(
            $result['blogname'],
            $result['blog_title'],
            $result['errors']
        );
        return false;
    }

    $public = $_post->getInt('blog_public');

    $blog_meta_defaults = [
        'lang_id' => 1,
        'public'  => $public
    ];

    // Handle the language setting for the new site.
    if (! empty($_post->get('WPLANG'))) {
        $languages = signup_get_available_languages();

        if (in_array($_post->get('WPLANG'), $languages)) {
            $language = wp_unslash(sanitize_text_field($_post->get('WPLANG')));

            if ($language) {
                $blog_meta_defaults['WPLANG'] = $language;
            }
        }
    }

    /**
     * Filters the new site meta variables.
     *
     * Use the {@see 'add_signup_meta'} filter instead.
     *
     * @param array $blog_meta_defaults An array of default blog meta variables.
     *
     * @since      MU
     * @deprecated 3.0.0 Use the {@see 'add_signup_meta'} filter instead.
     */
    $meta_defaults = apply_filters('signup_create_blog_meta', $blog_meta_defaults);

    /**
     * Filters the new default site meta variables.
     *
     * @param array $meta {
     *     An array of default site meta variables.
     *
     *     @type int $lang_id     The language ID.
     *     @type int $blog_public Whether search engines should be discouraged
     *                            from indexing the site.
     *                            1 for true, 0 for false.
     * }
     *
     * @since 3.0.0
     */
    $meta = apply_filters('add_signup_meta', $meta_defaults);

    $blog_id = wpmu_create_blog(
        $result['domain'],
        $result['path'],
        $result['blog_title'],
        $current_user->ID,
        $meta,
        $wpdb->siteid
    );

    if (is_wp_error($blog_id)) {
        return false;
    }

    confirm_another_blog_signup(
        $result['domain'],
        $result['path'],
        $result['blog_title'],
        $current_user->user_login,
        $blog_id
    );
    return true;
}

/**
 * Confirm a new site signup.
 *
 * @param string $domain     The domain URL.
 * @param string $path       The site root path.
 * @param string $blog_title The site title.
 * @param string $user_name  The username.
 * @param int    $blog_id    The site ID.
 *
 * @since MU
 * @since 4.4.0 Added the `$blog_id` parameter.
 */
function confirm_another_blog_signup( // @codingStandardsIgnoreLine
    $domain,
    $path,
    $blog_title,
    $user_name,
    $blog_id = 0
) {

    if ($blog_id) {
        switch_to_blog($blog_id);
        $home_url  = home_url('/');
        $login_url = wp_login_url();
        restore_current_blog();
    } else {
        $home_url  = 'http://' . $domain . $path;
        $login_url = $home_url . 'wp-login.php';
    }

    $site = sprintf(
        '<a href="%s">%s</a>',
        esc_url($home_url),
        $blog_title
    );

    $l10n = new L10N();
    $strings = [
        'the_site_is_yours' => sprintf($l10n->the_site_is_yours, $site),
        'your_new_site' => sprintf(
            $l10n->your_new_site, // @codingStandardsIgnoreLine
            esc_url($home_url),
            untrailingslashit($domain . $path),
            esc_url($login_url),
            $user_name
        )
    ];

    echo render('signup/confirm-another-blog-signup', [
        'l10n' => $strings
    ]);

    /**
     * Fires when the site or user sign-up process is complete.
     *
     * @since 3.0.0
     */
    do_action('signup_finished');
}

/**
 * Setup the new user signup process
 *
 * @param string $user_name  The username.
 * @param string $user_email The user's email.
 * @param Error  $errors     A Error object containing existing errors.
 *
 * @since MU
 */
function signup_user( // @codingStandardsIgnoreLine
    $user_name = '',
    $user_email = '',
    $errors = null
) {
    $app = getApp();
    $active_signup = $app->get('active_signup');

    if (!is_wp_error($errors)) {
        $errors = new Error();
    }

    $app = getApp();
    $_post = $app['request']->request;
    $signup_for = esc_html($_post->get('signup_for', 'blog'));

    $defaults = array(
        'user_name'  => $user_name,
        'user_email' => $user_email,
        'errors'     => $errors,
    );

    /**
     * Filters the default user variables used on the user sign-up form.
     *
     * @param array $defaults {
     *     An array of default user variables.
     *
     *     @type string $user_name  The user username.
     *     @type string $user_email The user email address.
     *     @type Error  $errors     A Error object with possible errors
     *                              relevant to the sign-up user.
     * }
     *
     * @since 3.0.0
     */
    $filtered = apply_filters(
        'signup_user_init',
        $defaults
    );

    $l10n = new L10N();

    $strings = [
        /* translators: %s: name of the network */
        'get_your_own_account' => sprintf(
            $l10n->get_your_own_account,
            get_network()->site_name
        ),
    ];

    $app = getApp();
    $view = new View($app);
    $view->setActions([
        'signup_hidden_fields' => ['validate-user'],
    ]);
    $view->setData([
        'l10n' => array_merge($l10n->getData(), $strings),
        'userForm' => $app->mute(() ==> {
            show_user_form(
                $filtered['user_name'],
                $filtered['user_email'],
                $filtered['errors']
            );
        }),
        'signupForUser' => $active_signup === 'user',
        'signupForBlog' => $active_signup === 'blog',
        'blogChecked' => checked($signup_for, 'blog', false),
        'userChecked' => checked($signup_for, 'user', false),
    ]);

    echo $view->render('signup/confirm-another-blog-signup');
}

/**
 * Validate the new user signup
 *
 * @since MU
 *
 * @return bool True if new user signup was validated, false if error
 */
function validate_user_signup() // @codingStandardsIgnoreLine
{
    $result = validate_user_form();
    $user_name = $result['user_name'];
    $user_email = $result['user_email'];
    $errors = $result['errors'];

    if ($errors->get_error_code()) {
        signup_user($user_name, $user_email, $errors);
        return false;
    }

    $app = getApp();
    $_post = $app['request']->request;
    if ('blog' == $_post->get('signup_for')) {
        signup_blog($user_name, $user_email);
        return false;
    }

    // This filter is documented in wp-signup.hh
    wpmu_signup_user(
        $user_name,
        $user_email,
        apply_filters('add_signup_meta', [])
    );

    confirm_user_signup($user_name, $user_email);
    return true;
}

/**
 * New user signup confirmation
 *
 * @param string $user_name  The username
 * @param string $user_email The user's email address
 *
 * @since MU
 */
function confirm_user_signup( // @codingStandardsIgnoreLine
    $user_name,
    $user_email
) {
    ?>
    <h2><?php /* translators: %s: username */
    printf(__('%s is your new username'), $user_name) ?></h2>
    <p><?php
        _e('But, before you can start using your new username, <strong>you must activate it</strong>.'); // @codingStandardsIgnoreLine
    ?></p>
    <p><?php /* translators: %s: email address */
        printf(
            __('Check your inbox at %s and click the link given.'),
            '<strong>' . $user_email . '</strong>'
        );
    ?></p>
    <p><?php
        _e('If you do not activate your username within two days, you will have to sign up again.'); // @codingStandardsIgnoreLine
    ?></p>
    <?php
    // This action is documented in wp-signup.hh
    do_action('signup_finished');
}

/**
 * Setup the new site signup
 *
 * @param string $user_name  The username.
 * @param string $user_email The user's email address.
 * @param string $blogname   The site name.
 * @param string $blog_title The site title.
 * @param Error  $errors     A Error object containing existing errors.
 *
 * @since MU
 */
function signup_blog( // @codingStandardsIgnoreLine
    $user_name = '',
    $user_email = '',
    $blogname = '',
    $blog_title = '',
    $errors = null
) {
    if (!is_wp_error($errors)) {
        $errors = new Error();
    }

    $signup_blog_defaults = [
        'user_name'  => $user_name,
        'user_email' => $user_email,
        'blogname'   => $blogname,
        'blog_title' => $blog_title,
        'errors'     => $errors
    ];

    /**
     * Filters the default site creation variables for the site sign-up form.
     *
     * @param array $signup_blog_defaults {
     *     An array of default site creation variables.
     *
     *     @type string $user_name  The user username.
     *     @type string $user_email The user email address.
     *     @type string $blogname   The blogname.
     *     @type string $blog_title The title of the site.
     *     @type Error  $errors     An Error object with possible errors relevant
     *                              to new site creation variables.
     * }
     *
     * @since 3.0.0
     */
    $filtered = apply_filters('signup_blog_init', $signup_blog_defaults);

    $blogname = $filtered['blogname'];
    if (empty($blogname)) {
        $blogname = $filtered['user_name'];
    }

    $app = getApp();

    $view = new View($app);
    $view->setActions([
        'signup_hidden_fields' => ['validate-site'],
    ]);
    $view->setData([
        'userName' => $filtered['user_name'],
        'userEmail' => $filtered['user_email'],
        'l10n' => [
            'signup' => __('Signup'),
        ],
        'blogForm' => $app->mute(() ==> {
            show_blog_form(
                $blogname,
                $filtered['blog_title'],
                $filtered['errors']
            );
        });
    ]);
    echo $view->render('signup/blog');
}

/**
 * Validate new site signup
 *
 * @since MU
 *
 * @return bool True if the site signup was validated, false if error
 */
function validate_blog_signup() // @codingStandardsIgnoreLine
{
    $app = getApp();
    $_post = $app['request']->request;

    // Re-validate user info.
    $user_result = wpmu_validate_user_signup(
        $_post->get('user_name'),
        $_post->get('user_email')
    );

    if ($user_result['errors']->get_error_code()) {
        signup_user(
            $user_result['user_name'],
            $user_result['user_email'],
            $user_result['errors']
        );
        return false;
    }

    $result = wpmu_validate_blog_signup(
        $_post->get('blogname'),
        $_post->get('blog_title')
    );

    if ($result['errors']->get_error_code()) {
        signup_blog(
            $user_result['user_name'],
            $user_result['user_email'],
            $result['blogname'],
            $result['blog_title'],
            $result['errors']
        );
        return false;
    }

    $signup_meta = [
        'lang_id' => 1,
        'public' => $_post->getInt('blog_public')
    ];

    // Handle the language setting for the new site.
    if (! empty($_post->get('WPLANG'))) {
        $languages = signup_get_available_languages();

        if (in_array($_post->get('WPLANG'), $languages)) {
            $language = wp_unslash(sanitize_text_field($_post->get('WPLANG')));

            if ($language) {
                $signup_meta['WPLANG'] = $language;
            }
        }
    }

    // This filter is documented in wp-signup.hh
    $meta = apply_filters('add_signup_meta', $signup_meta);

    wpmu_signup_blog(
        $result['domain'],
        $result['path'],
        $result['blog_title'],
        $user_result['user_name'],
        $user_result['user_email'],
        $meta
    );
    confirm_blog_signup(
        $result['domain'],
        $result['path'],
        $result['blog_title'],
        $user_result['user_email']
    );
    return true;
}

/**
 * New site signup confirmation
 *
 * @param string $domain     The domain URL
 * @param string $path       The site root path
 * @param string $blog_title The new site title
 * @param string $user_email The user's email address
 *
 * @since MU
 */
function confirm_blog_signup( // @codingStandardsIgnoreLine
    $domain,
    $path,
    $blog_title,
    $user_email = ''
) {
    $l10n new L10N();

    $strings = [
        'congratulations' => sprintf(
            $l10n->congratulations,
            sprintf(
                '<a href="http://%s%s">%s</a>',
                $domain,
                $path,
                $blog_title
            )
        ),
        'check_your_inbox' => sprintf(
            $l10n->check_your_inbox,
            '<strong>' . $user_email . '</strong>'
        ),
        'have_you_entered' => sprintf(
            $l10n->have_you_entered,
            $user_email
        )
    ];

    echo render('signup/confirm-blog-signup', [
        'l10n' => array_merge($l10n->getData(), $strings),
    ]);
    // This action is documented in wp-signup-functions.hh
    do_action('signup_finished');
}

/**
 * Retrieves languages available during the site/user signup process.
 *
 * @since 4.4.0
 *
 * @see get_available_languages()
 *
 * @return array List of available languages.
 */
function signup_get_available_languages() // @codingStandardsIgnoreLine
{
    /**
     * Filters the list of available languages for front-end site signups.
     *
     * Passing an empty array to this hook will disable output of the setting on the
     * signup form, and the default language will be used when creating the site.
     *
     * Languages not already installed will be stripped.
     *
     * @param array $available_languages Available languages.
     *
     * @since 4.4.0
     */
    $languages = (array) apply_filters(
        'signup_get_available_languages',
        get_available_languages()
    );

    /*
     * Strip any non-installed languages and return.
     *
     * Re-call get_available_languages() here in case a language pack was installed
     * in a callback hooked to the 'signup_get_available_languages' filter
     * before this point.
     */
    return array_intersect_assoc($languages, get_available_languages());
}
