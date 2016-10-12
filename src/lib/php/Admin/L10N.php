<?php
namespace WP\Admin;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			// Layout
			'main_content' => __( 'Main content' ),

			// Screen
			'pagination' => __( 'Pagination' ),
			'view_mode' => __( 'View Mode' ),
			'list_view' => __( 'List View' ),
			'excerpt_view' => __( 'Excerpt View' ),
			'layout' => __( 'Layout' ),
			'contextual_help_tab' => __( 'Contextual Help Tab' ),
			'help' => __( 'Help' ),
			'screen_options' => __( 'Screen Options' ),
			'boxes' => __( 'Boxes' ),
			'welcome' => _x( 'Welcome', 'Welcome panel' ),

			// Freedoms
			'freedoms' => __( 'Freedoms' ),
			'welcome' => __( 'Welcome to WordPress %s' ),
			'about' => __( 'Thank you for updating to the latest version. WordPress %s changes a lot behind the scenes to make your WordPress experience even better!' ),
			'version' => __( 'Version %s' ),
			'whats_new' => __( 'What&#8217;s New' ),
			'credits' => __( 'Credits' ),
			'freedoms_about' => __( 'WordPress is Free and open source software, built by a distributed community of mostly volunteer developers from around the world. WordPress comes with some awesome, worldview-changing rights courtesy of its <a href="%s">license</a>, the GPL.' ),
			'freedoms_list' => [
				__( 'You have the freedom to run the program, for any purpose.' ),
				__( 'You have access to the source code, the freedom to study how the program works, and the freedom to change it to make it do what you wish.' ),
				__( 'You have the freedom to redistribute copies of the original program so you can help your neighbor.' ),
				__( 'You have the freedom to distribute copies of your modified versions to others. By doing this you can give the whole community a chance to benefit from your changes.' ),
			],
			'trademark_policy' => __( 'WordPress grows when people like you tell their friends about it, and the thousands of businesses and services that are built on and around WordPress share that fact with their users. We&#8217;re flattered every time someone spreads the good word, just make sure to <a href="%s">check out our trademark guidelines</a> first.' ),
			'dotorg_plugins_url' => __( 'https://wordpress.org/plugins/' ),
			'dotorg_themes_url' => __( 'https://wordpress.org/themes/' ),
			'license' => __( 'Every plugin and theme in WordPress.org&#8217;s directory is 100%% GPL or a similarly free and compatible license, so you can feel safe finding <a href="%1$s">plugins</a> and <a href="%2$s">themes</a> there. If you get a plugin or theme from another source, make sure to <a href="%3$s">ask them if it&#8217;s GPL</a> first. If they don&#8217;t respect the WordPress license, we don&#8217;t recommend them.' ),
			'free_softare_foundation' => __( 'Don&#8217;t you wish all software came with these freedoms? So do we! For more information, check out the <a href="https://www.fsf.org/">Free Software Foundation</a>.' ),
		];
	}
}
