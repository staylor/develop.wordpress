<?php
namespace WP\Admin;
/**
 * Screen API: Screen class
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.4.0
 */

use WP\Mustache;
use function WP\getApp;

/**
 * Core class used to implement an admin screen API.
 *
 * @since 3.3.0
 */
final class Screen {
	use Mustache;

	protected $app;
	/**
	 * Any action associated with the screen. 'add' for *-add.php and *-new.php screens. Empty otherwise.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $action;

	/**
	 * The base type of the screen. This is typically the same as $id but with any post types and taxonomies stripped.
	 * For example, for an $id of 'edit-post' the base is 'edit'.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $base;

	/**
	 * The number of columns to display. Access with get_columns().
	 *
	 * @since 3.4.0
	 * @var int
	 */
	private $columns = 0;

	/**
	 * The unique ID of the screen.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $id;

	/**
	 * Which admin the screen is in. network | user | site | false
	 *
	 * @since 3.5.0
	 * @var string
	 */
	protected $in_admin;

	/**
	 * The base menu parent.
	 * This is derived from $parent_file by removing the query string and any .php extension.
	 * $parent_file values of 'edit.php?post_type=page' and 'edit.php?post_type=post' have a $parent_base of 'edit'.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $parent_base;

	/**
	 * The parent_file for the screen per the admin menu system.
	 * Some $parent_file values are 'edit.php?post_type=page', 'edit.php', and 'options-general.php'.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $parent_file;

	/**
	 * The post type associated with the screen, if any.
	 * The 'edit.php?post_type=page' screen has a post type of 'page'.
	 * The 'edit-tags.php?taxonomy=$taxonomy&post_type=page' screen has a post type of 'page'.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $post_type;

	/**
	 * The taxonomy associated with the screen, if any.
	 * The 'edit-tags.php?taxonomy=category' screen has a taxonomy of 'category'.
	 * @since 3.3.0
	 * @var string
	 */
	public $taxonomy;

	/**
	 * The help tab data associated with the screen, if any.
	 *
	 * @since 3.3.0
	 * @var array
	 */
	private $_help_tabs = [];

	/**
	 * The help sidebar data associated with screen, if any.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	private $_help_sidebar = '';

 	/**
	 * The accessible hidden headings and text associated with the screen, if any.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	private $_screen_reader_content = [];

	/**
	 * Stores old string-based help.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $_old_compat_help = [];

	/**
	 * The screen options associated with screen, if any.
	 *
	 * @since 3.3.0
	 * @var array
	 */
	private $_options = [];

	/**
	 * The screen object registry.
	 *
	 * @since 3.3.0
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $_registry = [];

	/**
	 * Stores the result of the public show_screen_options function.
	 *
	 * @since 3.3.0
	 * @var bool
	 */
	private $_show_screen_options;

