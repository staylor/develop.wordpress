<?php
namespace WP\Admin;

use WP\{App,View as BaseView};

class View extends BaseView {
	public $help;
	public $handler;

	public $html_type;
	public $charset;

	public function __construct( App $app ) {
		$this->app = $app;

		if ( empty( $this->app->current_screen ) ) {
			set_current_screen();
		}

		$this->l10n = new L10N();
		$hook_suffix = $this->app->get( 'hook_suffix' );

		$this->setAdminActions( $hook_suffix );

		$this->setAdminData( $hook_suffix );

		$this->enqueueAdminScripts();

		// Cookies
		wp_user_settings();

		$this->html_type = get_option( 'html_type' );
		$this->charset = get_option( 'blog_charset' );
	}

	public function enqueueAdminScripts() {
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'ie' );
		wp_enqueue_script( 'utils' );
		wp_enqueue_script( 'svg-painter' );
	}

	public function setAdminData( $hook_suffix ) {
		$title = esc_html( strip_tags( get_admin_page_title() ) );

		$admin_page = preg_replace( '/[^a-z0-9_-]+/i', '-', $hook_suffix );

		$admin_footer_text = sprintf(
			__( 'Thank you for creating with <a href="%s">WordPress</a>.' ), __( 'https://wordpress.org/' )
		);

		$this->setData( [
			'title' => $title,

			'screen' => $this->app->current_screen,

			'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),

			'hook_suffix' => $hook_suffix,

			'thousands_sep' => addslashes( $this->app['locale']->number_format['thousands_sep'] ),

			'decimal_point' => addslashes( $this->app['locale']->number_format['decimal_point'] ),

			'is_rtl' => (int) is_rtl(),

			'language_attributes' => get_language_attributes(),

			'admin_page' => $admin_page,

			'admin_title' => $this->getAdminTitle( $title ),

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

			'admin_body_class' => $this->getAdminBodyClass( $admin_page ),

			'admin_html_class' => ( is_admin_bar_showing() ) ? 'wp-toolbar' : '',

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
			'update_footer' => apply_filters( 'update_footer', '' ),

			'customize_support' => current_user_can( 'customize' ) ? $this->app->mute( 'wp_customize_support_script' ) : '',

			'screen_meta' => $this->app->mute( function () {
				$this->app->current_screen->render_screen_meta();
			} ),
		] );
	}

	public function setL10n( $l10n ) {
		$this->l10n->setData( $l10n->getData() );

		$this->setConfig( [
			'helpers' => [
				'l10n' => $this->l10n,
			]
		] );
	}

	public function compressionTest() {
		// get_site_option() won't exist when auto upgrading from <= 2.7
		if ( function_exists( 'get_site_option' ) && false === get_site_option( 'can_compress_scripts' ) ) {
			compression_test();
		}
	}

	/**
	 * @return bool
	 */
	public function is_network_admin() {
		return is_network_admin();
	}

	/**
	 * @return bool
	 */
	public function is_user_admin() {
		return is_user_admin();
	}

	public function setAdminActions( $hook_suffix ) {
		$admin_head_hook = sprintf( 'admin_head-%s', $hook_suffix );
		$admin_print_styles_hook = sprintf( 'admin_print_styles-%s', $hook_suffix );
		$admin_print_scripts_hook = sprintf( 'admin_print_scripts-%s', $hook_suffix );
		$admin_print_footer_scripts_hook = sprintf( 'admin_print_footer_scripts-%s', $hook_suffix );
		$admin_footer_hook = sprintf( 'admin_footer-%s', $hook_suffix );

		$this->actions = [
			/**
			 * Fires inside the HTML tag in the admin header.
			 *
			 * @since 2.2.0
			 */
			'admin_xml_ns' => [],
			/**
			 * Enqueue scripts for all admin pages.
			 *
			 * @since 2.8.0
			 *
			 * @param string $hook_suffix The current admin page.
			 */
			'admin_enqueue_scripts'  => [ $hook_suffix ],
			/**
			 * Fires when styles are printed for a specific admin page based on $hook_suffix.
			 *
			 * @since 2.6.0
			 */
			$admin_print_styles_hook => [],
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
			$admin_print_scripts_hook => [],
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
			$admin_head_hook => [],
			/**
			 * Fires in head section for all admin pages.
			 *
			 * @since 2.1.0
			 */
			'admin_head' => [],
			/**
			 * Fires after the admin menu has been output.
			 *
			 * @since 2.5.0
			 */
			'adminmenu' => [],
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
			$admin_print_footer_scripts_hook => [],
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
			$admin_footer_hook => [],
		];
	}

	/**
	 * @param string $title
	 * @return string
	 */
	protected function getAdminTitle( $title ) {
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

	/**
	 * @param string $admin_page
	 * @return string
	 */
	protected function getAdminBodyClass( $admin_page ) {
		$admin_body_class = $admin_page;

		if ( get_user_setting( 'mfold' ) === 'f' ) {
			$admin_body_class .= ' folded';
		}

		if ( ! get_user_setting( 'unfold' ) ) {
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

		return $admin_body_class . ' no-customize-support no-svg';
	}

	public function menu() {
		static $menu = null;
		if ( ! $menu ) {
			$menu = new Menu( $this->app );
		}
		return $menu->compile();
	}

	public function render( $template, $data ) {
		header( 'Content-Type: ' . $this->html_type . '; charset=' . $this->charset );

		if ( $this->app['is_IE'] ) {
			header( 'X-UA-Compatible: IE=edge' );
		}

		return parent::render( $template, $data );
	}
}
