<?php
namespace WP\Widget\Admin;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
			'select' => __( '&mdash; Select &mdash;' ),
			'sidebar' => __( 'Sidebar' ),
			'position' => __( 'Position' ),
			'widget_name' => __( 'Widget %s' ),
			'cancel' => __( 'Cancel' ),
			'add_widget' => __( 'Add Widget' ),
			'available_widgets' => __( 'Available Widgets' ),
			'deactivate' => _x( 'Deactivate', 'removing-widget' ),
			'clear_inactive_message' => __( 'This will clear all items from the inactive widgets list. You will not be able to restore any customizations.' ),
			'activate_widget_message' => __( 'To activate a widget drag it to a sidebar or click on it. To deactivate a widget and delete its settings, drag it back.' ),
			'select_both' => __( 'Select both the sidebar for this widget and the position of the widget in that sidebar.' ),
		];
	}
}