	/**
	 * Stores the 'screen_settings' section of screen options.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	private $_screen_settings;

	/**
	 * Fetches a screen object.
	 *
	 * @since 3.3.0
	 *
	 * @static
	 *
	 * @global string $hook_suffix
	 *
	 * @param string|Screen $hook_name Optional. The hook name (also known as the hook suffix) used to determine the screen.
	 * 	                                  Defaults to the current $hook_suffix global.
	 * @return Screen Screen object.
	 */
	public static function get( $hook_name = '' ) {
		if ( $hook_name instanceof Screen ) {
			return $hook_name;
		}

		$post_type = $taxonomy = null;
		$in_admin = false;
		$action = '';

		if ( $hook_name ) {
			$id = $hook_name;
		} else {
			$id = $GLOBALS['hook_suffix'];
		}
		// For those pesky meta boxes.
		if ( $hook_name && post_type_exists( $hook_name ) ) {
			$post_type = $id;
			$id = 'post'; // changes later. ends up being $base.
		} else {
			if ( '.php' == substr( $id, -4 ) ) {
				$id = substr( $id, 0, -4 );
			}

			if ( 'post-new' == $id || 'link-add' == $id || 'media-new' == $id || 'user-new' == $id ) {
				$id = substr( $id, 0, -4 );
				$action = 'add';
			}
		}

		if ( ! $post_type && $hook_name ) {
			if ( '-network' == substr( $id, -8 ) ) {
				$id = substr( $id, 0, -8 );
				$in_admin = 'network';
			} elseif ( '-user' == substr( $id, -5 ) ) {
				$id = substr( $id, 0, -5 );
				$in_admin = 'user';
			}

			$id = sanitize_key( $id );
			if ( 'edit-comments' != $id && 'edit-tags' != $id && 'edit-' == substr( $id, 0, 5 ) ) {
				$maybe = substr( $id, 5 );
				if ( taxonomy_exists( $maybe ) ) {
					$id = 'edit-tags';
					$taxonomy = $maybe;
				} elseif ( post_type_exists( $maybe ) ) {
					$id = 'edit';
					$post_type = $maybe;
				}
			}

			if ( ! $in_admin ) {
				$in_admin = 'site';
			}
		} else {
			if ( defined( 'WP_NETWORK_ADMIN' ) && WP_NETWORK_ADMIN ) {
				$in_admin = 'network';
			} elseif ( defined( 'WP_USER_ADMIN' ) && WP_USER_ADMIN ) {
				$in_admin = 'user';
			} else {
				$in_admin = 'site';
			}
		}

		if ( 'index' == $id ) {
			$id = 'dashboard';
		} elseif ( 'front' == $id ) {
			$in_admin = false;
		}

		$base = $id;
		$app = getApp();
		$_request = $app['request']->attributes;
		$_get = $app['request']->query;
		$_post = $app['request']->request;

		// If this is the current screen, see if we can be more accurate for post types and taxonomies.
		if ( ! $hook_name ) {
			if ( $_request->get( 'post_type' ) ) {
				$post_type = post_type_exists( $_request->get( 'post_type' ) ) ? $_request->get( 'post_type' ) : false;
			}

			if ( $_request->get( 'taxonomy' ) ) {
				$taxonomy = taxonomy_exists( $_request->get( 'taxonomy' ) ) ? $_request->get( 'taxonomy' ) : false;
			}

			switch ( $base ) {
			case 'post' :
				if ( $_get->getInt( 'post' ) ) {
					$post_id = $_get->getInt( 'post' );
				} elseif ( $_post->getInt( 'post_ID' ) ) {
					$post_id = $_post->getInt( 'post_ID' );
				} else {
					$post_id = 0;
				}

				if ( $post_id ) {
					$post = get_post( $post_id );
					if ( $post ) {
						$post_type = $post->post_type;
					}
				}
				break;
			case 'edit-tags' :
			case 'term' :
				if ( null === $post_type && is_object_in_taxonomy( 'post', $taxonomy ? $taxonomy : 'post_tag' ) ) {
					$post_type = 'post';
				}
				break;
			}
		}

		switch ( $base ) {
		case 'post' :
			if ( null === $post_type ) {
				$post_type = 'post';
			}
			$id = $post_type;
			break;
		case 'edit' :
			if ( null === $post_type ) {
				$post_type = 'post';
			}
			$id .= '-' . $post_type;
			break;
		case 'edit-tags' :
		case 'term' :
			if ( null === $taxonomy ) {
				$taxonomy = 'post_tag';
			}
			// The edit-tags ID does not contain the post type. Look for it in the request.
			if ( null === $post_type ) {
				$post_type = 'post';
				if ( $_request->get( 'post_type' ) && post_type_exists( $_request->get( 'post_type' ) ) ) {
					$post_type = $_request->get( 'post_type' );
				}
			}

			$id = 'edit-' . $taxonomy;
			break;
		}

		if ( 'network' == $in_admin ) {
			$id   .= '-network';
			$base .= '-network';
		} elseif ( 'user' == $in_admin ) {
			$id   .= '-user';
			$base .= '-user';
		}

		if ( isset( self::$_registry[ $id ] ) ) {
			$screen = self::$_registry[ $id ];
			if ( $screen === get_current_screen() ) {
				return $screen;
			}
		} else {
			$screen = new Screen();
			$screen->id = $id;
		}

		$screen->base       = $base;
		$screen->action     = $action;
		$screen->post_type  = (string) $post_type;
		$screen->taxonomy   = (string) $taxonomy;
		$screen->in_admin   = $in_admin;

		self::$_registry[ $id ] = $screen;

		return $screen;
	}

