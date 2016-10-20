<?php
namespace WP\Customize\Upload;
/**
 * Customize API: Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\Media\Control as MediaControl;

/**
 * Customize Upload Control Class.
 *
 * @since 3.4.0
 */
class Control extends MediaControl {
	public $type          = 'upload';
	public $mime_type     = '';
	public $button_labels = [];
	public $removed = ''; // unused
	public $context; // unused
	public $extensions = []; // unused

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 3.4.0
	 *
	 * @uses MediaControl::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$value = $this->value();
		if ( $value ) {
			// Get the attachment model for the existing file.
			$attachment_id = attachment_url_to_postid( $value );
			if ( $attachment_id ) {
				$this->json['attachment'] = wp_prepare_attachment_for_js( $attachment_id );
			}
		}
	}
}
