<?php
namespace WP\User;

use function WP\getApp;

/**
 * User API: WP\User\Roles class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Core class used to implement a user roles API.
 *
 * The role option is simple, the structure is organized by role name that store
 * the name in value of the 'name' key. The capabilities are stored as an array
 * in the value of the 'capability' key.
 *
 *     array (
 *    		'rolename' => array (
 *    			'name' => 'rolename',
 *    			'capabilities' => []
 *    		)
 *     )
 *
 * @since 2.0.0
 */
class Roles {
	/**
	 * List of roles and capabilities.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $roles;

	/**
	 * List of the role objects.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $role_objects = [];

	/**
	 * List of role names.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $role_names = [];

	/**
	 * Option name for storing role list.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var string
	 */
	public $role_key;

	/**
	 * Whether to use the database for retrieval and storage.
	 *
	 * @since 2.1.0
	 * @access public
	 * @var bool
	 */
	public $use_db = true;

	/**
	 * @since 4.7.0
	 * @access protected
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->_init();
	}

	/**
	 * Set up the object properties.
	 *
	 * The role key is set to the current prefix for the $wpdb object with
	 * 'user_roles' appended. If the $wp_user_roles global is set, then it will
	 * be used and the role option will not be updated or used.
	 *
	 * @since 2.1.0
	 * @access protected
	 */
	public function _init() {
		$app = getApp();
		$this->role_key = $app['db']->get_blog_prefix() . 'user_roles';
		$this->roles = get_option( $this->role_key );

		if ( empty( $this->roles ) ) {
			return;
		}

		$this->role_objects = [];
		$this->role_names = [];
		foreach ( array_keys( $this->roles ) as $role ) {
			$this->role_objects[ $role ] = new Role( $role, $this->roles[ $role ]['capabilities'] );
			$this->role_names[ $role ] = $this->roles[ $role ]['name'];
		}
	}

	/**
	 * Reinitialize the object
	 *
	 * Recreates the role objects. This is typically called only by switch_to_blog()
	 * after switching wpdb to a new site ID.
	 *
	 * @since 3.5.0
	 * @access public
	 */
	public function reinit() {
		// There is no need to reinit if using the wp_user_roles global.
		if ( ! $this->use_db ) {
			return;
		}
		$app = getApp();
		// Duplicated from _init() to avoid an extra function call.
		$this->role_key = $app['db']->get_blog_prefix() . 'user_roles';
		$this->roles = get_option( $this->role_key );
		if ( empty( $this->roles ) ) {
			return;
		}

		$this->role_objects = [];
		$this->role_names = [];
		foreach ( array_keys( $this->roles ) as $role ) {
			$this->role_objects[ $role ] = new Role( $role, $this->roles[ $role ]['capabilities'] );
			$this->role_names[ $role ] = $this->roles[ $role ]['name'];
		}
	}

	/**
	 * Add role name with capabilities to list.
	 *
	 * Updates the list of roles, if the role doesn't already exist.
	 *
	 * The capabilities are defined in the following format `array( 'read' => true );`
	 * To explicitly deny a role a capability you set the value for that capability to false.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $display_name Role display name.
	 * @param array $capabilities List of role capabilities in the above format.
	 * @return Role|void Role object, if role is added.
	 */
	public function add_role( $role, $display_name, $capabilities = [] ) {
		if ( empty( $role ) || isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[ $role ] = [
			'name' => $display_name,
			'capabilities' => $capabilities
		];
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}
		$this->role_objects[ $role ] = new Role( $role, $capabilities );
		$this->role_names[ $role ] = $display_name;
		return $this->role_objects[ $role ];
	}

	/**
	 * Remove role by name.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 */
	public function remove_role( $role ) {
		if ( ! isset( $this->role_objects[ $role ] ) ) {
			return;
		}

		unset(
			$this->role_objects[ $role ],
			$this->role_names[ $role ],
			$this->roles[ $role ]
		);

		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}

		if ( get_option( 'default_role' ) === $role ) {
			update_option( 'default_role', 'subscriber' );
		}
	}

	/**
	 * Add capability to role.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 * @param bool $grant Optional, default is true. Whether role is capable of performing capability.
	 */
	public function add_cap( $role, $cap, $grant = true ) {
		if ( ! isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[ $role ]['capabilities'][ $cap ] = $grant;
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}
	}

	/**
	 * Remove capability from role.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 */
	public function remove_cap( $role, $cap ) {
		if ( ! isset( $this->roles[ $role ] ) ) {
			return;
		}

		unset( $this->roles[ $role ]['capabilities'][$cap] );
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}
	}

	/**
	 * Retrieve role object by name.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @return WP_Role|null WP_Role object if found, null if the role does not exist.
	 */
	public function get_role( $role ) {
		return $this->role_objects[ $role ] ?? null;
	}

	/**
	 * Retrieve list of role names.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array List of role names.
	 */
	public function get_names() {
		return $this->role_names;
	}

	/**
	 * Whether role name is currently in the list of available roles.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name to look up.
	 * @return bool
	 */
	public function is_role( $role ) {
		return isset( $this->role_names[ $role ] );
	}
}