	/**
	 * Makes the screen object the current screen.
	 *
	 * @see set_current_screen()
	 * @since 3.3.0
	 */
	public function set_current_screen() {
		$this->app->current_screen = $this;

		/**
		 * Fires after the current screen has been set.
		 *
		 * @since 3.0.0
		 *
		 * @param Screen $current_screen Current Screen object.
		 */
		do_action( 'current_screen', $this->app->current_screen );
	}

	/**
	 * Constructor
	 *
	 * @since 3.3.0
	 */
	private function __construct() {
		$this->app = getApp();

		$this->setConfig( [
			'helpers' => [
				'l10n' => new L10N()
			]
		] );
	}

	/**
	 * Indicates whether the screen is in a particular admin
	 *
	 * @since 3.5.0
	 *
	 * @param string $admin The admin to check against (network | user | site).
	 *                      If empty any of the three admins will result in true.
	 * @return bool True if the screen is in the indicated admin, false otherwise.
	 */
	public function in_admin( $admin = null ) {
		if ( empty( $admin ) ) {
			return (bool) $this->in_admin;
		}
		return ( $admin == $this->in_admin );
	}

	/**
	 * Sets the old string-based contextual help for the screen for backward compatibility.
	 *
	 * @since 3.3.0
	 *
	 * @static
	 *
	 * @param Screen $screen A screen object.
	 * @param string $help Help text.
	 */
	public static function add_old_compat_help( $screen, $help ) {
		self::$_old_compat_help[ $screen->id ] = $help;
	}

	/**
	 * Set the parent information for the screen.
	 * This is called in admin-header.php after the menu parent for the screen has been determined.
	 *
	 * @since 3.3.0
	 *
	 * @param string $parent_file The parent file of the screen. Typically the $parent_file global.
	 */
	public function set_parentage( $parent_file ) {
		$this->parent_file = $parent_file;
		list( $this->parent_base ) = explode( '?', $parent_file );
		$this->parent_base = str_replace( '.php', '', $this->parent_base );
	}

	/**
	 * Adds an option for the screen.
	 * Call this in template files after admin.php is loaded and before admin-header.php is loaded to add screen options.
	 *
	 * @since 3.3.0
	 *
	 * @param string $option Option ID
	 * @param mixed $args Option-dependent arguments.
	 */
	public function add_option( $option, $args = [] ) {
		$this->_options[ $option ] = $args;
	}

	/**
	 * Remove an option from the screen.
	 *
	 * @since 3.8.0
	 *
	 * @param string $option Option ID.
	 */
	public function remove_option( $option ) {
		unset( $this->_options[ $option ] );
	}

	/**
	 * Remove all options from the screen.
	 *
	 * @since 3.8.0
	 */
	public function remove_options() {
		$this->_options = [];
	}

	/**
	 * Get the options registered for the screen.
	 *
	 * @since 3.8.0
	 *
	 * @return array Options with arguments.
	 */
	public function get_options() {
		return $this->_options;
	}

	/**
	 * Gets the arguments for an option for the screen.
	 *
	 * @since 3.3.0
	 *
	 * @param string $option Option name.
	 * @param string $key    Optional. Specific array key for when the option is an array.
	 *                       Default false.
	 * @return string The option value if set, null otherwise.
	 */
	public function get_option( $option, $key = false ) {
		if ( ! isset( $this->_options[ $option ] ) ) {
			return null;
		}

		if ( $key ) {
			if ( isset( $this->_options[ $option ][ $key ] ) ) {
				return $this->_options[ $option ][ $key ];
			}
			return null;
		}
		return $this->_options[ $option ];
	}

	/**
	 * Gets the help tabs registered for the screen.
	 *
	 * @since 3.4.0
	 * @since 4.4.0 Help tabs are ordered by their priority.
	 *
	 * @return array Help tabs with arguments.
	 */
	public function get_help_tabs() {
		$help_tabs = $this->_help_tabs;

		$priorities = [];
		foreach ( $help_tabs as $help_tab ) {
			if ( isset( $priorities[ $help_tab['priority'] ] ) ) {
				$priorities[ $help_tab['priority'] ][] = $help_tab;
			} else {
				$priorities[ $help_tab['priority'] ] = [ $help_tab ];
			}
		}

		ksort( $priorities );

		$sorted = [];
		foreach ( $priorities as $list ) {
			foreach ( $list as $tab ) {
				$sorted[ $tab['id'] ] = $tab;
			}
		}

		return $sorted;
	}

