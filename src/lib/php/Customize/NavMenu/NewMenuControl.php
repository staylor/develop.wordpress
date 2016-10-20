<?php
namespace WP\Customize\NavMenu;
/**
 * Customize API: NewMenuControl class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */
use WP\Customize\Control as BaseControl;
/**
 * Customize control class for new menus.
 *
 * @since 4.3.0
 */
class NewMenuControl extends BaseControl {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @access public
	 * @var string
	 */
	public $type = 'new_menu';

	/**
	 * Render the control's content.
	 *
	 * @since 4.3.0
	 * @access public
	 */
	public function render_content() {
		?>
		<button type="button" class="button button-primary" id="create-new-menu-submit"><?php _e( 'Create Menu' ); ?></button>
		<span class="spinner"></span>
		<?php
	}
}
