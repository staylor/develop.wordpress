<?php
/**
 * Core Widgets API
 *
 * This API is used for creating dynamic sidebar without hardcoding functionality into
 * themes
 *
 * Includes both internal WordPress routines and theme-use routines.
 *
 * This functionality was found in a plugin before the WordPress 2.2 release, which
 * included it in the core from that point on.
 *
 * @link https://codex.wordpress.org/Plugins/WordPress_Widgets WordPress Widgets
 * @link https://codex.wordpress.org/Plugins/WordPress_Widgets_Api Widgets API
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 2.2.0
 */

use function WP\getApp;

$app = getApp();
$app->widgets['deprecated_callbacks'] = [
	'wp_widget_pages',
	'wp_widget_pages_control',
	'wp_widget_calendar',
	'wp_widget_calendar_control',
	'wp_widget_archives',
	'wp_widget_archives_control',
	'wp_widget_links',
	'wp_widget_meta',
	'wp_widget_meta_control',
	'wp_widget_search',
	'wp_widget_recent_entries',
	'wp_widget_recent_entries_control',
	'wp_widget_tag_cloud',
	'wp_widget_tag_cloud_control',
	'wp_widget_categories',
	'wp_widget_categories_control',
	'wp_widget_text',
	'wp_widget_text_control',
	'wp_widget_rss',
	'wp_widget_rss_control',
	'wp_widget_recent_comments',
	'wp_widget_recent_comments_control'
];

//
// Template tags & API functions
//

/**
 * Register a widget
 *
 * Registers a WP_Widget widget
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 *
 * @param string $widget_class The name of a class that extends WP_Widget
 */
function register_widget( $widget_class ) {
	$app = getApp();
	$app['widget_factory']->register( $widget_class );
}

/**
 * Unregisters a widget.
 *
 * Unregisters a WP_Widget widget. Useful for un-registering default widgets.
 * Run within a function hooked to the {@see 'widgets_init'} action.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 *
 * @param string $widget_class The name of a class that extends WP_Widget.
 */
function unregister_widget( $widget_class ) {
	$app = getApp();
	$app['widget_factory']->unregister( $widget_class );
}

/**
 * Creates multiple sidebars.
 *
 * If you wanted to quickly create multiple sidebars for a theme or internally.
 * This function will allow you to do so. If you don't pass the 'name' and/or
 * 'id' in `$args`, then they will be built for you.
 *
 * @since 2.2.0
 *
 * @see register_sidebar() The second parameter is documented by register_sidebar() and is the same here.
 *
 * @param int          $number Optional. Number of sidebars to create. Default 1.
 * @param array|string $args {
 *     Optional. Array or string of arguments for building a sidebar.
 *
 *     @type string $id   The base string of the unique identifier for each sidebar. If provided, and multiple
 *                        sidebars are being defined, the id will have "-2" appended, and so on.
 *                        Default 'sidebar-' followed by the number the sidebar creation is currently at.
 *     @type string $name The name or title for the sidebars displayed in the admin dashboard. If registering
 *                        more than one sidebar, include '%d' in the string as a placeholder for the uniquely
 *                        assigned number for each sidebar.
 *                        Default 'Sidebar' for the first sidebar, otherwise 'Sidebar %d'.
 * }
 */
function register_sidebars( int $number = 1, $args = [] ) {
	$app = getApp();

	if ( is_string( $args ) ) {
		parse_str( $args, $args );
	}

	for ( $i = 1; $i <= $number; $i++ ) {
		$_args = $args;

		if ( $number > 1 ) {
			$_args['name'] = isset( $args['name'] ) ? sprintf( $args['name'], $i ) : sprintf( __( 'Sidebar %d' ), $i );
		} else {
			$_args['name'] = isset( $args['name'] ) ? $args['name'] : __( 'Sidebar' );
		}

		// Custom specified ID's are suffixed if they exist already.
		// Automatically generated sidebar names need to be suffixed regardless starting at -0
		if ( isset( $args['id'] ) ) {
			$_args['id'] = $args['id'];
			$n = 2; // Start at -2 for conflicting custom ID's
			while ( is_registered_sidebar( $_args['id'] ) ) {
				$n++;
				$_args['id'] = $args['id'] . '-' . $n;
			}
		} else {
			$n = count( $app->sidebars['registered'] );
			do {
				++$n;
				$_args['id'] = 'sidebar-' . $n;
			} while ( is_registered_sidebar( $_args['id'] ) );
		}
		register_sidebar( $_args );
	}
}

/**
 * Builds the definition for a single sidebar and returns the ID.
 *
 * Accepts either a string or an array and then parses that against a set
 * of default arguments for the new sidebar. WordPress will automatically
 * generate a sidebar ID and name based on the current number of registered
 * sidebars if those arguments are not included.
 *
 * When allowing for automatic generation of the name and ID parameters, keep
 * in mind that the incrementor for your sidebar can change over time depending
 * on what other plugins and themes are installed.
 *
 * If theme support for 'widgets' has not yet been added when this function is
 * called, it will be automatically enabled through the use of add_theme_support()
 *
 * @since 2.2.0
 *
 * @param array|string $args {
 *     Optional. Array or string of arguments for the sidebar being registered.
 *
 *     @type string $name          The name or title of the sidebar displayed in the Widgets
 *                                 interface. Default 'Sidebar $instance'.
 *     @type string $id            The unique identifier by which the sidebar will be called.
 *                                 Default 'sidebar-$instance'.
 *     @type string $description   Description of the sidebar, displayed in the Widgets interface.
 *                                 Default empty string.
 *     @type string $class         Extra CSS class to assign to the sidebar in the Widgets interface.
 *                                 Default empty.
 *     @type string $before_widget HTML content to prepend to each widget's HTML output when
 *                                 assigned to this sidebar. Default is an opening list item element.
 *     @type string $after_widget  HTML content to append to each widget's HTML output when
 *                                 assigned to this sidebar. Default is a closing list item element.
 *     @type string $before_title  HTML content to prepend to the sidebar title when displayed.
 *                                 Default is an opening h2 element.
 *     @type string $after_title   HTML content to append to the sidebar title when displayed.
 *                                 Default is a closing h2 element.
 * }
 * @return string Sidebar ID added to $wp_registered_sidebars global.
 */
