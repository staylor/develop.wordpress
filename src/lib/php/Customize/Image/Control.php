<?php
namespace WP\Customize\Image;
/**
 * Customize API: Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Manager;
use WP\Customize\Upload\Control as UploadControl;

/**
 * Customize Image Control class.
 *
 * @since 3.4.0
 */
class Control extends UploadControl {
	public $type = 'image';
	public $mime_type = 'image';

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 * @uses UploadControl::__construct()
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 * @param string  $id      Control ID.
	 * @param array   $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( Manager $manager, $id, $args = [] ) {
		parent::__construct( $manager, $id, $args );

		$this->button_labels = wp_parse_args( $this->button_labels, [
			'select'       => __( 'Select Image' ),
			'change'       => __( 'Change Image' ),
			'remove'       => __( 'Remove' ),
			'default'      => __( 'Default' ),
			'placeholder'  => __( 'No image selected' ),
			'frame_title'  => __( 'Select Image' ),
			'frame_button' => __( 'Choose Image' ),
		] );
	}
}
