<?php
namespace WP\Customize\BackgroundImage;
/**
 * Customize Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Manager;
use WP\Customize\Image\Control as ImageControl;

/**
 * Customize Background Image Control class.
 *
 * @since 3.4.0
 */
class Control extends ImageControl {
	public $type = 'background';

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 * @uses ImageControl::__construct()
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 */
	public function __construct( Manager $manager ) {
		parent::__construct( $manager, 'background_image', [
			'label'    => __( 'Background Image' ),
			'section'  => 'background_image',
		] );
	}

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 4.1.0
	 */
	public function enqueue() {
		parent::enqueue();

		wp_localize_script( 'customize-controls', '_wpCustomizeBackground', [
			'nonces' => [
				'add' => wp_create_nonce( 'background-add' ),
			],
		] );
	}
}