	/**
	 * Gets the arguments for a help tab.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id Help Tab ID.
	 * @return array Help tab arguments.
	 */
	public function get_help_tab( $id ) {
		if ( ! isset( $this->_help_tabs[ $id ] ) ) {
			return null;
		}
		return $this->_help_tabs[ $id ];
	}

	/**
	 * Add a help tab to the contextual help for the screen.
	 * Call this on the load-$pagenow hook for the relevant screen.
	 *
	 * @since 3.3.0
	 * @since 4.4.0 The `$priority` argument was added.
	 *
	 * @param array $args {
	 *     Array of arguments used to display the help tab.
	 *
	 *     @type string $title    Title for the tab. Default false.
	 *     @type string $id       Tab ID. Must be HTML-safe. Default false.
	 *     @type string $content  Optional. Help tab content in plain text or HTML. Default empty string.
	 *     @type string $callback Optional. A callback to generate the tab content. Default false.
	 *     @type int    $priority Optional. The priority of the tab, used for ordering. Default 10.
	 * }
	 */
	public function add_help_tab( $args ) {
		$defaults = [
			'title'    => false,
			'id'       => false,
			'content'  => '',
			'callback' => false,
			'priority' => 10,
		];
		$params = wp_parse_args( $args, $defaults );

		$params['id'] = sanitize_html_class( $params['id'] );

		// Ensure we have an ID and title.
		if ( ! $params['id'] || ! $params['title'] ) {
			return;
		}

		// Allows for overriding an existing tab with that ID.
		$this->_help_tabs[ $params['id'] ] = $params;
	}

	/**
	 * Removes a help tab from the contextual help for the screen.
	 *
	 * @since 3.3.0
	 *
	 * @param string $id The help tab ID.
	 */
	public function remove_help_tab( $id ) {
		unset( $this->_help_tabs[ $id ] );
	}

	/**
	 * Removes all help tabs from the contextual help for the screen.
	 *
	 * @since 3.3.0
	 */
	public function remove_help_tabs() {
		$this->_help_tabs = [];
	}

	/**
	 * Gets the content from a contextual help sidebar.
	 *
	 * @since 3.4.0
	 *
	 * @return string Contents of the help sidebar.
	 */
	public function get_help_sidebar() {
		return $this->_help_sidebar;
	}

	/**
	 * Add a sidebar to the contextual help for the screen.
	 * Call this in template files after admin.php is loaded and before admin-header.php is loaded to add a sidebar to the contextual help.
	 *
	 * @since 3.3.0
	 *
	 * @param string $content Sidebar content in plain text or HTML.
	 */
	public function set_help_sidebar( $content ) {
		$this->_help_sidebar = $content;
	}

	/**
	 * Gets the number of layout columns the user has selected.
	 *
	 * The layout_columns option controls the max number and default number of
	 * columns. This method returns the number of columns within that range selected
	 * by the user via Screen Options. If no selection has been made, the default
	 * provisioned in layout_columns is returned. If the screen does not support
	 * selecting the number of layout columns, 0 is returned.
	 *
	 * @since 3.4.0
	 *
	 * @return int Number of columns to display.
	 */
	public function get_columns() {
		return $this->columns;
	}

 	/**
	 * Get the accessible hidden headings and text used in the screen.
	 *
	 * @since 4.4.0
	 *
	 * @see set_screen_reader_content() For more information on the array format.
	 *
	 * @return array An associative array of screen reader text strings.
	 */
	public function get_screen_reader_content() {
		return $this->_screen_reader_content;
	}

	/**
	 * Get a screen reader text string.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Screen reader text array named key.
	 * @return string Screen reader text string.
	 */
	public function get_screen_reader_text( $key ) {
		if ( ! isset( $this->_screen_reader_content[ $key ] ) ) {
			return null;
		}
		return $this->_screen_reader_content[ $key ];
	}

	/**
	 * Add accessible hidden headings and text for the screen.
	 *
	 * @since 4.4.0
	 *
	 * @param array $content {
	 *     An associative array of screen reader text strings.
	 *
	 *     @type string $heading_views      Screen reader text for the filter links heading.
	 *                                      Default 'Filter items list'.
	 *     @type string $heading_pagination Screen reader text for the pagination heading.
	 *                                      Default 'Items list navigation'.
	 *     @type string $heading_list       Screen reader text for the items list heading.
	 *                                      Default 'Items list'.
	 * }
	 */
	public function set_screen_reader_content( $content = [] ) {
		$defaults = [
			'heading_views'      => __( 'Filter items list' ),
			'heading_pagination' => __( 'Items list navigation' ),
			'heading_list'       => __( 'Items list' ),
		];

		$this->_screen_reader_content = wp_parse_args( $content, $defaults );
	}

