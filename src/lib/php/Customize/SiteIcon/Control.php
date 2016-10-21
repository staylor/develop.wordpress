<?php
namespace WP\Customize\SiteIcon;
/**
 * Customize API: Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Manager;
use WP\Customize\CroppedImage\Control as CroppedImageControl;

/**
 * Customize Site Icon control class.
 *
 * Used only for custom functionality in JavaScript.
 *
 * @since 4.3.0
 */
class Control extends CroppedImageControl {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @access public
	 * @var string
	 */
	public $type = 'site_icon';

	/**
	 * Constructor.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 * @param string  $id      Control ID.
	 * @param array   $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( Manager $manager, $id, $args = [] ) {
		parent::__construct( $manager, $id, $args );
		add_action( 'customize_controls_print_styles', 'wp_site_icon', 99 );
	}

	/**
	 * Renders a JS template for the content of the site icon control.
	 *
	 * @since 4.5.0
	 * @access public
	 */
	public function content_template() {
		$app = $this->manager->app;
		$asset = $app['asset.admin'];
		$src = $asset->getUrl( 'images/' . ( is_rtl() ? 'browser-rtl.png' : 'browser.png' ) );

		echo $app['mustache']->render( 'customize/control/site-icon/content', [
			'blogname' => get_bloginfo( 'name' ),
			'browser_preview_src' => esc_url( $src ),
			'button_label' => $this->button_labels,
			'l10n' => [
				'preview_as_browser_icon' => __( 'Preview as a browser icon' ),
				'preview_as_app_icon' => __( 'Preview as an app icon' ),
			]
		] );
	}
}
