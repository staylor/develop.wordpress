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
 * @param string $blogname  The new site name.
 * @param string $blogTitle The new site title.
 * @param Error  $errors    A Error object containing existing errors.
 *
 * @since MU
 *
 * @return string
 */
function renderBlogForm( // @codingStandardsIgnoreLine
    $blogname = '',
    $blogTitle = '',
    $errors = null
) {
    $app = getApp();
    $_post = $app['request']->request;

    $messages = [];

    if (!($errors && $errors instanceof Error)) {
        $errors = new Error();
    } else {
        $messages['name'] = $errors->get_error_message('blogname');
        $messages['title'] = $errors->get_error_message('blog_title');
    }

    $currentNetwork = get_network();
    $siteDomain = preg_replace('|^www\.|', '', $currentNetwork->domain);

    $yourAddress = '';
    if (! is_user_logged_in()) {
        if (! is_subdomain_install()) {
            $site = $currentNetwork->domain .
                $currentNetwork->path .
                __('sitename');
        } else {
            $site = __('domain') . '.' .
                $siteDomain .
                $currentNetwork->path;
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
    }

    $l10n = new L10N();
    $l10n->setData([ 'your_address' => $yourAddress ]);

    return $app->mute(
        () ==> {
            echo render(
                'signup/blog-form',
                [
                    'loggedIn' => is_user_logged_in(),
                    'currentNetwork' => $currentNetwork,
                    'siteDomain' => $siteDomain,
                    'blogname' => $blogname,
                    'blogTitle' => $blogTitle,
                    'languageDropdown' => $dropdown,
                    'isSubdomainInstall' => is_subdomain_install(),
                    'errors' => $messages,
                    'l10n' => $l10n,
                    'yesChecked' => checked(
                        ! $_post->has('blog_public')
                        || $_post->getInt('blog_public') === 1,
                        true,
                        false
                    ),
                    'noChecked' => checked(
                        $_post->has('blog_public')
                        && $_post->getInt('blog_public') === 0,
                        true,
                        false
                    ),
                ]
            );

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
    );
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

    $currentUser = wp_get_current_user();
    if (! is_user_logged_in()) {
        die();
    }

    $result = wpmu_validate_blog_signup(
        $_post->get('blogname'),
        $_post->get('blog_title'),
        is_user_logged_in() ? wp_get_current_user() : ''
    );

    if ($result['errors']->get_error_code()) {
        $l10n = new L10N();

        $defaults = [
            'blogname'   => $result['blogname'],
            'blog_title' => $result['blog_title'],
            'errors'     => $result['errors']
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
                $currentUser->display_name
            ),
        ];
        $l10n->setData($strings);

        $blogs = [];
        $userBlogs = get_blogs_of_user($currentUser->ID);
        foreach ($userBlogs as $userBlog) {
            $url = get_home_url($userBlog->userblog_id);
            $blogs[] = [
                'url' => $url,
                'escUrl' => esc_url($url),
            ];
        }

        $view = new View($app);
        $view->setActions(
            [
                /**
                 * Hidden sign-up form fields output when creating
                 * another site or user.
                 *
                 * @param string $context A string describing the steps of the
                 *                        sign-up process. The value can be:
                 *                        'create-another-site', 'validate-user', or
                 *                        'validate-site'.
                 *
                 * @since MU
                 */
                'signup_hidden_fields' => ['create-another-site'],
            ]
        );
        $view->setData(
            [
                'blogForm' => renderBlogForm(
                    $filtered['blogname'],
                    $filtered['blog_title'],
                    $filtered['errors']
                ),
                'hasBlogs' => count($blogs) > 0,
                'blogs' => $blogs,
                'errors' => $filtered['errors']->get_error_code(),
                'l10n' => $l10n,
            ]
        );

        echo $view->render('signup/another-blog');
        return false;
    }

    $blog_meta_defaults = [
        'lang_id' => 1,
        'public'  => $_post->getInt('blog_public'),
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
        $currentUser->ID,
        $meta,
        $app['db']->siteid
    );

    if (is_wp_error($blog_id)) {
        return false;
    }

    if ($blog_id) {
        switch_to_blog($blog_id);
        $homeUrl  = home_url('/');
        $loginUrl = wp_login_url();
        restore_current_blog();
    } else {
        $homeUrl  = 'http://' . $result['domain'] . $result['path'];
        $loginUrl = $homeUrl . 'wp-login.php';
    }

    $site = sprintf(
        '<a href="%s">%s</a>',
        esc_url($homeUrl),
        $result['blog_title']
    );

    $l10n = new L10N();
    $strings = [
        'the_site_is_yours' => sprintf($l10n->the_site_is_yours, $site),
        'your_new_site' => sprintf(
            $l10n->your_new_site, // @codingStandardsIgnoreLine
            esc_url($homeUrl),
            untrailingslashit($result['domain'] . $result['path']),
            esc_url($loginUrl),
            $currentUser->user_login
        )
    ];
    $l10n->setData($strings);

    echo render(
        'signup/confirm-another-blog-signup',
        [
            'l10n' => $l10n
        ]
    );

    /**
     * Fires when the site or user sign-up process is complete.
     *
     * @since 3.0.0
     */
    do_action('signup_finished');
    return true;
}

/**
 * Setup the new user signup process
 *
 * @param string $userName  The username.
 * @param string $userEmail The user's email.
 * @param Error  $errors    A Error object containing existing errors.
 *
 * @since MU
 *
 * @return void
 */
function signup_user( // @codingStandardsIgnoreLine
    $userName = '',
    $userEmail = '',
    $errors = null
) {
    $app = getApp();
    $active_signup = $app->get('active_signup');

    $_post = $app['request']->request;
    $signup_for = esc_html($_post->get('signup_for', 'blog'));

    $messages = [];

    if (null === $errors || ! is_wp_error($errors)) {
        $errors = new Error();
    } else {
        $messages['name'] = $errors->get_error_message('user_name');
        $messages['email'] = $errors->get_error_message('user_email');
        $messages['generic'] = $errors->get_error_message('generic');
    }

    $defaults = array(
        'user_name'  => $userName,
        'user_email' => $userEmail,
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
    $l10n->setData($strings);

    $view = new View($app);
    $view->setActions(
        [
            'signup_hidden_fields' => ['validate-user'],
            /**
             * Fires at the end of the user registration form
             * on the site sign-up form.
             *
             * @param Error $errors A Error object containing containing
             *                      'user_name' or 'user_email' errors.
             *
             * @since 3.0.0
             */
            'signup_extra_fields' => [$errors],
        ]
    );
    $view->setData(
        [
            'errors' => $messages,
            'userName' => $userName,
            'userEmail' => $userEmail,
            'l10n' => $l10n,
            'signupForUser' => $active_signup === 'user',
            'signupForBlog' => $active_signup === 'blog',
            'blogChecked' => checked($signup_for, 'blog', false),
            'userChecked' => checked($signup_for, 'user', false),
        ]
    );

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
    $app = getApp();
    $_post = $app['request']->request;
    $result = wpmu_validate_user_signup(
        $_post->get('user_name'),
        $_post->get('user_email')
    );

    if ($result['errors']->get_error_code()) {
        signup_user(
            $result['user_name'],
            $result['user_email'],
            $result['errors']
        );
        return false;
    }

    if ('blog' === $_post->get('signup_for')) {
        signup_blog(
            $result['user_name'],
            $result['user_email']
        );
        return false;
    }

    wpmu_signup_user(
        $result['user_name'],
        $result['user_email'],
        // This filter is documented in wp-signup.hh
        apply_filters('add_signup_meta', [])
    );

    $l10n = new L10N();
    $strings = [
        'is_your_username' => sprintf($l10n->is_your_username, $result['user_name']),
        'check_your_inbox' => sprintf(
            $l10n->check_your_inbox,
            '<strong>' . $result['user_email'] . '</strong>'
        ),
    ];
    $l10n->setData($strings);

    echo render(
        'signup/confirm-user-signup',
        [
            'l10n' => $l10n,
        ]
    );
    // This action is documented in wp-signup.hh
    do_action('signup_finished');
    return true;
}

/**
 * Setup the new site signup
 *
 * @param string $userName  The username.
 * @param string $userEmail The user's email address.
 * @param string $blogname  The site name.
 * @param string $blogTitle The site title.
 * @param Error  $errors    A Error object containing existing errors.
 *
 * @since MU
 *
 * @return void
 */
function signup_blog( // @codingStandardsIgnoreLine
    $userName = '',
    $userEmail = '',
    $blogname = '',
    $blogTitle = '',
    $errors = null
) {
    if (null === $errors || !is_wp_error($errors)) {
        $errors = new Error();
    }

    $defaults = [
        'user_name'  => $userName,
        'user_email' => $userEmail,
        'blogname'   => $blogname,
        'blog_title' => $blogTitle,
        'errors'     => $errors
    ];

    /**
     * Filters the default site creation variables for the site sign-up form.
     *
     * @param array $defaults {
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
    $filtered = apply_filters('signup_blog_init', $defaults);

    $blogname = $filtered['blogname'];
    if (empty($blogname)) {
        $blogname = $filtered['user_name'];
    }

    $app = getApp();

    $view = new View($app);
    $view->setActions(
        [
            'signup_hidden_fields' => ['validate-site'],
        ]
    );
    $view->setData(
        [
            'userName' => $filtered['user_name'],
            'userEmail' => $filtered['user_email'],
            'l10n' => [
                'signup' => __('Signup'),
            ],
            'blogForm' => renderBlogForm(
                $blogname,
                $filtered['blog_title'],
                $filtered['errors']
            ),
        ]
    );
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

    $l10n = new L10N();
    $strings = [
        'congratulations' => sprintf(
            $l10n->congratulations,
            sprintf(
                '<a href="http://%s%s">%s</a>',
                $result['domain'],
                $result['path'],
                $result['blog_title']
            )
        ),
        'check_your_inbox' => sprintf(
            $l10n->check_your_inbox,
            '<strong>' . $user_result['user_email'] . '</strong>'
        ),
        'have_you_entered' => sprintf(
            $l10n->have_you_entered,
            $user_result['user_email']
        )
    ];
    $l10n->setData($strings);

    echo render(
        'signup/confirm-blog-signup',
        [
            'l10n' => $l10n,
        ]
    );
    // This action is documented in wp-signup-functions.hh
    do_action('signup_finished');
    return true;
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