	/**
	 * Remove all the accessible hidden headings and text for the screen.
	 *
	 * @since 4.4.0
	 */
	public function remove_screen_reader_content() {
		$this->_screen_reader_content = [];
	}

	/**
	 * Render the screen's help section.
	 *
	 * This will trigger the deprecated filters for backward compatibility.
	 *
	 * @since 3.3.0
	 *
	 * @global string $screen_layout_columns
	 */
	public function render_screen_meta() {

		/**
		 * Filters the legacy contextual help list.
		 *
		 * @since 2.7.0
		 * @deprecated 3.3.0 Use get_current_screen()->add_help_tab() or
		 *                   get_current_screen()->remove_help_tab() instead.
		 *
		 * @param array     $old_compat_help Old contextual help.
		 * @param Screen $this            Current Screen instance.
		 */
		self::$_old_compat_help = apply_filters( 'contextual_help_list', self::$_old_compat_help, $this );

		$old_help = isset( self::$_old_compat_help[ $this->id ] ) ? self::$_old_compat_help[ $this->id ] : '';

		/**
		 * Filters the legacy contextual help text.
		 *
		 * @since 2.7.0
		 * @deprecated 3.3.0 Use get_current_screen()->add_help_tab() or
		 *                   get_current_screen()->remove_help_tab() instead.
		 *
		 * @param string    $old_help  Help text that appears on the screen.
		 * @param string    $screen_id Screen ID.
		 * @param Screen $this      Current Screen instance.
		 *
		 */
		$old_help = apply_filters( 'contextual_help', $old_help, $this->id, $this );

		// Default help only if there is no old-style block of text and no new-style help tabs.
		if ( empty( $old_help ) && ! $this->get_help_tabs() ) {

			/**
			 * Filters the default legacy contextual help text.
			 *
			 * @since 2.8.0
			 * @deprecated 3.3.0 Use get_current_screen()->add_help_tab() or
			 *                   get_current_screen()->remove_help_tab() instead.
			 *
			 * @param string $old_help_default Default contextual help text.
			 */
			$default_help = apply_filters( 'default_contextual_help', '' );
			if ( $default_help ) {
				$old_help = '<p>' . $default_help . '</p>';
			}
		}

		if ( $old_help ) {
			$this->add_help_tab( [
				'id'      => 'old-contextual-help',
				'title'   => __('Overview'),
				'content' => $old_help,
			] );
		}

		$help_sidebar = $this->get_help_sidebar();

		$help_class = 'hidden';
		if ( ! $help_sidebar ) {
			$help_class .= ' no-sidebar';
		}
		// Time to render!

		$tabs = array_values( $this->get_help_tabs() );
		$content = $tabs;
		if ( ! empty( $tabs ) ) {
			$tabs[0]['class'] = 'active';
		}

		foreach ( $content as $i => &$c ) {
			if ( 0 === $i ) {
				$c['classes'] = 'help-tab-content active';
			} else {
				$c['classes'] = 'help-tab-content';
			}

			// If it exists, fire tab callback.
			if ( ! empty( $c['callback'] ) ) {
				ob_start();
				call_user_func_array( $c['callback'], [ $this, $c ] );
				$c['callback_output'] = ob_get_clean();
			}
		}

		// Setup layout columns

		/**
		 * Filters the array of screen layout columns.
		 *
		 * This hook provides back-compat for plugins using the back-compat
		 * Filters instead of add_screen_option().
		 *
		 * @since 2.8.0
		 *
		 * @param array     $empty_columns Empty array.
		 * @param string    $screen_id     Screen ID.
		 * @param Screen $this          Current Screen instance.
		 */
		$columns = apply_filters( 'screen_layout_columns', [], $this->id, $this );

		if ( ! empty( $columns ) && isset( $columns[ $this->id ] ) ) {
			$this->add_option( 'layout_columns', [ 'max' => $columns[ $this->id ] ] );
		}

		if ( $this->get_option( 'layout_columns' ) ) {
			$this->columns = (int) get_user_option( "screen_layout_{$this->id}" );

			if ( ! $this->columns && $this->get_option( 'layout_columns', 'default' ) ) {
				$this->columns = $this->get_option( 'layout_columns', 'default' );
			}
		}

		// Add screen options
		$screen_options = '';
		if ( $this->show_screen_options() ) {
			ob_start();
			$this->render_screen_options();
			$screen_options = ob_get_clean();
		}

		echo $this->render( 'admin/screen/screen-meta', [
			'help_class' => $help_class,
			'help_tabs' => $tabs,
			'help_sidebar' => $help_sidebar,
			'help_tab_panels' => $content,
			'screen_options' => $screen_options,
		] );
	}

