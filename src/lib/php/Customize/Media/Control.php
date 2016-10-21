<?php
namespace WP\Customize\Media;
/**
 * Customize API: Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Image\Control as ImageControl;
use WP\Customize\{Manager,Control as BaseControl};

/**
 * Customize Media Control class.
 *
 * @since 4.2.0
 */
class Control extends BaseControl {
	/**
	 * Control type.
	 *
	 * @since 4.2.0
	 * @access public
	 * @var string
	 */
	public $type = 'media';

	/**
	 * Media control mime type.
	 *
	 * @since 4.2.0
	 * @access public
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Button labels.
	 *
	 * @since 4.2.0
	 * @access public
	 * @var array
	 */
	public $button_labels = [];

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 * @since 4.2.0 Moved from \WP\Customize\Upload\Control.
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( Manager $manager, $id, $args = [] ) {
		parent::__construct( $manager, $id, $args );

		if ( ! ( $this instanceof ImageControl ) ) {
			$this->button_labels = wp_parse_args( $this->button_labels, array(
				'select'       => __( 'Select File' ),
				'change'       => __( 'Change File' ),
				'default'      => __( 'Default' ),
				'remove'       => __( 'Remove' ),
				'placeholder'  => __( 'No file selected' ),
				'frame_title'  => __( 'Select File' ),
				'frame_button' => __( 'Choose File' ),
			) );
		}
	}

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Moved from \WP\Customize\Upload\Control.
	 */
	public function enqueue() {
		wp_enqueue_media();
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Moved from \WP\Customize\Upload\Control.
	 */
	public function to_json() {
		parent::to_json();
		$this->json['label'] = html_entity_decode( $this->label, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$this->json['mime_type'] = $this->mime_type;
		$this->json['button_labels'] = $this->button_labels;
		$this->json['canUpload'] = current_user_can( 'upload_files' );

		$value = $this->value();

		if ( is_object( $this->setting ) ) {
			if ( $this->setting->default ) {
				// Fake an attachment model - needs all fields used by template.
				// Note that the default value must be a URL, NOT an attachment ID.
				$type = in_array( substr( $this->setting->default, -3 ), array( 'jpg', 'png', 'gif', 'bmp' ) ) ? 'image' : 'document';
				$default_attachment = array(
					'id' => 1,
					'url' => $this->setting->default,
					'type' => $type,
					'icon' => wp_mime_type_icon( $type ),
					'title' => basename( $this->setting->default ),
				);

				if ( 'image' === $type ) {
					$default_attachment['sizes'] = array(
						'full' => array( 'url' => $this->setting->default ),
					);
				}

				$this->json['defaultAttachment'] = $default_attachment;
			}

			if ( $value && $this->setting->default && $value === $this->setting->default ) {
				// Set the default as the attachment.
				$this->json['attachment'] = $this->json['defaultAttachment'];
			} elseif ( $value ) {
				$this->json['attachment'] = wp_prepare_attachment_for_js( $value );
			}
		}
	}

	/**
	 * Don't render any content for this control from PHP.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Moved from \WP\Customize\Upload\Control.
	 *
	 * @see \WP\Customize\Media\Control::content_template()
	 */
	public function render_content() {}

	/**
	 * Render a JS template for the content of the media control.
	 *
	 * @since 4.1.0
	 * @since 4.2.0 Moved from \WP\Customize\Upload\Control.
	 */
	public function content_template() {
		echo $this->manager->app['mustache']->render( 'customize/control/media/content', [] );
	}
}
