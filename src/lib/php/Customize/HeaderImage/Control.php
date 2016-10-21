<?php
namespace WP\Customize\HeaderImage;
/**
 * Customize API: HeaderImage class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Manager;
use WP\Customize\Image\Control as ImageControl;
use function WP\getApp;

/**
 * Customize Header Image Control class.
 *
 * @since 3.4.0
 */
class Control extends ImageControl {
	public $type = 'header';
	public $uploaded_headers;
	public $default_headers;

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 */
	public function __construct( Manager $manager ) {
		parent::__construct( $manager, 'header_image', [
			'label'    => __( 'Header Image' ),
			'settings' => [
				'default' => 'header_image',
				'data'    => 'header_image_data',
			],
			'section'  => 'header_image',
			'removed'  => 'remove-header',
			'get_url'  => 'get_header_image',
		] );

	}

	/**
	 * @access public
	 */
	public function enqueue() {
		wp_enqueue_media();
		wp_enqueue_script( 'customize-views' );

		$this->prepare_control();

		wp_localize_script( 'customize-views', '_wpCustomizeHeader', [
			'data' => [
				'width' => absint( get_theme_support( 'custom-header', 'width' ) ),
				'height' => absint( get_theme_support( 'custom-header', 'height' ) ),
				'flex-width' => absint( get_theme_support( 'custom-header', 'flex-width' ) ),
				'flex-height' => absint( get_theme_support( 'custom-header', 'flex-height' ) ),
				'currentImgSrc' => $this->get_current_image_src(),
			],
			'nonces' => [
				'add' => wp_create_nonce( 'header-add' ),
				'remove' => wp_create_nonce( 'header-remove' ),
			],
			'uploads' => $this->uploaded_headers,
			'defaults' => $this->default_headers
		] );

		parent::enqueue();
	}

	public function prepare_control() {
		$app = getApp();
		$header = $app->theme['custom_image_header'];

		if ( empty( $header ) ) {
			return;
		}

		// Process default headers and uploaded headers.
		$header->process_default_headers();
		$this->default_headers = $header->get_default_header_images();
		$this->uploaded_headers = $header->get_uploaded_header_images();
	}

	/**
	 * @access public
	 */
	public function print_header_image_template() {
		$path = 'customize/control/header-image/template/';
		echo $this->manager->app['mustache']->render( $path . 'choice', [
			'l10n' => [
				'remove_image' => __( 'Remove image' ),
				'set_image' => __( 'Set image' ),
				'randomize_uploaded_headers' => __( 'Randomize uploaded headers' ),
				'randomize_suggested_headers' => __( 'Randomize suggested headers' ),
			]
		] );

		echo $this->manager->app['mustache']->render( $path . 'current', [
			'l10n' => [
				'no_image_set' => __( 'No image set.' ),
				'randomizing_uploaded_headers' => __( 'Randomizing uploaded headers' ),
				'randomizing_suggested_headers' => __( 'Randomizing suggested headers' ),
			]
		] );
	}

	/**
	 * @return string|void
	 */
	public function get_current_image_src() {
		$src = $this->value();
		if ( isset( $this->get_url ) ) {
			$src = call_user_func( $this->get_url, $src );
			return $src;
		}
	}

	/**
	 * @access public
	 */
	public function render_content() {
		$this->print_header_image_template();
		$visibility = $this->get_current_image_src() ? '' : ' style="display:none" ';
		$width = absint( get_theme_support( 'custom-header', 'width' ) );
		$height = absint( get_theme_support( 'custom-header', 'height' ) );

		if ( $width && $height ) {
			/* translators: %s: header size in pixels */
			$intro = sprintf( __( 'While you can crop images to your liking after clicking <strong>Add new image</strong>, your theme recommends a header size of %s pixels.' ),
				sprintf( '<strong>%s &times; %s</strong>', $width, $height )
			);
		} elseif ( $width ) {
			/* translators: %s: header width in pixels */
			$intro = sprintf( __( 'While you can crop images to your liking after clicking <strong>Add new image</strong>, your theme recommends a header width of %s pixels.' ),
				sprintf( '<strong>%s</strong>', $width )
			);
		} else {
			/* translators: %s: header height in pixels */
			$intro = sprintf( __( 'While you can crop images to your liking after clicking <strong>Add new image</strong>, your theme recommends a header height of %s pixels.' ),
				sprintf( '<strong>%s</strong>', $height )
			);
		}

		echo $this->manager->app['mustache']->render( 'customize/control/header-image/content', [
			'intro' => $intro,
			'visibility' => $visibility,
			'l10n' => [
				'current_header' => __( 'Current header' ),
				'hide_header_image' => __( 'Hide header image' ),
				'hide_image' => __( 'Hide image' ),
				'add_new_header_image' => __( 'Add new header image' ),
				'add_new_image' => __( 'Add new image' ),
				'previously_updated' => _x( 'Previously uploaded', 'custom headers' ),
				'suggested' => _x( 'Suggested', 'custom headers' ),
			],
			'caps' => [
				'upload_files' => current_user_can( 'upload_files' ),
			]
		] );
	}
}