function register_sidebar( $args = [] ) {
	$app = getApp();
	$i = count( $app->sidebars['registered'] ) + 1;

	$id_is_empty = empty( $args['id'] );

	$defaults = [
		'name' => sprintf( __( 'Sidebar %d' ), $i ),
		'id' => 'sidebar-' . $i,
		'description' => '',
		'class' => '',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => "</li>\n",
		'before_title' => '<h2 class="widgettitle">',
		'after_title' => "</h2>\n",
	];

	$sidebar = wp_parse_args( $args, $defaults );

	if ( $id_is_empty ) {
		/* translators: 1: the id argument, 2: sidebar name, 3: recommended id value */
		_doing_it_wrong( __FUNCTION__, sprintf( __( 'No %1$s was set in the arguments array for the "%2$s" sidebar. Defaulting to "%3$s". Manually set the %1$s to "%3$s" to silence this notice and keep existing sidebar content.' ), '<code>id</code>', $sidebar['name'], $sidebar['id'] ), '4.2.0' );
	}

	$app->sidebars['registered'][ $sidebar['id'] ] = $sidebar;

	add_theme_support( 'widgets' );

	/**
	 * Fires once a sidebar has been registered.
	 *
	 * @since 3.0.0
	 *
	 * @param array $sidebar Parsed arguments for the registered sidebar.
	 */
	do_action( 'register_sidebar', $sidebar );

	return $sidebar['id'];
}

/**
 * Removes a sidebar from the list.
 *
 * @since 2.2.0
 *
 * @param string $name The ID of the sidebar when it was added.
 */
function unregister_sidebar( $name ) {
	$app = getApp();
	unset( $app->sidebars['registered'][ $name ] );
}

/**
 * Checks if a sidebar is registered.
 *
 * @since 4.4.0
 *
 * @param string|int $sidebar_id The ID of the sidebar when it was registered.
 * @return bool True if the sidebar is registered, false otherwise.
 */
function is_registered_sidebar( $sidebar_id ) {
	$app = getApp();
	return isset( $app->sidebars['registered'][ $sidebar_id ] );
}

/**
 * Register an instance of a widget.
 *
 * The default widget option is 'classname' that can be overridden.
 *
 * The function can also be used to un-register widgets when `$output_callback`
 * parameter is an empty string.
 *
 * @since 2.2.0
 *
 * @param int|string $id              Widget ID.
 * @param string     $name            Widget display title.
 * @param callable   $output_callback Run when widget is called.
 * @param array      $args {
 *     Optional. An array of supplementary widget options for the instance.
 *
 *     @type string $classname   Class name for the widget's HTML container. Default is a shortened
 *                               version of the output callback name.
 *     @type string $description Widget description for display in the widget administration
 *                               panel and/or theme.
 * }
 */
function wp_register_sidebar_widget( $id, $name, $output_callback, $args = [] ) {
	$app = getApp();

	$id = strtolower( $id );

	if ( empty( $output_callback ) ) {
		unset( $app->widgets['registered'][ $id ] );
		return;
	}

	$id_base = _get_widget_id_base( $id );
	if ( in_array( $output_callback, $app->widgets['deprecated_callbacks'], true ) && ! is_callable( $output_callback ) ) {
		unset(
			$app->widgets['controls'][ $id ],
			$app->widgets['updates'][ $id_base ]
		);
		return;
	}

	$defaults = [ 'classname' => $output_callback ];
	$options = wp_parse_args( $args, $defaults );
	$widget = [
		'name' => $name,
		'id' => $id,
		'callback' => $output_callback,
		'params' => array_slice( func_get_args(), 4 )
	];
	$widget = array_merge( $widget, $options );

	if ( is_callable( $output_callback ) && ( ! isset( $app->widgets['registered'][ $id ] ) || did_action( 'widgets_init' ) ) ) {

		/**
		 * Fires once for each registered widget.
		 *
		 * @since 3.0.0
		 *
		 * @param array $widget An array of default widget arguments.
		 */
		do_action( 'wp_register_sidebar_widget', $widget );
		$app->widgets['registered'][ $id ] = $widget;
	}
}

/**
 * Retrieve description for widget.
 *
 * When registering widgets, the options can also include 'description' that
 * describes the widget for display on the widget administration panel or
 * in the theme.
 *
 * @since 2.5.0
 *
 * @param int|string $id Widget ID.
 * @return string|void Widget description, if available.
 */
function wp_widget_description( $id ) {
	if ( ! is_scalar( $id ) ) {
		return;
	}

	$app = getApp();

	if ( isset( $app->widgets['registered'][ $id ]['description'] ) ) {
		return esc_html( $app->widgets['registered'][ $id ]['description'] );
	}
}

/**
 * Retrieve description for a sidebar.
 *
 * When registering sidebars a 'description' parameter can be included that
 * describes the sidebar for display on the widget administration panel.
 *
 * @since 2.9.0
 *
 * @param string $id sidebar ID.
 * @return string|void Sidebar description, if available.
 */
function wp_sidebar_description( $id ) {
	if ( ! is_scalar( $id ) ) {
		return;
	}

	$app = getApp();

	if ( isset( $app->sidebars['registered'][ $id ]['description'] ) ) {
		return esc_html( $app->sidebars['registered'][ $id ]['description'] );
	}
}

/**
 * Remove widget from sidebar.
 *
 * @since 2.2.0
 *
 * @param int|string $id Widget ID.
 */
function wp_unregister_sidebar_widget( $id ) {

	/**
	 * Fires just before a widget is removed from a sidebar.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id The widget ID.
	 */
	do_action( 'wp_unregister_sidebar_widget', $id );

	wp_register_sidebar_widget( $id, '', '' );
	wp_unregister_widget_control( $id );
}

