<?php
namespace WP\Customize\NavMenu;
/**
 * Customize API: NameControl class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */
use WP\Customize\Control as BaseControl;
/**
 * Customize control to represent the name field for a given menu.
 *
 * @since 4.3.0
 */
class NameControl extends BaseControl {

	/**
	 * Type of control, used by JS.
	 *
	 * @since 4.3.0
	 * @access public
	 * @var string
	 */
	public $type = 'nav_menu_name';

	/**
	 * No-op since we're using JS template.
	 *
	 * @since 4.3.0
	 * @access protected
	 */
	protected function render_content() {}

	/**
	 * Render the Underscore template for this control.
	 *
	 * @since 4.3.0
	 * @access protected
	 */
	protected function content_template() {
		?>
		<label>
			<# if ( data.label ) { #>
				<span class="customize-control-title screen-reader-text">{{ data.label }}</span>
			<# } #>
			<input type="text" class="menu-name-field live-update-section-title" />
		</label>
		<?php
	}
}