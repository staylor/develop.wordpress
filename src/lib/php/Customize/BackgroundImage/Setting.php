<?php
namespace WP\Customize\BackgroundImage;
/**
 * Customize API: Setting class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */
use WP\Customize\Setting as BaseSetting;
/**
 * Customizer Background Image Setting class.
 *
 * @since 3.4.0
 */
class Setting extends BaseSetting {
	public $id = 'background_image_thumb';

	/**
	 * @since 3.4.0
	 *
	 * @param $value
	 */
	public function update( $value ) {
		remove_theme_mod( 'background_image_thumb' );
	}
}