	/**
	 *
	 * @global array $wp_meta_boxes
	 *
	 * @return bool
	 */
	public function show_screen_options() {
		global $wp_meta_boxes;

		if ( is_bool( $this->_show_screen_options ) ) {
			return $this->_show_screen_options;
		}

		$columns = get_column_headers( $this );

		$show_screen = ! empty( $wp_meta_boxes[ $this->id ] ) || $columns || $this->get_option( 'per_page' );

		switch ( $this->base ) {
		case 'widgets':
			$this->_screen_settings = '<p><a id="access-on" href="widgets.php?widgets-access=on">' . __('Enable accessibility mode') . '</a><a id="access-off" href="widgets.php?widgets-access=off">' . __('Disable accessibility mode') . "</a></p>\n";
			break;
		case 'post' :
			$expand = '<fieldset class="editor-expand hidden"><legend>' . __( 'Additional settings' ) . '</legend><label for="editor-expand-toggle">';
			$expand .= '<input type="checkbox" id="editor-expand-toggle"' . checked( get_user_setting( 'editor_expand', 'on' ), 'on', false ) . ' />';
			$expand .= __( 'Enable full-height editor and distraction-free functionality.' ) . '</label></fieldset>';
			$this->_screen_settings = $expand;
			break;
		default:
			$this->_screen_settings = '';
			break;
		}

		/**
		 * Filters the screen settings text displayed in the Screen Options tab.
		 *
		 * This filter is currently only used on the Widgets screen to enable
		 * accessibility mode.
		 *
		 * @since 3.0.0
		 *
		 * @param string    $screen_settings Screen settings.
		 * @param Screen $this            Screen object.
		 */
		$this->_screen_settings = apply_filters( 'screen_settings', $this->_screen_settings, $this );

		if ( $this->_screen_settings || $this->_options ) {
			$show_screen = true;
		}

		/**
		 * Filters whether to show the Screen Options tab.
		 *
		 * @since 3.2.0
		 *
		 * @param bool      $show_screen Whether to show Screen Options tab.
		 *                               Default true.
		 * @param Screen $this        Current Screen instance.
		 */
		$this->_show_screen_options = apply_filters( 'screen_options_show_screen', $show_screen, $this );
		return $this->_show_screen_options;
	}

	/**
	 * Render the screen options tab.
	 *
	 * @since 3.3.0
	 *
	 * @param array $opts {
	 *     @type bool $wrap  Whether the screen-options-wrap div will be included. Defaults to true.
	 * }
	 */
	public function render_screen_options( $opts = [] ) {
		$options = wp_parse_args( $opts, [
			'wrap' => true,
		] );

		$wrapper_start = $wrapper_end = $form_start = $form_end = '';

		// Output optional wrapper.
		if ( $options['wrap'] ) {
			$wrapper_start = '<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="' . esc_attr__( 'Screen Options Tab' ) . '">';
			$wrapper_end = '</div>';
		}

		// Don't output the form and nonce for the widgets accessibility mode links.
		if ( 'widgets' !== $this->base ) {
			$form_start = "\n<form id='adv-settings' method='post'>\n";
			$form_end = "\n" . wp_nonce_field( 'screen-options-nonce', 'screenoptionnonce', false, false ) . "\n</form>\n";
		}

		echo $wrapper_start . $form_start;

		$this->render_meta_boxes_preferences();
		$this->render_list_table_columns_preferences();
		$this->render_screen_layout();
		$this->render_per_page_options();
		$this->render_view_mode();
		echo $this->_screen_settings;

		/**
		 * Filters whether to show the Screen Options submit button.
		 *
		 * @since 4.4.0
		 *
		 * @param bool      $show_button Whether to show Screen Options submit button.
		 *                               Default false.
		 * @param Screen $this        Current Screen instance.
		 */
		$show_button = apply_filters( 'screen_options_show_submit', false, $this );

		if ( $show_button ) {
			submit_button( __( 'Apply' ), 'primary', 'screen-options-apply', true );
		}

		echo $form_end . $wrapper_end;
	}

