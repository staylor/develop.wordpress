<?php
namespace WP\Customize\Color;
/**
 * Customize API: Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

use WP\Customize\{Manager,Control as BaseControl};

/**
 * Customize Color Control class.
 *
 * @since 3.4.0
 *
 * @see \WP\Customize\Control
 */
class Control extends BaseControl {
	/**
	 * @access public
	 * @var string
	 */
	public $type = 'color';

	/**
	 * @access public
	 * @var array
	 */
	public $statuses;

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 *
	 * @param Manager $manager Customizer bootstrap instance.
	 * @param string  $id      Control ID.
	 * @param array   $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( Manager $manager, $id, $args = [] ) {
		$this->statuses = [ '' => __( 'Default' ) ];
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Enqueue scripts/styles for the color picker.
	 *
	 * @since 3.4.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 3.4.0
	 */
	public function to_json() {
		parent::to_json();
		$this->json['statuses'] = $this->statuses;
		$this->json['defaultValue'] = $this->setting->default;
	}

	/**
	 * Don't render the control content from PHP, as it's rendered via JS on load.
	 *
	 * @since 3.4.0
	 */
	public function render_content() {}

	/**
	 * Render a JS template for the content of the color picker control.
	 *
	 * @since 4.1.0
	 */
	public function content_template() {
		echo $this->manager->app['mustache']->render( 'customize/control/color/content', [
			'l10n' => [
				'hex_value' => __( 'Hex Value' ),
			]
		] );
	}
}
