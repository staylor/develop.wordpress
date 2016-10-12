<?php
namespace WP\Widget\Admin;

use WP\Admin\FormHandler as AdminHandler;

class FormHandler extends AdminHandler {

	public function doSaveOrDelete( $sidebars_widgets ) {
		$widget_id = $this->_get->get( 'widget-id' );
		check_admin_referer( "save-delete-widget-{$widget_id}" );

		$post_data = [];
		$number = $this->_get->getInt( 'multi_number', 0 );
		if ( $number ) {
			foreach ( $this->_post->all() as $key => $val ) {
				if ( is_array( $val ) && preg_match( '/__i__|%i%/', key( $val ) ) ) {
					$post_data[ $key ] = [ $number => array_shift( $val ) ];
					break;
				}
			}
		}

		$sidebar_id = $post_data['sidebar'];
		$position = isset( $post_data[ $sidebar_id . '_position' ] ) ?
			(int) $post_data[ $sidebar_id . '_position' ] - 1 : 0;

		$id_base = $post_data['id_base'];
		$sidebar = isset( $sidebars_widgets[ $sidebar_id ] ) ? $sidebars_widgets[ $sidebar_id ] : [];

		// Delete.
		if ( isset( $post_data['removewidget'] ) && $post_data['removewidget'] ) {

			if ( ! in_array( $widget_id, $sidebar, true ) ) {
				$this->redirect( admin_url('widgets.php?error=0') );
			}

			$sidebar = array_diff( $sidebar, [ $widget_id ] );
			$post_data = [
				'sidebar' => $sidebar_id,
				'widget-' . $id_base => [],
				'the-widget-id' => $widget_id,
				'delete_widget' => '1',
			];

			/**
			 * Fires immediately after a widget has been marked for deletion.
			 *
			 * @since 4.4.0
			 *
			 * @param string $widget_id  ID of the widget marked for deletion.
			 * @param string $sidebar_id ID of the sidebar the widget was deleted from.
			 * @param string $id_base    ID base for the widget.
			 */
			do_action( 'delete_widget', $widget_id, $sidebar_id, $id_base );
		}

		$post_data['widget-id'] = $sidebar;

		$this->_post->replace( $post_data );

		foreach ( (array) $this->app->widgets['updates'] as $name => $control ) {
			if ( $name !== $id_base || ! is_callable( $control['callback'] ) ) {
				continue;
			}

			ob_start();
				call_user_func_array( $control['callback'], $control['params'] );
			ob_end_clean();
			break;
		}

		$sidebars_widgets[ $sidebar_id ] = $sidebar;

		// Remove old position.
		if ( ! isset( $post_data['delete_widget'] ) ) {
			foreach ( $sidebars_widgets as $key => $sb ) {
				if ( is_array( $sb ) ) {
					$sidebars_widgets[ $key ] = array_diff( $sb, [ $widget_id ] );
				}
			}
			array_splice( $sidebars_widgets[ $sidebar_id ], $position, 0, $widget_id );
		}

		wp_set_sidebars_widgets( $sidebars_widgets );

		$location = admin_url( 'widgets.php?message=0' );
		$this->redirect( $location );
	}

	public function doRemoveInactive( $sidebars_widgets ) {
		check_admin_referer( 'remove-inactive-widgets', '_wpnonce_remove_inactive_widgets' );

		foreach ( $sidebars_widgets['wp_inactive_widgets'] as $key => $widget_id ) {
			$pieces = explode( '-', $widget_id );
			$multi_number = array_pop( $pieces );
			$id_base = implode( '-', $pieces );
			$widget = get_option( 'widget_' . $id_base );
			unset( $widget[ $multi_number ] );
			update_option( 'widget_' . $id_base, $widget );
			unset( $sidebars_widgets['wp_inactive_widgets'][ $key ] );
		}

		wp_set_sidebars_widgets( $sidebars_widgets );

		$this->redirect( admin_url( 'widgets.php?message=0' ) );
	}
}
