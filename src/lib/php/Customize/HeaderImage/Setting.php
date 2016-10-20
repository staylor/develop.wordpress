<?php
namespace WP\Customize\HeaderImage;
/**
 * Customize API: Setting class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */
use WP\Customize\Setting as BaseSetting;
use function WP\getApp;

/**
 * A setting that is used to filter a value, but will not save the results.
 *
 * Results should be properly handled using another setting or callback.
 *
 * @since 3.4.0
 */
class Setting extends BaseSetting {
	public $id = 'header_image_data';

	/**
	 * @since 3.4.0
	 *
	 * @param $value
	 */
	public function update( $value ) {
		$app = getApp();
		// If the value doesn't exist (removed or random),
		// use the header_image value.
		if ( ! $value ) {
			$value = $this->manager->get_setting('header_image')->post_value();
		}

		if ( is_array( $value ) && isset( $value['choice'] ) ) {
			 $app->theme['custom_image_header']->set_header_image( $value['choice'] );
		} else {
			 $app->theme['custom_image_header']->set_header_image( $value );
		}
	}
}
