<?php
namespace WP\Admin;

use WP\{App,View as BaseView};

class View extends BaseView {
	public $l10n;
	public $help;
	public $handler;

	public function __construct( App $app ) {
		$this->app = $app;

		if ( empty( $this->app->current_screen ) ) {
			set_current_screen();
		}

		$this->setAdminActions();

		if ( get_called_class() === __CLASS__ ) {
			$this->setL10n( new L10N() );
		}
	}

	public function setL10n( $l10n ) {
		$this->l10n = $l10n;

		$admin_footer_text = sprintf(
			__( 'Thank you for creating with <a href="%s">WordPress</a>.' ), __( 'https://wordpress.org/' )
		);

		$this->setConfig( [
			'helpers' => [
				'l10n' => $l10n,

				'hook_suffix' => $this->app->hook_suffix,

				'admin_title' => $this->getAdminTitle(),

				/**
				 * Filters the CSS classes for the body tag in the admin.
				 *
				 * This filter differs from the {@see 'post_class'} and {@see 'body_class'} filters
				 * in two important ways:
				 *
				 * 1. `$classes` is a space-separated string of class names instead of an array.
				 * 2. Not all core admin classes are filterable, notably: wp-admin, wp-core-ui,
				 *    and no-js cannot be removed.
				 *
				 * @since 2.3.0
				 *
				 * @param string $classes Space-separated list of CSS classes.
				 */
				'admin_body_classes' => apply_filters( 'admin_body_class', '' ),

				'admin_body_class' => $this->getAdminBodyClass(),

				'screen_meta' => $this->app->mute( function () {
					$this->app->current_screen->render_screen_meta();
				} ),
				/**
				 * Filters the "Thank you" text displayed in the admin footer.
				 *
				 * @since 2.8.0
				 *
				 * @param string $text The content that will be printed.
				 */
				'admin_footer_text' => apply_filters( 'admin_footer_text', '<span id="footer-thankyou">' . $admin_footer_text . '</span>' ),
				/**
				 * Filters the version/update text displayed in the admin footer.
				 *
				 * WordPress prints the current version and update information,
				 * using core_update_footer() at priority 10.
				 *
				 * @since 2.3.0
				 *
				 * @see core_update_footer()
				 *
				 * @param string $content The content that will be printed.
				 */
				'update_footer' => apply_filters( 'update_footer', '' )
			]
		] );
	}

	public function compressionTest() {
		// get_site_option() won't exist when auto upgrading from <= 2.7
		if ( function_exists( 'get_site_option' ) && false === get_site_option( 'can_compress_scripts' ) ) {
			compression_test();
		}
	}

	public function is_network_admin() {
		return is_network_admin();
	}

	public function is_user_admin() {
		return is_user_admin();
	}

