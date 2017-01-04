<?hh
namespace WP\Registration;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			'activate' => __('Activate'),
			'activation_key_required' => __('Activation Key Required'),
			'activation_key_label' => __('Activation Key:'),
			'now_active' => __('Your account is now active!'),
			'error_occurred' => __('An error occurred during the activation'),
			'username' => __('Username:'),
			'name_rules' => __('(Must be at least 4 characters, letters and numbers only.)'),
			'password' => __('Password:'),
			'email_address' => __('Email&nbsp;Address:'),
			'double_check' => __('We send your registration email to this address. (Double-check your email address before continuing.)'),
			/* translators: %s: site address */
			'congratulations' => __('Congratulations! Your new site, %s, is almost ready.'),
			// @codingStandardsIgnoreLine
			'before_you_can_start' => __('But, before you can start using your site, <strong>you must activate it</strong>.'),
			/* translators: %s: email address */
			'check_your_inbox' => __('Check your inbox at %s and click the link given.'),
			 // @codingStandardsIgnoreLine
			'within_two_days' => __('If you do not activate your site within two days, you will have to sign up again.'),
			'still_waiting' => __('Still waiting for your email?'),
			 // @codingStandardsIgnoreLine
			'havent_received' => __('If you haven&#8217;t received your email yet, there are a number of things you can do:'),
			 // @codingStandardsIgnoreLine
			'wait_a_little' => __('Wait a little longer. Sometimes delivery of email can be delayed by processes outside of our control.'),
			// @codingStandardsIgnoreLine
			'check_the_junk' => __('Check the junk or spam folder of your email client. Sometime emails wind up there by mistake.'),
			/* translators: %s: email address */
			// @codingStandardsIgnoreLine
			'have_you_entered' => __('Have you entered your email correctly? You have entered %s, if it&#8217;s incorrect, you will not receive your email.'),
			'get_another_site' => __('Get <em>another</em> %s site in seconds'),
			// @codingStandardsIgnoreLine
			'there_was_a_problem' => __('There was a problem, please correct the form below and try again.'),
			// @codingStandardsIgnoreLine
			'welcome_back' => __('Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart&#8217;s content, but write responsibly!'),
			'member_sites' => __('Sites you are already a member of:'),
			// @codingStandardsIgnoreLine
			'great_site_domain' => __('If you&#8217;re not going to use a great site domain, leave it for a new user. Now have at it!'),
			'create_site' => __('Create Site'),
			'the_site_is_yours' => __('The site %s is yours.'),
			/* translators:
                1: home URL,
                2: site address,
                3: login URL,
                4: username */
			// @codingStandardsIgnoreLine
			'your_new_site' => __('<a href="%1$s">%2$s</a> is your new site. <a href="%3$s">Log in</a> as &#8220;%4$s&#8221; using your existing password.'),
			'get_your_own_account' => __('Get your own %s account in seconds'),
			'gimme_a_site' => __('Gimme a site!'),
			'just_a_username' => __('Just a username, please.'),
			'next' => __('Next'),
			'no' => __('No'),
			'yes' => __('Yes'),
			'privacy' => __('Privacy:'),
			// @codingStandardsIgnoreLine
			'allow_search_engines' => __('Allow search engines to index this site.'),
			// @codingStandardsIgnoreLine
            'extended_name_rules' => __('Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!'),
            'site_title' => __('Site Title:'),
            'site_language' => __('Site Language:'),
			'site_domain' => __('Site Domain:'),
			'site_name' => __('Site Name:'),
			/* translators: %s: username */
			'is_your_username' => __('%s is your new username'),
			// @codingStandardsIgnoreLine
			'before_you_can_start' => __('But, before you can start using your new username, <strong>you must activate it</strong>.'),
			/* translators: %s: email address */
			'check_your_inbox' => __('Check your inbox at %s and click the link given.'),
			// @codingStandardsIgnoreLine
			'do_not_activate' => __('If you do not activate your username within two days, you will have to sign up again.'),
 		];
	}
}