	public function meta_box_prefs() {
		global $wp_meta_boxes;

		if ( empty($wp_meta_boxes[ $this->id ] ) ) {
			return;
		}

		$hidden = get_hidden_meta_boxes( $this );
		$boxes = $wp_meta_boxes[ $this->id ];
		$priorities = [ 'high', 'core', 'default', 'low' ];

		$prefs = [];
		foreach ( array_keys( $boxes ) as $context ) {
			foreach ( $priorities as $priority ) {
				if ( ! isset( $boxes[ $context ][ $priority ] ) ) {
					continue;
				}
				foreach ( $boxes[ $context ][ $priority ] as $box ) {
					if ( false == $box || ! $box['title'] ) {
						continue;
					}
					// Submit box cannot be hidden
					if ( 'submitdiv' === $box['id'] || 'linksubmitdiv' === $box['id'] ) {
						continue;
					}

					$widget_title = $box['title'];

					if ( is_array( $box['args'] ) && isset( $box['args']['__widget_basename'] ) ) {
						$widget_title = $box['args']['__widget_basename'];
					}

					$prefs[] = [
						'id' => $box['id'],
						'checked' => checked( in_array( $box['id'], $hidden ), false, false ),
						'title' => $widget_title,
					];
				}
			}
		}
		return $prefs;
	}

	/**
	 * Render the meta boxes preferences.
	 *
	 * @since 4.4.0
	 *
	 * @global array $wp_meta_boxes
	 */
	public function render_meta_boxes_preferences() {
		global $wp_meta_boxes;

		if ( ! isset( $wp_meta_boxes[ $this->id ] ) ) {
			return;
		}

		$show_welcome = 'dashboard' === $this->id && has_action( 'welcome_panel' ) && current_user_can( 'edit_theme_options' );

		if ( $show_welcome ) {
			if ( isset( $_GET['welcome'] ) ) {
				$welcome_checked = empty( $_GET['welcome'] ) ? 0 : 1;
				update_user_meta( get_current_user_id(), 'show_welcome_panel', $welcome_checked );
			} else {
				$welcome_checked = get_user_meta( get_current_user_id(), 'show_welcome_panel', true );
				if ( 2 == $welcome_checked && wp_get_current_user()->user_email != get_option( 'admin_email' ) ) {
					$welcome_checked = false;
				}
			}
		}

		echo $this->render( 'admin/screen/meta-boxes-preferences', [
			'prefs' => $this->meta_box_prefs(),
			'show_welcome' => $show_welcome,
			'welcome_checked' => checked( (bool) $welcome_checked, true, false ),
		] );
	}

	/**
	 * Render the list table columns preferences.
	 *
	 * @since 4.4.0
	 */
	public function render_list_table_columns_preferences() {

		$columns = get_column_headers( $this );
		$hidden  = get_hidden_columns( $this );

		if ( ! $columns ) {
			return;
		}

		$legend = ! empty( $columns['_title'] ) ? $columns['_title'] : __( 'Columns' );

		$special = [ '_title', 'cb', 'comment', 'media', 'name', 'title', 'username', 'blogname' ];

		$prefs = [];
		foreach ( $columns as $column => $title ) {
			// Can't hide these for they are special
			if ( in_array( $column, $special ) ) {
				continue;
			}

			if ( empty( $title ) ) {
				continue;
			}

			if ( 'comments' == $column ) {
				$title = __( 'Comments' );
			}

			$id = "$column-hide";

			$prefs[] = [
				'column' => $column,
				'title' => $title,
				'id' => $id,
				'checked' => checked( ! in_array( $column, $hidden ), true, false ),
			];
		}

		echo $this->render( 'admin/screen/list-table-columns-preferences', [
			'legend' => $legend,
			'prefs' => $prefs,
		] );
	}

