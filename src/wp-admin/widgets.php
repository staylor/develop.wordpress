<?php
/**
 * Widget administration panel
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Widget as WidgetView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

$view = new WidgetView( $app );

/** WordPress Administration Widgets API */
require_once( __DIR__ . '/includes/widgets.php');

if ( ! current_user_can( 'edit_theme_options' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit theme options on this site.' ) . '</p>',
		403
	);
}

$widgets_access = get_user_setting( 'widgets_access' );
if ( $view->_get->get( 'widgets-access' ) ) {
	$widgets_access = 'on' === $view->_get->get( 'widgets-access' ) ? 'on' : 'off';
	set_user_setting( 'widgets_access', $widgets_access );
}

if ( 'on' == $widgets_access ) {
	add_filter( 'admin_body_class', 'wp_widgets_access_body_class' );
} else {
	wp_enqueue_script( 'admin-widgets' );

	if ( wp_is_mobile() ) {
		wp_enqueue_script( 'jquery-touch-punch' );
	}
}

/**
 * Fires early before the Widgets administration screen loads,
 * after scripts are enqueued.
 *
 * @since 2.2.0
 */
do_action( 'sidebar_admin_setup' );

$app->title = __( 'Widgets' );
$app->parent_file = 'themes.php';
$app->current_screen->set_parentage( $app->parent_file );

$view->help->addWidgets();

if ( ! current_theme_supports( 'widgets' ) ) {
	wp_die( __( 'The theme you are currently using isn&#8217;t widget-aware, meaning that it has no sidebars that you are able to change. For information on making your theme widget-aware, please <a href="https://codex.wordpress.org/Widgetizing_Themes">follow these instructions</a>.' ) );
}

// These are the widgets grouped by sidebar
$sidebars_widgets = wp_get_sidebars_widgets();
if ( empty( $sidebars_widgets ) ) {
	$sidebars_widgets = wp_get_widget_defaults();
}

foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
	if ( 'wp_inactive_widgets' == $sidebar_id ) {
		continue;
	}

	if ( ! is_registered_sidebar( $sidebar_id ) ) {
		if ( ! empty( $widgets ) ) { // register the inactive_widgets area as sidebar
			register_sidebar( [
				'name' => __( 'Inactive Sidebar (not used)' ),
				'id' => $sidebar_id,
				'class' => 'inactive-sidebar orphan-sidebar',
				'description' => __( 'This sidebar is no longer available and does not show anywhere on your site. Remove each of the widgets below to fully remove this inactive sidebar.' ),
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '',
				'after_title' => '',
			] );
		} else {
			unset( $sidebars_widgets[ $sidebar_id ] );
		}
	}
}

// register the inactive_widgets area as sidebar
register_sidebar( [
	'name' => __( 'Inactive Widgets' ),
	'id' => 'wp_inactive_widgets',
	'class' => 'inactive-sidebar',
	'description' => __( 'Drag widgets here to remove them from the sidebar but keep their settings.' ),
	'before_widget' => '',
	'after_widget' => '',
	'before_title' => '',
	'after_title' => '',
] );

retrieve_widgets();

// We're saving a widget without js
if ( $view->_get->has( 'savewidget' ) || $view->_get->has( 'removewidget' ) ) {
	$view->handler->doSaveOrDelete( $sidebars_widgets );
}

// Remove inactive widgets without js
if ( $view->_post->get( 'removeinactivewidgets' ) ) {
	$view->handler->doRemoveInactive( $sidebars_widgets );
}

$controls = $app->get( 'widget_controls' );
$registered_sidebars = $app->get( 'registered_sidebars' );