/**
 * Registers widget control callback for customizing options.
 *
 * @since 2.2.0
 *
 * @todo `$params` parameter?
 *
 * @param int|string   $id               Sidebar ID.
 * @param string       $name             Sidebar display name.
 * @param callable     $control_callback Run when sidebar is displayed.
 * @param array $options {
 *     Optional. Array or string of control options. Default empty array.
 *
 *     @type int        $height  Never used. Default 200.
 *     @type int        $width   Width of the fully expanded control form (but try hard to use the default width).
 *                               Default 250.
 *     @type int|string $id_base Required for multi-widgets, i.e widgets that allow multiple instances such as the
 *                               text widget. The widget id will end up looking like `{$id_base}-{$unique_number}`.
 * }
 */
function wp_register_widget_control( $id, $name, $control_callback, $options = [] ) {
	$app = getApp();
	$id = strtolower( $id );
	$id_base = _get_widget_id_base( $id );

	if ( empty( $control_callback ) ) {
		unset( $app->widgets['controls'][ $id ] );
		unset( $app->widgets['updates'][ $id_base ] );
		return;
	}

	if ( in_array( $control_callback, $app->widgets['deprecated_callbacks'], true ) && ! is_callable( $control_callback ) ) {
		unset( $app->widgets['registered'][ $id ] );
		return;
	}

	if ( isset( $app->widgets['controls'][ $id ] ) && ! did_action( 'widgets_init' ) ) {
		return;
	}

	$defaults = [ 'width' => 250, 'height' => 200 ]; // height is never used
	$options = wp_parse_args( $options, $defaults );
	$options['width'] = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$widget = [
		'name' => $name,
		'id' => $id,
		'callback' => $control_callback,
		'params' => array_slice( func_get_args(), 4 )
	];
	$widget = array_merge( $widget, $options );

	$app->widgets['controls'][ $id ] = $widget;

	if ( isset( $app->widgets['updates'][ $id_base ] ) ) {
		return;
	}

	if ( isset( $widget['params'][0]['number'] ) ) {
		$widget['params'][0]['number'] = -1;
	}

	unset( $widget['width'], $widget['height'], $widget['name'], $widget['id'] );
	$app->widgets['updates'][ $id_base ] = $widget;
}

/**
 * Registers the update callback for a widget.
 *
 * @since 2.8.0
 *
 * @param string   $id_base         The base ID of a widget created by extending WP_Widget.
 * @param callable $update_callback Update callback method for the widget.
 * @param array    $options         Optional. Widget control options. See wp_register_widget_control().
 *                                  Default empty array.
 */
function _register_widget_update_callback( $id_base, $update_callback, $options = [] ) {
	$app = getApp();

	if ( isset( $app->widgets['updates'][ $id_base ] ) ) {
		if ( empty( $update_callback ) ) {
			unset( $app->widgets['updates'][ $id_base ] );
		}
		return;
	}

	$args = [
		'callback' => $update_callback,
		'params' => array_slice( func_get_args(), 3 )
	];

	$widget = array_merge( $args, $options );
	$app->widgets['updates'][ $id_base ] = $widget;
}

/**
 * Registers the form callback for a widget.
 *
 * @since 2.8.0
 *
 * @param int|string $id            Widget ID.
 * @param string     $name          Name attribute for the widget.
 * @param callable   $form_callback Form callback.
 * @param array      $args          Optional. Widget control options. See wp_register_widget_control().
 *                                  Default empty array.
 */
function _register_widget_form_callback( $id, $name, $form_callback, $args = [] ) {
	$id = strtolower( $id );

	$app = getApp();

	if ( empty( $form_callback ) ) {
		unset( $app->widgets['controls'][ $id ] );
		return;
	}

	if ( isset( $app->widgets['controls'][ $id ] ) && ! did_action( 'widgets_init' ) ) {
		return;
	}

	$defaults = [ 'width' => 250, 'height' => 200 ];
	$options = wp_parse_args( $args, $defaults );
	$options['width'] = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$opts = [
		'name' => $name,
		'id' => $id,
		'callback' => $form_callback,
		'params' => array_slice( func_get_args(), 4 )
	];
	$widget = array_merge( $opts, $options );

	$app->widgets['controls'][ $id ] = $widget;
}

/**
 * Remove control callback for widget.
 *
 * @since 2.2.0
 *
 * @param int|string $id Widget ID.
 */
function wp_unregister_widget_control( $id ) {
	wp_register_widget_control( $id, '', '' );
}

/**
 * Display dynamic sidebar.
 *
 * By default this displays the default sidebar or 'sidebar-1'. If your theme specifies the 'id' or
 * 'name' parameter for its registered sidebars you can pass an id or name as the $index parameter.
 * Otherwise, you can pass in a numerical index to display the sidebar at that index.
 *
 * @since 2.2.0
 *
 * @param int|string $index Optional, default is 1. Index, name or ID of dynamic sidebar.
 * @return bool True, if widget sidebar was found and called. False if not found or not called.
 */