	public function setAdminActions() {
		$this->actions = [
			/**
			 * Enqueue scripts for all admin pages.
			 *
			 * @since 2.8.0
			 *
			 * @param string $hook_suffix The current admin page.
			 */
			'admin_enqueue_scripts'  => [ $this->app->hook_suffix ],
			/**
			 * Fires when styles are printed for a specific admin page based on $hook_suffix.
			 *
			 * @since 2.6.0
			 */
			"admin_print_styles-{$this->app->hook_suffix}" => [],
			/**
			 * Fires when styles are printed for all admin pages.
			 *
			 * @since 2.6.0
			 */
			'admin_print_styles' => [],
			/**
			 * Fires when scripts are printed for a specific admin page based on $hook_suffix.
			 *
			 * @since 2.1.0
			 */
			"admin_print_scripts-{$this->app->hook_suffix}" => [],
			/**
			 * Fires when scripts are printed for all admin pages.
			 *
			 * @since 2.1.0
			 */
			'admin_print_scripts' => [],
			/**
			 * Fires in head section for a specific admin page.
			 *
			 * The dynamic portion of the hook, `$hook_suffix`, refers to the hook suffix
			 * for the admin page.
			 *
			 * @since 2.1.0
			 */
			"admin_head-{$this->app->hook_suffix}" => [],
			/**
			 * Fires in head section for all admin pages.
			 *
			 * @since 2.1.0
			 */
			'admin_head' => [],
			/**
			 * Fires at the beginning of the content section in an admin page.
			 *
			 * @since 3.0.0
			 */
			'in_admin_header' => [],
			/**
			 * Prints network admin screen notices.
			 *
			 * @since 3.1.0
			 */
			'network_admin_notices' => [],
			/**
			 * Prints user admin screen notices.
			 *
			 * @since 3.1.0
			 */
			'user_admin_notices' => [],
			/**
			 * Prints admin screen notices.
			 *
			 * @since 3.1.0
			 */
			'admin_notices' => [],
			/**
			 * Prints generic admin screen notices.
			 *
			 * @since 3.1.0
			 */
			'all_admin_notices' => [],
			/**
			 * Fires after the opening tag for the admin footer.
			 *
			 * @since 2.5.0
			 */
			'in_admin_footer' => [],
			/**
			 * Prints scripts or data before the default footer scripts.
			 *
			 * @since 1.2.0
			 *
			 * @param string $data The data to print.
			 */
			'admin_footer' => [ '' ],
			/**
			 * Prints scripts and data queued for the footer.
			 *
			 * The dynamic portion of the hook name, `$hook_suffix`,
			 * refers to the global hook suffix of the current page.
			 *
			 * @since 4.6.0
			 *
			 * @param string $hook_suffix The current admin page.
			 */
			"admin_print_footer_scripts-{$this->app->hook_suffix}" => [],
			/**
			 * Prints any scripts and data queued for the footer.
			 *
			 * @since 2.8.0
			 */
			'admin_print_footer_scripts' => [],
			/**
			 * Prints scripts or data after the default footer scripts.
			 *
			 * The dynamic portion of the hook name, `$hook_suffix`,
			 * refers to the global hook suffix of the current page.
			 *
			 * @since 2.8.0
			 *
			 * @param string $hook_suffix The current admin page.
			 */
			"admin_footer-{$this->app->hook_suffix}" => [],
		];
	}

	protected function getAdminTitle() {
		$title = esc_html( strip_tags( get_admin_page_title() ) );

		if ( is_network_admin() ) {
			$admin_title = sprintf( __( 'Network Admin: %s' ), esc_html( get_current_site()->site_name ) );
		} elseif ( is_user_admin() ) {
			$admin_title = sprintf( __( 'User Dashboard: %s' ), esc_html( get_current_site()->site_name ) );
		} else {
			$admin_title = get_bloginfo( 'name' );
		}

		if ( $admin_title == $title ) {
			$admin_title = sprintf( __( '%1$s &#8212; WordPress' ), $title );
		} else {
			$admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), $title, $admin_title );
		}
		/**
		 * Filters the title tag content for an admin page.
		 *
		 * @since 3.1.0
		 *
		 * @param string $admin_title The page title, with extra context added.
		 * @param string $title       The original page title.
		 */
		return apply_filters( 'admin_title', $admin_title, $title );
	}

	protected function getAdminBodyClass() {
		$admin_body_class = preg_replace('/[^a-z0-9_-]+/i', '-', $this->app->hook_suffix );

		if ( get_user_setting('mfold') == 'f' ) {
			$admin_body_class .= ' folded';
		}

		if ( ! get_user_setting('unfold') ) {
			$admin_body_class .= ' auto-fold';
		}

		if ( is_admin_bar_showing() ) {
			$admin_body_class .= ' admin-bar';
		}

		if ( is_rtl() ) {
			$admin_body_class .= ' rtl';
		}

		if ( $this->app->current_screen->post_type ) {
			$admin_body_class .= ' post-type-' . $this->app->current_screen->post_type;
		}

		if ( $this->app->current_screen->taxonomy ) {
			$admin_body_class .= ' taxonomy-' . $this->app->current_screen->taxonomy;
		}

		$admin_body_class .= ' branch-' . str_replace( [ '.', ',' ], '-', floatval( $this->app['wp_version'] ) );
		$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', $this->app['wp_version'] ) );
		$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
		$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

		if ( wp_is_mobile() ) {
			$admin_body_class .= ' mobile';
		}

		if ( is_multisite() ) {
			$admin_body_class .= ' multisite';
		}

		if ( is_network_admin() ) {
			$admin_body_class .= ' network-admin';
		}

		$admin_body_class .= ' no-customize-support no-svg';

		return $admin_body_class;
	}

	public function render( string $template, $data ): string {
		header( 'Content-Type: ' . get_option('html_type') . '; charset=' . get_option( 'blog_charset' ) );

		return parent::render( $template, $data );
	}
}