// Output the widget form without js
if ( $view->_get->has( 'editwidget' ) ) {
	$widget_id = $view->_get->get( 'editwidget' );

	if ( $view->_get->get( 'addnew' ) ) {
		// Default to the first sidebar
		$keys = array_keys( $registered_sidebars->getArrayCopy() );
		$sidebar = reset( $keys );

		$multi_number = $view->_get->getInt( 'num' );
		$base = $view->_get->get( 'base' );

		if ( $base && $multi_number ) { // multi-widget
			// Copy minimal info from an existing instance of this widget to a new instance
			foreach ( $controls as $control ) {
				if ( $base === $control['id_base'] ) {
					$control_callback = $control['callback'];
					$control['params'][0]['number'] = -1;
					$widget_id = $control['id'] = $control['id_base'] . '-' . $multi_number;
					$controls[ $control['id'] ] = $control;
					break;
				}
			}
		}
	}

	$registered = $app->get( 'registered_widgets' );

	if ( isset( $controls[ $widget_id ] ) && ! isset( $control ) ) {
		$control = $controls[ $widget_id ];
		$control_callback = $control['callback'];
	} elseif ( ! isset( $controls[ $widget_id ] ) && isset( $registered[ $widget_id ] ) ) {
		$name = esc_html( strip_tags( $registered[ $widget_id ]['name'] ) );
	}

	if ( ! isset( $name ) ) {
		$name = esc_html( strip_tags( $control['name'] ) );
	}

	if ( ! isset( $sidebar ) ) {
		$sidebar = $view->_get->get( 'sidebar', 'wp_inactive_widgets' );
	}

	if ( ! isset( $multi_number ) ) {
		$multi_number = isset( $control['params'][0]['number'] ) ? $control['params'][0]['number'] : '';
	}
	$id_base = isset( $control['id_base'] ) ? $control['id_base'] : $control['id'];

	// Show the widget form.
	$width = ' style="width:' . max( $control['width'], 350 ) . 'px"';
	$key = $view->_get->getInt( 'key', 0 );

	$data = [
		'title' => $app->title,
		'width' => $width,
		'subheading' => sprintf( $view->l10n->widget_name, $name ),
		'nonce' => wp_nonce_field( "save-delete-widget-{$widget_id}", '_wpnonce', true, false ),
		'widget_id' => $widget_id,
		'id_base' => $id_base,
		'multi_number' => $multi_number,
		'addnew' => $view->_get->has( 'addnew' ),
		'save_widget_button' => get_submit_button( __( 'Save Widget' ), 'primary alignright', 'savewidget', false ),
	];

	if ( is_callable( $control_callback ) ) {
		$data['options'] = $app->mute( function () use ( $control_callback, $control ) {
			call_user_func_array( $control_callback, $control['params'] );
		} );
	} else {
		$data['options'] = '<p>' . __('There are no options for this widget.') . '</p>';
	}

	$sidebars = [];

	foreach ( $registered_sidebars as $sbname => $sbvalue ) {
		$sidebar = [
			'name' => $sbname,
			'checked' => checked( $sbname, $sidebar, false ),
			'label' => $sbvalue['name'],
		];

		if ( 'wp_inactive_widgets' == $sbname || 'orphaned_widgets' == substr( $sbname, 0, 16 ) ) {
			$sidebar['space'] = '&nbsp;';
		} else {
			if ( ! isset( $sidebars_widgets[ $sbname ] ) || ! is_array( $sidebars_widgets[ $sbname ] ) ) {
				$j = 1;
				$sidebars_widgets[ $sbname ] = [];
			} else {
				$j = count( $sidebars_widgets[ $sbname ] );
				if ( $view->_get->get( 'addnew' ) || ! in_array( $widget_id, $sidebars_widgets[ $sbname ], true ) ) {
					$j++;
				}
			}

			$opts = [];
			for ( $i = 1; $i <= $j; $i++ ) {
				if ( in_array( $widget_id, $sidebars_widgets[ $sbname ], true ) ) {
					$selected = selected( $i, $key + 1, false );
				} else {
					$selected = '';
				}
				$opts[] = [
					'value' => $i,
					'selected' => $selected,
				];
			}

			$sidebar['opts'] = $opts;
			$sidebars[] = $sidebar;
		}
	}

	$data['sidebars'] = $sidebars;

	if ( ! $view->_get->has( 'addnew' ) ) {
		$data['delete_button'] = get_submit_button( __( 'Delete' ), 'alignleft', 'removewidget', false );
	}

	$view->setData( $data );

	echo $view->render( 'widget/edit', $view );

	exit();
}

$messages = [
	__( 'Changes saved.' )
];

$errors = [
	__( 'Error while saving.' ),
	__( 'Error in displaying the widget settings form.' )
];