function dynamic_sidebar( $index = 1 ) {
	$app = getApp();

	if ( is_int( $index ) ) {
		$index = 'sidebar-' . $index;
	} else {
		$index = sanitize_title( $index );
		foreach ( (array) $app->sidebars['registered'] as $key => $value ) {
			if ( sanitize_title( $value['name'] ) === $index ) {
				$index = $key;
				break;
			}
		}
	}

	$sidebars_widgets = wp_get_sidebars_widgets();
	if ( empty( $app->sidebars['registered'][ $index ] ) || empty( $sidebars_widgets[ $index ] ) || ! is_array( $sidebars_widgets[ $index ] ) ) {
		/** This action is documented in wp-includes/widget.php */
		do_action( 'dynamic_sidebar_before', $index, false );
		/** This action is documented in wp-includes/widget.php */
		do_action( 'dynamic_sidebar_after', $index, false );
		/** This filter is documented in wp-includes/widget.php */
		return apply_filters( 'dynamic_sidebar_has_widgets', false, $index );
	}

	/**
	 * Fires before widgets are rendered in a dynamic sidebar.
	 *
	 * Note: The action also fires for empty sidebars, and on both the front end
	 * and back end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 3.9.0
	 *
	 * @param int|string $index       Index, name, or ID of the dynamic sidebar.
	 * @param bool       $has_widgets Whether the sidebar is populated with widgets.
	 *                                Default true.
	 */
	do_action( 'dynamic_sidebar_before', $index, true );
	$sidebar = $app->sidebars['registered'][ $index ];

	$did_one = false;
	foreach ( (array) $sidebars_widgets[ $index ] as $id ) {

		if ( ! isset( $app->widgets['registered'][ $id ] ) ) {
			continue;
		}

		$params = array_merge(
			[
				array_merge( $sidebar, [
					'widget_id' => $id,
					'widget_name' => $app->widgets['registered'][ $id ]['name']
				] )
			],
			(array) $app->widgets['registered'][ $id ]['params']
		);

		// Substitute HTML id and class attributes into before_widget
		$classname_ = '';
		foreach ( (array) $app->widgets['registered'][ $id ]['classname'] as $cn ) {
			if ( is_string( $cn ) ) {
				$classname_ .= '_' . $cn;
			} elseif ( is_object( $cn ) ) {
				$classname_ .= '_' . get_class( $cn );
			}
		}
		$classname_ = ltrim( $classname_, '_' );
		$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $id, $classname_ );

		/**
		 * Filters the parameters passed to a widget's display callback.
		 *
		 * Note: The filter is evaluated on both the front end and back end,
		 * including for the Inactive Widgets sidebar on the Widgets screen.
		 *
		 * @since 2.5.0
		 *
		 * @see register_sidebar()
		 *
		 * @param array $params {
		 *     @type array $args  {
		 *         An array of widget display arguments.
		 *
		 *         @type string $name          Name of the sidebar the widget is assigned to.
		 *         @type string $id            ID of the sidebar the widget is assigned to.
		 *         @type string $description   The sidebar description.
		 *         @type string $class         CSS class applied to the sidebar container.
		 *         @type string $before_widget HTML markup to prepend to each widget in the sidebar.
		 *         @type string $after_widget  HTML markup to append to each widget in the sidebar.
		 *         @type string $before_title  HTML markup to prepend to the widget title when displayed.
		 *         @type string $after_title   HTML markup to append to the widget title when displayed.
		 *         @type string $widget_id     ID of the widget.
		 *         @type string $widget_name   Name of the widget.
		 *     }
		 *     @type array $widget_args {
		 *         An array of multi-widget arguments.
		 *
		 *         @type int $number Number increment used for multiples of the same widget.
		 *     }
		 * }
		 */
		$params = apply_filters( 'dynamic_sidebar_params', $params );

		$callback = $app->widgets['registered'][ $id ][ 'callback' ];

		/**
		 * Fires before a widget's display callback is called.
		 *
		 * Note: The action fires on both the front end and back end, including
		 * for widgets in the Inactive Widgets sidebar on the Widgets screen.
		 *
		 * The action is not fired for empty sidebars.
		 *
		 * @since 3.0.0
		 *
		 * @param array $widget_id {
		 *     An associative array of widget arguments.
		 *
		 *     @type string $name                Name of the widget.
		 *     @type string $id                  Widget ID.
		 *     @type array|callable $callback    When the hook is fired on the front end, $callback is an array
		 *                                       containing the widget object. Fired on the back end, $callback
		 *                                       is 'wp_widget_control', see $_callback.
		 *     @type array          $params      An associative array of multi-widget arguments.
		 *     @type string         $classname   CSS class applied to the widget container.
		 *     @type string         $description The widget description.
		 *     @type array          $_callback   When the hook is fired on the back end, $_callback is populated
		 *                                       with an array containing the widget object, see $callback.
		 * }
		 */
		do_action( 'dynamic_sidebar', $app->widgets['registered'][ $id ] );

		if ( is_callable( $callback ) ) {
			call_user_func_array( $callback, $params );
			$did_one = true;
		}
	}

	/**
	 * Fires after widgets are rendered in a dynamic sidebar.
	 *
	 * Note: The action also fires for empty sidebars, and on both the front end
	 * and back end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 3.9.0
	 *
	 * @param int|string $index       Index, name, or ID of the dynamic sidebar.
	 * @param bool       $has_widgets Whether the sidebar is populated with widgets.
	 *                                Default true.
	 */
	do_action( 'dynamic_sidebar_after', $index, true );

	/**
	 * Filters whether a sidebar has widgets.
	 *
	 * Note: The filter is also evaluated for empty sidebars, and on both the front end
	 * and back end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 3.9.0
	 *
	 * @param bool       $did_one Whether at least one widget was rendered in the sidebar.
	 *                            Default false.
	 * @param int|string $index   Index, name, or ID of the dynamic sidebar.
	 */
	return apply_filters( 'dynamic_sidebar_has_widgets', $did_one, $index );
}

/**
 * Whether widget is displayed on the front end.
 *
 * Either $callback or $id_base can be used
 * $id_base is the first argument when extending WP_Widget class
 * Without the optional $widget_id parameter, returns the ID of the first sidebar
 * in which the first instance of the widget with the given callback or $id_base is found.
 * With the $widget_id parameter, returns the ID of the sidebar where
 * the widget with that callback/$id_base AND that ID is found.
 *
 * NOTE: $widget_id and $id_base are the same for single widgets. To be effective
 * this function has to run after widgets have initialized, at action {@see 'init'} or later.
 *
 * @since 2.2.0
 *
 * @param string|false $callback      Optional, Widget callback to check. Default false.
 * @param int|false    $widget_id     Optional. Widget ID. Optional, but needed for checking. Default false.
 * @param string|false $id_base       Optional. The base ID of a widget created by extending WP_Widget. Default false.
 * @param bool         $skip_inactive Optional. Whether to check in 'wp_inactive_widgets'. Default true.
 * @return string|false False if widget is not active or id of sidebar in which the widget is active.
 */