	/**
	 * Render the option for number of columns on the page
	 *
	 * @since 3.3.0
	 */
	public function render_screen_layout() {
		if ( ! $this->get_option( 'layout_columns' ) ) {
			return;
		}

		$screen_layout_columns = $this->get_columns();
		$num = $this->get_option( 'layout_columns', 'max' );

		$prefs = [];
		for ( $i = 1; $i <= $num; ++$i ) {
			$prefs[] = [
				'checked' => checked( $screen_layout_columns, $i, false ),
				'i' => $i,
				'text' => sprintf( _n( '%s column', '%s columns', $i ), number_format_i18n( $i ) )
			];
		}

		echo $this->render( 'admin/screen/screen-layout', [
			'prefs' => $prefs
		] );
	}

	/**
	 * Render the items per page option
	 *
	 * @since 3.3.0
	 */
	public function render_per_page_options() {
		if ( null === $this->get_option( 'per_page' ) ) {
			return;
		}

		$per_page_label = $this->get_option( 'per_page', 'label' );
		if ( null === $per_page_label ) {
			$per_page_label = __( 'Number of items per page:' );
		}

		$option = $this->get_option( 'per_page', 'option' );
		if ( ! $option ) {
			$option = str_replace( '-', '_', "{$this->id}_per_page" );
		}

		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $this->get_option( 'per_page', 'default' );
			if ( ! $per_page ) {
				$per_page = 20;
			}
		}

		if ( 'edit_comments_per_page' == $option ) {
			$_request = $this->app['request']->attributes;
			$comment_status = $_request->has( 'comment_status' ) ? $_request->get( 'comment_status' ) : 'all';

			/** This filter is documented in wp-admin/includes/class-wp-comments-list-table.php */
			$per_page = apply_filters( 'comments_per_page', $per_page, $comment_status );
		} elseif ( 'categories_per_page' == $option ) {
			/** This filter is documented in wp-admin/includes/class-wp-terms-list-table.php */
			$per_page = apply_filters( 'edit_categories_per_page', $per_page );
		} else {
			/** This filter is documented in wp-admin/includes/class-wp-list-table.php */
			$per_page = apply_filters( $option, $per_page );
		}

		// Back compat
		if ( isset( $this->post_type ) ) {
			/** This filter is documented in wp-admin/includes/post.php */
			$per_page = apply_filters( 'edit_posts_per_page', $per_page, $this->post_type );
		}

		// This needs a submit button
		add_filter( 'screen_options_show_submit', '__return_true' );

		echo $this->render( 'admin/screen/per-page-options', [
			'option' => $option,
			'per_page' => $per_page,
			'per_page_label' => $per_page_label,
		] );
	}

	/**
	 * Render the list table view mode preferences.
	 *
	 * @since 4.4.0
	 */
	public function render_view_mode() {
		$screen = get_current_screen();

		// Currently only enabled for posts lists
		if ( 'edit' !== $screen->base ) {
			return;
		}

		$view_mode_post_types = get_post_types( [
			'hierarchical' => false,
			'show_ui' => true
		] );

		/**
		 * Filters the post types that have different view mode options.
		 *
		 * @since 4.4.0
		 *
		 * @param array $view_mode_post_types Array of post types that can change view modes.
		 *                                    Default hierarchical post types with show_ui on.
		 */
		$view_mode_post_types = apply_filters( 'view_mode_post_types', $view_mode_post_types );

		if ( ! in_array( $this->post_type, $view_mode_post_types ) ) {
			return;
		}

		global $mode;

		// This needs a submit button
		add_filter( 'screen_options_show_submit', '__return_true' );

		echo $this->render( 'admin/screen/view-mode', [
			'list_checked' => checked( 'list', $mode, false ),
			'excerpt_checked' => checked( 'excerpt', $mode, false ),
		] );
	}

	/**
	 * Render screen reader text.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The screen reader text array named key.
	 * @param string $tag Optional. The HTML tag to wrap the screen reader text. Default h2.
	 */
	public function render_screen_reader_content( $key = '', $tag = 'h2' ) {

		if ( ! isset( $this->_screen_reader_content[ $key ] ) ) {
			return;
		}

		echo $this->render( 'admin/screen/screen-reader-content', [
			'tag' => $tag,
			'content' => $this->_screen_reader_content[ $key ]
		] );
	}
}