$data['title'] = $app->title;
if ( current_user_can( 'customize' ) ) {
	$data['title_extra'] = sprintf(
		' <a class="page-title-action hide-if-no-customize" href="%1$s">%2$s</a>',
		esc_url( add_query_arg(
			[
				[ 'autofocus' => [ 'panel' => 'widgets' ] ],
				'return' => urlencode( wp_unslash( $app['request.uri'] ) )
			],
			admin_url( 'customize.php' )
		) ),
		__( 'Manage with Live Preview' )
	);
}

$message = $view->_get->get( 'message' );
$error = $view->_get->get( 'error' );

if ( $message && isset( $messages[ $message ] ) ) {
	$data['message'] = $messages[ $message ];
}

if ( $error && isset( $errors[ $error ] ) ) {
	$data['error'] = $errors[ $error ];
}

$widget_holders = [];
$theme_sidebars = [];
foreach ( $registered_sidebars as $sidebar => $registered_sidebar ) {
	if ( false !== strpos( $registered_sidebar['class'], 'inactive-sidebar' ) || 'orphaned_widgets' == substr( $sidebar, 0, 16 ) ) {
		$wrap_class = 'widgets-holder-wrap';
		if ( ! empty( $registered_sidebar['class'] ) ) {
			$wrap_class .= ' ' . $registered_sidebar['class'];
		}
		$is_inactive_widgets = 'wp_inactive_widgets' === $registered_sidebar['id'];

		$widget_holder = [
			'wrap_class' => $wrap_class,
			'content' => $app->mute( function () use ( $registered_sidebar ) {
				wp_list_widget_controls( $registered_sidebar['id'], $registered_sidebar['name'] );
			} ),
			'is_inactive_widgets' => $is_inactive_widgets,
		];

		if ( $is_inactive_widgets ) {
			$attributes = [ 'id' => 'inactive-widgets-control-remove' ];
			if ( empty( $sidebars_widgets['wp_inactive_widgets'] ) ) {
				$attributes['disabled'] = '';
			}
			$widget_holder['submit_button'] = get_submit_button( __( 'Clear Inactive Widgets' ), 'delete', 'removeinactivewidgets', false, $attributes );
			$widget_holder['nonce'] = wp_nonce_field( 'remove-inactive-widgets', '_wpnonce_remove_inactive_widgets', false, false );
		}

		$widget_holders[] = $widget_holder;
	} else {
		$theme_sidebars[ $sidebar ] = $registered_sidebar;
	}
}

$i = $split = 0;
$single_sidebar_class = '';
$sidebars_count = count( $theme_sidebars );

if ( $sidebars_count > 1 ) {
	$split = ceil( $sidebars_count / 2 );
} else {
	$single_sidebar_class = ' single-sidebar';
}

$sidebars = [];
foreach ( $theme_sidebars as $sidebar => $registered_sidebar ) {
	$wrap_class = 'widgets-holder-wrap';
	if ( ! empty( $registered_sidebar['class'] ) ) {
		$wrap_class .= ' sidebar-' . $registered_sidebar['class'];
	}

	if ( $i > 0 ) {
		$wrap_class .= ' closed';
	}

	$sidebar_data = [
		'wrap_class' => $wrap_class,
		// Show the control forms for each of the widgets in this sidebar
		'widget_forms' => $app->mute( function () use ( $sidebar, $registered_sidebar ) {
			wp_list_widget_controls( $sidebar, $registered_sidebar['name'] );
		} ),
	];

	if ( $split && $i == $split ) {
		$sidebar_data['split'] = '</div><div class="sidebars-column-2">';
	}

	$sidebars[] = $sidebar_data;

	$i++;
}

$data['wp_list_widgets'] = $app->mute( 'wp_list_widgets' );
$data['widget_holders'] = $widget_holders;
$data['single_sidebar_class'] = $single_sidebar_class;
$data['sidebars'] = $sidebars;
$data['nonce'] = wp_nonce_field( 'save-sidebar-widgets', '_wpnonce_widgets', false, false );

$view->setData( $data );

$view->setActions( [
	/**
	 * Fires before the Widgets administration page content loads.
	 *
	 * @since 3.0.0
	 */
	'widgets_admin_page' => [],
	/**
	 * Fires after the available widgets and sidebars have loaded, before the admin footer.
	 *
	 * @since 2.2.0
	 */
	'sidebar_admin_page' => [],
] );

echo $view->render( 'widget/widgets', $view );