function is_active_widget( $callback = false, $widget_id = false, $id_base = false, $skip_inactive = true ) {
	$sidebars_widgets = wp_get_sidebars_widgets();

	$app = getApp();

	if ( is_array( $sidebars_widgets ) ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( ! is_array( $widgets ) ) {
				continue;
			}

			if ( $skip_inactive && ( 'wp_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) ) {
				continue;
			}

			foreach ( $widgets as $widget ) {
				if (
					(
						$callback &&
						isset( $app->widgets['registered'][ $widget ]['callback'] ) &&
						$app->widgets['registered'][ $widget ]['callback'] === $callback
					) ||
					( $id_base && _get_widget_id_base( $widget ) === $id_base )
				) {
					if ( ! $widget_id || $widget_id == $app->widgets['registered'][ $widget ]['id'] ) {
						return $sidebar;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Whether the dynamic sidebar is enabled and used by theme.
 *
 * @since 2.2.0
 *
 * @return bool True, if using widgets. False, if not using widgets.
 */
function is_dynamic_sidebar() {
	$app = getApp();

	$sidebars_widgets = get_option( 'sidebars_widgets' );
	foreach ( (array) $app->sidebars['registered'] as $index => $sidebar ) {
		if ( empty( $sidebars_widgets[ $index ] ) ) {
			continue;
		}

		foreach ( (array) $sidebars_widgets[ $index ] as $widget ) {
			if ( array_key_exists( $widget, $app->widgets['registered'] ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Whether a sidebar is in use.
 *
 * @since 2.8.0
 *
 * @param string|int $index Sidebar name, id or number to check.
 * @return bool true if the sidebar is in use, false otherwise.
 */
function is_active_sidebar( $index ) {
	$index = ( is_int( $index ) ) ? "sidebar-$index" : sanitize_title( $index );
	$sidebars_widgets = wp_get_sidebars_widgets();
	$is_active_sidebar = ! empty( $sidebars_widgets[ $index ] );

	/**
	 * Filters whether a dynamic sidebar is considered "active".
	 *
	 * @since 3.9.0
	 *
	 * @param bool       $is_active_sidebar Whether or not the sidebar should be considered "active".
	 *                                      In other words, whether the sidebar contains any widgets.
	 * @param int|string $index             Index, name, or ID of the dynamic sidebar.
	 */
	return apply_filters( 'is_active_sidebar', $is_active_sidebar, $index );
}

//
// Internal Functions
//

/**
 * Retrieve full list of sidebars and their widget instance IDs.
 *
 * Will upgrade sidebar widget list, if needed. Will also save updated list, if
 * needed.
 *
 * @since 2.2.0
 * @access private
 *
 * @param bool $deprecated Not used (argument deprecated).
 * @return array Upgraded list of widgets to version 3 array format when called from the admin.
 */
function wp_get_sidebars_widgets( $deprecated = true ) {
	if ( $deprecated !== true ) {
		_deprecated_argument( __FUNCTION__, '2.8.1' );
	}

	$app = getApp();

	// If loading from front page, consult $_wp_sidebars_widgets rather than options
	// to see if wp_convert_widget_settings() has made manipulations in memory.
	if ( ! is_admin() ) {
		if ( empty( $app->sidebars['_widgets'] ) ) {
			$app->sidebars['_widgets'] = get_option( 'sidebars_widgets', [] );
		}
		$sidebars_widgets = $app->sidebars['_widgets'];
	} else {
		$sidebars_widgets = get_option( 'sidebars_widgets', [] );
	}

	if ( is_array( $sidebars_widgets ) && isset( $sidebars_widgets[ 'array_version' ] ) ) {
		unset( $sidebars_widgets[ 'array_version' ] );
	}

	/**
	 * Filters the list of sidebars and their widgets.
	 *
	 * @since 2.7.0
	 *
	 * @param array $sidebars_widgets An associative array of sidebars and their widgets.
	 */
	$app->sidebars['widgets'] = apply_filters( 'sidebars_widgets', $sidebars_widgets );
	return $app->sidebars['widgets'];
}

/**
 * Set the sidebar widget option to update sidebars.
 *
 * @since 2.2.0
 * @access private
 *
 * @param array $sidebars_widgets Sidebar widgets and their settings.
 */
function wp_set_sidebars_widgets( $sidebars_widgets ) {
	if ( ! isset( $sidebars_widgets[ 'array_version' ] ) ) {
		$sidebars_widgets[ 'array_version' ] = 3;
	}
	update_option( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Retrieve default registered sidebars list.
 *
 * @since 2.2.0
 * @access private
 *
 * @return array
 */
function wp_get_widget_defaults() {
	$app = getApp();
	$defaults = [];

	$keys = array_keys( $app->sidebars['registered'] );
	foreach ( $keys as $index ) {
		$defaults[ $index ] = [];
	}

	return $defaults;
}

/**
 * Convert the widget settings from single to multi-widget format.
 *
 * @since 2.8.0
 *
 * @param string $base_name
 * @param string $option_name
 * @param array  $settings
 * @return array
 */
function wp_convert_widget_settings( $base_name, $option_name, $settings ) {
	// This test may need expanding.
	$single = $changed = false;
	if ( empty( $settings ) ) {
		$single = true;
	} else {
		foreach ( array_keys( $settings ) as $number ) {
			if ( 'number' == $number ) {
				continue;
			}
			if ( ! is_numeric( $number ) ) {
				$single = true;
				break;
			}
		}
	}

	if ( $single ) {
		$settings = [ 2 => $settings ];

		// If loading from the front page, update sidebar in memory but don't save to options
		if ( is_admin() ) {
			$sidebars_widgets = get_option( 'sidebars_widgets' );
		} else {
			if ( empty( $app->sidebars['_widgets'] ) ) {
				$app->sidebars['_widgets'] = get_option( 'sidebars_widgets', [] );
			}
			$sidebars_widgets = & $app->sidebars['_widgets'];
		}

		foreach ( (array) $sidebars_widgets as $index => $sidebar ) {
			if ( is_array( $sidebar ) ) {
				foreach ( $sidebar as $i => $name ) {
					if ( $base_name == $name ) {
						$sidebars_widgets[ $index ][ $i ] = "$name-2";
						$changed = true;
						break 2;
					}
				}
			}
		}

		if ( is_admin() && $changed ) {
			update_option( 'sidebars_widgets', $sidebars_widgets );
		}
	}

	$settings[ '_multiwidget' ] = 1;
	if ( is_admin() ) {
		update_option( $option_name, $settings );
	}

	return $settings;
}

/**
 * Output an arbitrary widget as a template tag.
 *
 * @since 2.8.0
 *
 * @param string $widget   The widget's PHP class name (see class-wp-widget.php).
 * @param array  $instance Optional. The widget's instance settings. Default empty array.
 * @param array  $args {
 *     Optional. Array of arguments to configure the display of the widget.
 *
 *     @type string $before_widget HTML content that will be prepended to the widget's HTML output.
 *                                 Default `<div class="widget %s">`, where `%s` is the widget's class name.
 *     @type string $after_widget  HTML content that will be appended to the widget's HTML output.
 *                                 Default `</div>`.
 *     @type string $before_title  HTML content that will be prepended to the widget's title when displayed.
 *                                 Default `<h2 class="widgettitle">`.
 *     @type string $after_title   HTML content that will be appended to the widget's title when displayed.
 *                                 Default `</h2>`.
 * }
 */
function the_widget( $widget, $instance = [], $args = [] ) {
	$app = getApp();

	$widget_obj = $app['widget_factory']->widgets[ $widget ];
	if ( ! ( $widget_obj instanceof WP_Widget ) ) {
		return;
	}

	$default_args = [
		'before_widget' => '<div class="widget %s">',
		'after_widget'  => "</div>",
		'before_title'  => '<h2 class="widgettitle">',
		'after_title'   => '</h2>',
	];
	$opts = wp_parse_args( $args, $default_args );
	$opts['before_widget'] = sprintf(
		$opts['before_widget'],
		$widget_obj->widget_options['classname']
	);

	$settings = wp_parse_args( $instance );

	/**
	 * Fires before rendering the requested widget.
	 *
	 * @since 3.0.0
	 *
	 * @param string $widget   The widget's class name.
	 * @param array  $settings The current widget instance's settings.
	 * @param array  $args     An array of the widget's sidebar arguments.
	 */
	do_action( 'the_widget', $widget, $settings, $opts );

	$widget_obj->_set( -1 );
	$widget_obj->widget( $opts, $settings );
}

/**
 * Retrieves the widget ID base value.
 *
 * @since 2.8.0
 *
 * @param string $id Widget ID.
 * @return string Widget ID base.
 */
function _get_widget_id_base( $id ) {
	return preg_replace( '/-[0-9]+$/', '', $id );
}

/**
 * Handle sidebars config after theme change
 *
 * @access private
 * @since 3.3.0
 */
function _wp_sidebars_changed() {
	$app = getApp();

	if ( ! is_array( $app->sidebars['widgets'] ) ) {
		$app->sidebars['widgets'] = wp_get_sidebars_widgets();
	}
	retrieve_widgets( true );
}

/**
 * Look for "lost" widgets, this has to run at least on each theme change.
 *
 * @since 2.8.0
 *
 * @param string|bool $theme_changed Whether the theme was changed as a boolean. A value
 *                                   of 'customize' defers updates for the Customizer.
 * @return array|void
 */
function retrieve_widgets( $theme_changed = false ) {
	$app = getApp();
	$sidebars_widgets = $app->sidebars['widgets'];

	$registered_sidebar_keys = array_keys( $app->sidebars['registered'] );
	$orphaned = 0;

	$old_sidebars_widgets = get_theme_mod( 'sidebars_widgets' );
	if ( is_array( $old_sidebars_widgets ) ) {
		// time() that sidebars were stored is in $old_sidebars_widgets['time']
		$_sidebars_widgets = $old_sidebars_widgets[ 'data' ];

		if ( 'customize' !== $theme_changed ) {
			remove_theme_mod( 'sidebars_widgets' );
		}

		foreach ( $_sidebars_widgets as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) {
				continue;
			}

			if ( ! in_array( $sidebar, $registered_sidebar_keys ) ) {
				++$orphaned;
				$_sidebars_widgets[ 'orphaned_widgets_' . $orphaned ] = $widgets;
				unset( $_sidebars_widgets[ $sidebar ] );
			}
		}
	} else {
		if ( empty( $sidebars_widgets ) ) {
			return;
		}

		unset( $sidebars_widgets[ 'array_version' ] );

		$old = array_keys( $sidebars_widgets );
		sort( $old );
		sort( $registered_sidebar_keys );

		if ( $old == $registered_sidebar_keys ) {
			return;
		}

		$_sidebars_widgets = [
			'wp_inactive_widgets' => ! empty( $sidebars_widgets[ 'wp_inactive_widgets' ] ) ? $sidebars_widgets[ 'wp_inactive_widgets' ] : []
		];

		unset( $sidebars_widgets[ 'wp_inactive_widgets' ] );

		foreach ( $app->sidebars['registered'] as $id => $settings ) {
			if ( $theme_changed ) {
				$_sidebars_widgets[ $id ] = array_shift( $sidebars_widgets );
			} else {
				// no theme change, grab only sidebars that are currently registered
				if ( isset( $sidebars_widgets[ $id ] ) ) {
					$_sidebars_widgets[ $id ] = $sidebars_widgets[ $id ];
					unset( $sidebars_widgets[ $id ] );
				}
			}
		}

		foreach ( $sidebars_widgets as $val ) {
			if ( is_array( $val ) && ! empty( $val ) ) {
				++$orphaned;
				$_sidebars_widgets[ 'orphaned_widgets_' . $orphaned ] = $val;
			}
		}
	}

	// discard invalid, theme-specific widgets from sidebars
	$shown_widgets = [];

	foreach ( $_sidebars_widgets as $sidebar => $widgets ) {
		if ( ! is_array( $widgets ) ) {
			continue;
		}

		$_widgets = [];
		foreach ( $widgets as $widget ) {
			if ( isset( $app->widgets['registered'][ $widget ] ) ) {
				$_widgets[] = $widget;
			}
		}

		$_sidebars_widgets[ $sidebar ] = $_widgets;
		$shown_widgets = array_merge( $shown_widgets, $_widgets );
	}

	$sidebars_widgets = $_sidebars_widgets;
	unset( $_sidebars_widgets, $_widgets );

	// find hidden/lost multi-widget instances
	$lost_widgets = [];
	foreach ( $app->widgets['registered'] as $key => $val ) {
		if ( in_array( $key, $shown_widgets, true ) ) {
			continue;
		}

		$number = preg_replace( '/.+?-([0-9]+)$/', '$1', $key );

		if ( 2 > (int) $number ) {
			continue;
		}

		$lost_widgets[] = $key;
	}

	$sidebars_widgets[ 'wp_inactive_widgets' ] = array_merge( $lost_widgets, (array) $sidebars_widgets[ 'wp_inactive_widgets' ] );
	if ( 'customize' !== $theme_changed ) {
		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	$app->sidebars['widgets'] = $sidebars_widgets;
	return $sidebars_widgets;
}

/**
 * Display the RSS entries in a list.
 *
 * @since 2.5.0
 *
 * @param string|array|object $rss RSS url.
 * @param array $args Widget arguments.
 */
function wp_widget_rss_output( $rss, $args = [] ) {
	if ( is_string( $rss ) ) {
		$rss = fetch_feed( $rss );
	} elseif ( is_array( $rss ) && isset( $rss[ 'url' ] ) ) {
		$args = $rss;
		$rss = fetch_feed( $rss[ 'url' ] );
	} elseif ( ! is_object( $rss ) ) {
		return;
	}

	if ( is_wp_error( $rss ) ) {
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			echo '<p>' . sprintf( __( '<strong>RSS Error</strong>: %s' ), $rss->get_error_message() ) . '</p>';
		}
		return;
	}

	$default_args = [ 'show_author' => 0, 'show_date' => 0, 'show_summary' => 0, 'items' => 0 ];
	$args = wp_parse_args( $args, $default_args );

	$items = (int) $args['items'];
	if ( $items < 1 || 20 < $items ) {
		$items = 10;
	}
	$show_summary  = (int) $args['show_summary'];
	$show_author   = (int) $args['show_author'];
	$show_date     = (int) $args['show_date'];

	if ( ! $rss->get_item_quantity() ) {
		echo '<ul><li>' . __( 'An error has occurred, which probably means the feed is down. Try again later.' ) . '</li></ul>';
		$rss->__destruct();
		unset( $rss );
		return;
	}

	echo '<ul>';
	foreach ( $rss->get_items( 0, $items ) as $item ) {
		$link = $item->get_link();
		while ( stristr( $link, 'http' ) != $link ) {
			$link = substr( $link, 1 );
		}
		$link = esc_url( strip_tags( $link ) );

		$title = esc_html( trim( strip_tags( $item->get_title() ) ) );
		if ( empty( $title ) ) {
			$title = __( 'Untitled' );
		}

		$desc = @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
		$desc = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );

		$summary = '';
		if ( $show_summary ) {
			$summary = $desc;

			// Change existing [...] to [&hellip;].
			if ( '[...]' == substr( $summary, -5 ) ) {
				$summary = substr( $summary, 0, -5 ) . '[&hellip;]';
			}

			$summary = '<div class="rssSummary">' . esc_html( $summary ) . '</div>';
		}

		$date = '';
		if ( $show_date ) {
			$date = $item->get_date( 'U' );

			if ( $date ) {
				$date = ' <span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date ) . '</span>';
			}
		}

		$author = '';
		if ( $show_author ) {
			$author = $item->get_author();
			if ( is_object( $author ) ) {
				$author = $author->get_name();
				$author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
			}
		}

		if ( $link == '' ) {
			echo "<li>$title{$date}{$summary}{$author}</li>";
		} elseif ( $show_summary ) {
			echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$summary}{$author}</li>";
		} else {
			echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$author}</li>";
		}
	}
	echo '</ul>';
	$rss->__destruct();
	unset( $rss );
}

/**
 * Display RSS widget options form.
 *
 * The options for what fields are displayed for the RSS form are all booleans
 * and are as follows: 'url', 'title', 'items', 'show_summary', 'show_author',
 * 'show_date'.
 *
 * @since 2.5.0
 *
 * @param array|string $args Values for input fields.
 * @param array $inputs Override default display options.
 */
function wp_widget_rss_form( $args, $inputs = null ) {
	$default_inputs = [ 'url' => true, 'title' => true, 'items' => true, 'show_summary' => true, 'show_author' => true, 'show_date' => true ];
	$inputs = wp_parse_args( $inputs, $default_inputs );

	$args['title'] = isset( $args['title'] ) ? $args['title'] : '';
	$args[ 'url' ] = isset( $args[ 'url' ] ) ? $args[ 'url' ] : '';
	$args['items'] = isset( $args['items'] ) ? (int) $args['items'] : 0;

	if ( $args['items'] < 1 || 20 < $args['items'] ) {
		$args['items'] = 10;
	}

	$args['show_summary'] = isset( $args['show_summary'] ) ? (int) $args['show_summary'] : (int) $inputs['show_summary'];
	$args['show_author'] = isset( $args['show_author'] ) ? (int) $args['show_author'] : (int) $inputs['show_author'];
	$args['show_date'] = isset( $args['show_date'] ) ? (int) $args['show_date'] : (int) $inputs['show_date'];

	if ( ! empty( $args[ 'error' ] ) ) {
		echo '<p class="widget-error"><strong>' . sprintf( __( 'RSS Error: %s' ), $args[ 'error' ] ) . '</strong></p>';
	}

	$esc_number = esc_attr( $args['number'] );
	if ( $inputs[ 'url' ] ) :
?>
	<p><label for="rss-url-<?php echo $esc_number; ?>"><?php _e( 'Enter the RSS feed URL here:' ); ?></label>
	<input class="widefat" id="rss-url-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][url]" type="text" value="<?php echo esc_url( $args[ 'url' ] ); ?>" /></p>
<?php endif; if ( $inputs['title'] ) : ?>
	<p><label for="rss-title-<?php echo $esc_number; ?>"><?php _e( 'Give the feed a title (optional):' ); ?></label>
	<input class="widefat" id="rss-title-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][title]" type="text" value="<?php echo esc_attr( $args['title'] ); ?>" /></p>
<?php endif; if ( $inputs['items'] ) : ?>
	<p><label for="rss-items-<?php echo $esc_number; ?>"><?php _e( 'How many items would you like to display?' ); ?></label>
	<select id="rss-items-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][items]">
	<?php
	for ( $i = 1; $i <= 20; ++$i ) {
		echo "<option value='$i' " . selected( $args['items'], $i, false ) . ">$i</option>";
	}
	?>
	</select></p>
<?php endif; if ( $inputs['show_summary'] ) : ?>
	<p><input id="rss-show-summary-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_summary]" type="checkbox" value="1" <?php checked( $args['show_summary'] ); ?> />
	<label for="rss-show-summary-<?php echo $esc_number; ?>"><?php _e( 'Display item content?' ); ?></label></p>
<?php endif; if ( $inputs['show_author'] ) : ?>
	<p><input id="rss-show-author-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_author]" type="checkbox" value="1" <?php checked( $args['show_author'] ); ?> />
	<label for="rss-show-author-<?php echo $esc_number; ?>"><?php _e( 'Display item author if available?' ); ?></label></p>
<?php endif; if ( $inputs['show_date'] ) : ?>
	<p><input id="rss-show-date-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_date]" type="checkbox" value="1" <?php checked( $args['show_date'] ); ?>/>
	<label for="rss-show-date-<?php echo $esc_number; ?>"><?php _e( 'Display item date?' ); ?></label></p>
<?php
	endif;
	foreach ( array_keys( $default_inputs ) as $input ) :
		if ( 'hidden' === $inputs[ $input ] ) :
			$id = str_replace( '_', '-', $input );
?>
	<input type="hidden" id="rss-<?php echo esc_attr( $id ); ?>-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][<?php echo esc_attr( $input ); ?>]" value="<?php echo esc_attr( $args[ $input ] ); ?>" />
<?php
		endif;
	endforeach;
}

/**
 * Process RSS feed widget data and optionally retrieve feed items.
 *
 * The feed widget can not have more than 20 items or it will reset back to the
 * default, which is 10.
 *
 * The resulting array has the feed title, feed url, feed link (from channel),
 * feed items, error (if any), and whether to show summary, author, and date.
 * All respectively in the order of the array elements.
 *
 * @since 2.5.0
 *
 * @param array $widget_rss RSS widget feed data. Expects unescaped data.
 * @param bool $check_feed Optional, default is true. Whether to check feed for errors.
 * @return array
 */
function wp_widget_rss_process( $widget_rss, $check_feed = true ) {
	$items = (int) $widget_rss['items'];
	if ( $items < 1 || 20 < $items ) {
		$items = 10;
	}
	$url           = esc_url_raw( strip_tags( $widget_rss[ 'url' ] ) );
	$title         = isset( $widget_rss['title'] ) ? trim( strip_tags( $widget_rss['title'] ) ) : '';
	$show_summary  = isset( $widget_rss['show_summary'] ) ? (int) $widget_rss['show_summary'] : 0;
	$show_author   = isset( $widget_rss['show_author'] ) ? (int) $widget_rss['show_author'] : 0;
	$show_date     = isset( $widget_rss['show_date'] ) ? (int) $widget_rss['show_date'] : 0;

	if ( $check_feed ) {
		$rss = fetch_feed( $url );
		$error = false;
		$link = '';
		if ( is_wp_error( $rss ) ) {
			$error = $rss->get_error_message();
		} else {
			$link = esc_url( strip_tags( $rss->get_permalink() ) );
			while ( stristr( $link, 'http' ) != $link ) {
				$link = substr( $link, 1 );
			}

			$rss->__destruct();
			unset( $rss );
		}
	}

	return compact( 'title', 'url', 'link', 'items', 'error', 'show_summary', 'show_author', 'show_date' );
}

/**
 * Registers all of the default WordPress widgets on startup.
 *
 * Calls {@see 'widgets_init'} action after all of the WordPress widgets have been registered.
 *
 * @since 2.2.0
 */
function wp_widgets_init() {
	if ( ! is_blog_installed() ) {
		return;
	}

	register_widget( 'WP_Widget_Pages' );

	register_widget( 'WP_Widget_Calendar' );

	register_widget( 'WP_Widget_Archives' );

	if ( get_option( 'link_manager_enabled' ) ) {
		register_widget( 'WP_Widget_Links' );
	}

	register_widget( 'WP_Widget_Meta' );

	register_widget( 'WP_Widget_Search' );

	register_widget( 'WP_Widget_Text' );

	register_widget( 'WP_Widget_Categories' );

	register_widget( 'WP_Widget_Recent_Posts' );

	register_widget( 'WP_Widget_Recent_Comments' );

	register_widget( 'WP_Widget_RSS' );

	register_widget( 'WP_Widget_Tag_Cloud' );

	register_widget( 'WP_Nav_Menu_Widget' );

	/**
	 * Fires after all default WordPress widgets have been registered.
	 *
	 * @since 2.2.0
	 */
	do_action( 'widgets_init' );
}
