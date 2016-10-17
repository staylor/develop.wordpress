<?php
namespace WP\Option\Admin;

use WP\Admin\Help as AdminHelp;

class Help extends AdminHelp {

	public function addWriting() {
		$this->addOverview(
			'<p>' . __( 'You can submit content in several different ways; this screen holds the settings for all of them. The top section controls the editor within the dashboard, while the rest control external publishing methods. For more information on any of these methods, use the documentation links.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>'
		);

		/** This filter is documented in wp-admin/options.php */
		if ( apply_filters( 'enable_post_by_email_configuration', true ) ) {
			$this->screen->add_help_tab( [
				'id'      => 'options-postemail',
				'title'   => __( 'Post Via Email' ),
				'content' => '<p>' . __( 'Post via email settings allow you to send your WordPress install an email with the content of your post. You must set up a secret email account with POP3 access to use this, and any mail received at this address will be posted, so it&#8217;s a good idea to keep this address very secret.' ) . '</p>',
			] );
		}

		/** This filter is documented in wp-admin/options-writing.php */
		if ( apply_filters( 'enable_update_services_configuration', true ) ) {
			$this->screen->add_help_tab( [
				'id'      => 'options-services',
				'title'   => __( 'Update Services' ),
				'content' => '<p>' . __( 'If desired, WordPress will automatically alert various services of your new posts.' ) . '</p>',
			] );
		}

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_Writing_Screen">Documentation on Writing Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addReading() {
		$this->addOverview(
			'<p>' . __( 'This screen contains the settings that affect the display of your content.' ) . '</p>' .
			'<p>' . sprintf(__( 'You can choose what&#8217;s displayed on the front page of your site. It can be posts in reverse chronological order (classic blog), or a fixed/static page. To set a static home page, you first need to create two <a href="%s">Pages</a>. One will become the front page, and the other will be where your posts are displayed.' ), 'post-new.php?post_type=page' ) . '</p>' .
			'<p>' . __( 'You can also control the display of your content in RSS feeds, including the maximum numbers of posts to display and whether to show full text or a summary.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'      => 'site-visibility',
			'title'   => has_action( 'blog_privacy_selector' ) ? __( 'Site Visibility' ) : __( 'Search Engine Visibility' ),
			'content' => '<p>' . __( 'You can choose whether or not your site will be crawled by robots, ping services, and spiders. If you want those services to ignore your site, click the checkbox next to &#8220;Discourage search engines from indexing this site&#8221; and click the Save Changes button at the bottom of the screen. Note that your privacy is not complete; your site is still visible on the web.' ) . '</p>' .
				'<p>' . __( 'When this setting is in effect, a reminder is shown in the At a Glance box of the Dashboard that says, &#8220;Search Engines Discouraged,&#8221; to remind you that your site is not being crawled.' ) . '</p>',
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_Reading_Screen">Documentation on Reading Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addPermalink() {
		$this->addOverview(
			'<p>' . __( 'Permalinks are the permanent URLs to your individual pages and blog posts, as well as your category and tag archives. A permalink is the web address used to link to your content. The URL to each post should be permanent, and never change &#8212; hence the name permalink.' ) . '</p>' .
			'<p>' . __( 'This screen allows you to choose your permalink structure. You can choose from common settings or create custom URL structures.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>'
		);

		$this->screen->add_help_tab( [
			'id'      => 'permalink-settings',
			'title'   => __( 'Permalink Settings' ),
			'content' => '<p>' . __( 'Permalinks can contain useful information, such as the post date, title, or other elements. You can choose from any of the suggested permalink formats, or you can craft your own if you select Custom Structure.' ) . '</p>' .
				'<p>' . __( 'If you pick an option other than Plain, your general URL path with structure tags (terms surrounded by <code>%</code>) will also appear in the custom structure field and your path can be further modified there.' ) . '</p>' .
				'<p>' . __( 'When you assign multiple categories or tags to a post, only one can show up in the permalink: the lowest numbered category. This applies if your custom structure includes <code>%category%</code> or <code>%tag%</code>.' ) . '</p>' .
				'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>',
		] );

		$this->screen->add_help_tab( [
			'id'      => 'custom-structures',
			'title'   => __( 'Custom Structures' ),
			'content' => '<p>' . __( 'The Optional fields let you customize the &#8220;category&#8221; and &#8220;tag&#8221; base names that will appear in archive URLs. For example, the page listing all posts in the &#8220;Uncategorized&#8221; category could be <code>/topics/uncategorized</code> instead of <code>/category/uncategorized</code>.' ) . '</p>' .
				'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>',
		] );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_Permalinks_Screen">Documentation on Permalinks Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Using_Permalinks">Documentation on Using Permalinks</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addMedia() {
		$media_options_help = '<p>' . __( 'You can set maximum sizes for images inserted into your written content; you can also insert an image as Full Size.' ) . '</p>';

		if ( ! is_multisite() && ( get_option( 'upload_url_path' ) || ( get_option( 'upload_path' ) != 'wp-content/uploads' && get_option( 'upload_path' ) ) ) ) {
			$media_options_help .= '<p>' . __( 'Uploading Files allows you to choose the folder and path for storing your uploaded files.' ) . '</p>';
		}

		$media_options_help .= '<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>';

		$this->addOverview( $media_options_help );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_Media_Screen">Documentation on Media Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addGeneral() {
		$options_help = '<p>' . __( 'The fields on this screen determine some of the basics of your site setup.' ) . '</p>' .
			'<p>' . __( 'Most themes display the site title at the top of every page, in the title bar of the browser, and as the identifying name for syndicated feeds. The tagline is also displayed by many themes.' ) . '</p>';

		if ( ! is_multisite() ) {
			$options_help .= '<p>' . __( 'The WordPress URL and the Site URL can be the same (example.com) or different; for example, having the WordPress core files (example.com/wordpress) in a subdirectory instead of the root directory.' ) . '</p>' .
				'<p>' . __( 'If you want site visitors to be able to register themselves, as opposed to by the site administrator, check the membership box. A default user role can be set for all new users, whether self-registered or registered by the site admin.' ) . '</p>';
		}

		$options_help .= '<p>' . __( 'You can set the language, and the translation files will be automatically downloaded and installed (available if your filesystem is writable).' ) . '</p>' .
			'<p>' . __( 'UTC means Coordinated Universal Time.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>';

		$this->addOverview( $options_help );

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_General_Screen">Documentation on General Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}

	public function addDiscussion() {
		$this->addOverview(
			'<p>' . __( 'This screen provides many options for controlling the management and display of comments and links to your posts/pages. So many, in fact, they won&#8217;t all fit here! :) Use the documentation links to get information on what each discussion setting does.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>'
		);

		$this->screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.wordpress.org/Settings_Discussion_Screen">Documentation on Discussion Settings</a>' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support Forums</a>' ) . '</p>'
		);
	}
}