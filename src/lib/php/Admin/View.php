<?php
namespace WP\Admin;

use WP\{App,View as BaseView};

class View extends BaseView {
	public $l10n;
	public $help;
	public $handler;

	public function __construct( App $app ) {
		$this->app = $app;

		$this->actions = [
			'in_admin_footer' => [],
			'admin_footer' => [ '' ],
			"admin_print_footer_scripts-{$app->hook_suffix}" => [],
			'admin_print_footer_scripts' => [],
			"admin_footer-{$app->hook_suffix}" => [],
		];

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
}