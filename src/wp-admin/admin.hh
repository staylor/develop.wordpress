<?php
/**
 * WordPress Administration Bootstrap
 *
 * @package WordPress
 * @subpackage Administration
 */

require_once( dirname( __DIR__ ) . '/vendor/autoload.php' );

$app = \WP\getApp();
$_get = $app['request']->query;

/**
 * In WordPress Administration Screens
 *
 * @since 2.3.2
 */
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

if ( ! defined( 'WP_NETWORK_ADMIN' ) ) {
	define( 'WP_NETWORK_ADMIN', false );
}

if ( ! defined( 'WP_USER_ADMIN' ) ) {
	define( 'WP_USER_ADMIN', false );
}

if ( ! WP_NETWORK_ADMIN && ! WP_USER_ADMIN ) {
	define( 'WP_BLOG_ADMIN', true );
}

if ( $_get->get( 'import' ) && ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	define( 'WP_LOAD_IMPORTERS', true );
}

require_once(dirname( __DIR__ ) . '/wp-load.hh' );

nocache_headers();

if ( get_option( 'db_upgraded' ) ) {
	flush_rewrite_rules();
	update_option( 'db_upgraded',  false );

	/**
	 * Fires on the next page load after a successful DB upgrade.
	 *
	 * @since 2.8.0
	 */
	do_action( 'after_db_upgrade' );
} elseif ( get_option( 'db_version' ) != $app['wp_db_version'] && empty( $_post->all() ) ) {
	if ( ! is_multisite() ) {
		wp_redirect( admin_url( 'upgrade.php?_wp_http_referer=' . urlencode( wp_unslash( $app['request.uri'] ) ) ) );
		exit;

	/**
	 * Filters whether to attempt to perform the multisite DB upgrade routine.
	 *
	 * In single site, the user would be redirected to wp-admin/upgrade.php.
	 * In multisite, the DB upgrade routine is automatically fired, but only
	 * when this filter returns true.
	 *
	 * If the network is 50 sites or less, it will run every time. Otherwise,
	 * it will throttle itself to reduce load.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $do_mu_upgrade Whether to perform the Multisite upgrade routine. Default true.
	 */
	} elseif ( apply_filters( 'do_mu_upgrade', true ) ) {
		$c = get_blog_count();

		/*
		 * If there are 50 or fewer sites, run every time. Otherwise, throttle to reduce load:
		 * attempt to do no more than threshold value, with some +/- allowed.
		 */
		if ( $c <= 50 || ( $c > 50 && mt_rand( 0, (int)( $c / 50 ) ) == 1 ) ) {
			require_once( ABSPATH . WPINC . '/http.php' );
			$response = wp_remote_get( admin_url( 'upgrade.php?step=1' ), [ 'timeout' => 120, 'httpversion' => '1.1' ] );
			/** This action is documented in wp-admin/network/upgrade.php */
			do_action( 'after_mu_upgrade', $response );
			unset( $response);
		}
		unset( $c);
	}
}

require_once( ABSPATH . 'wp-admin/includes/admin.php' );

auth_redirect();

// Schedule trash collection
if ( ! wp_next_scheduled( 'wp_scheduled_delete' ) && ! wp_installing() ) {
	wp_schedule_event(time(), 'daily', 'wp_scheduled_delete' );
}

set_screen_options();

$date_format = __( 'F j, Y' );
$time_format = __( 'g:i a' );

wp_enqueue_script( 'common' );

$pagenow = $app['pagenow'];
$typenow = $app['typenow'];
$taxnow = $app['taxnow'];

$page_hook = null;

$editing = false;

if ( $_get->get( 'page' ) ) {
	$app->set( 'plugin_page', plugin_basename( wp_unslash( $_get->get( 'page' ) ) ) );
}

if ( WP_NETWORK_ADMIN ) {
	require( ABSPATH . 'wp-admin/network/menu.php' );
} elseif ( WP_USER_ADMIN ) {
	require( ABSPATH . 'wp-admin/user/menu.php' );
} else {
	require( ABSPATH . 'wp-admin/menu.php' );
}

if ( current_user_can( 'manage_options' ) ) {
	wp_raise_memory_limit( 'admin' );
}

/**
 * Fires as an admin screen or script is being initialized.
 *
 * Note, this does not just run on user-facing admin screens.
 * It runs on admin-ajax.php and admin-post.php as well.
 *
 * This is roughly analogous to the more general {@see 'init'} hook, which fires earlier.
 *
 * @since 2.5.0
 */
do_action( 'admin_init' );

$plugin_page = $app->get( 'plugin_page' );
if ( $plugin_page ) {

	if ( ! empty( $typenow ) ) {
		$the_parent = $pagenow . '?post_type=' . $typenow;
	} else {
		$the_parent = $pagenow;
	}

	$page_hook = get_plugin_page_hook( $plugin_page, $the_parent );
	if ( ! $page_hook ) {
		$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );

		// Back-compat for plugins using add_management_page().
		if ( empty( $page_hook ) && 'edit.php' == $pagenow && '' != get_plugin_page_hook( $plugin_page, 'tools.php' ) ) {
			// There could be plugin specific params on the URL, so we need the whole query string
			if ( $app['request']->getQueryString() ) {
				$query_string = $app['request']->getQueryString() ;
			} else {
				$query_string = 'page=' . $plugin_page;
			}
			wp_redirect( admin_url( 'tools.php?' . $query_string) );
			exit;
		}
	}
	unset( $the_parent);
}

$app->set( 'hook_suffix',  '' );
if ( isset( $page_hook ) ) {
	$app->set( 'hook_suffix', $page_hook );
} elseif ( $plugin_page ) {
	$app->set( 'hook_suffix', $plugin_page );
} elseif ( isset( $pagenow ) ) {
	$app->set( 'hook_suffix', $pagenow );
}

set_current_screen();

$typenow = $app['typenow'];
$taxnow = $app['taxnow'];

// Handle plugin admin pages.
if ( $plugin_page ) {
	if ( $page_hook ) {
		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where a callback is provided when the screen is registered.
		 *
		 * The dynamic portion of the hook name, `$page_hook`, refers to a mixture of plugin
		 * page information including:
		 * 1. The page type. If the plugin page is registered as a submenu page, such as for
		 *    Settings, the page type would be 'settings'. Otherwise the type is 'toplevel'.
		 * 2. A separator of '_page_'.
		 * 3. The plugin basename minus the file extension.
		 *
		 * Together, the three parts form the `$page_hook`. Citing the example above,
		 * the hook name used would be 'load-settings_page_pluginbasename'.
		 *
		 * @see get_plugin_page_hook()
		 *
		 * @since 2.1.0
		 */
		do_action( "load-{$page_hook}" );
		if ( ! $_get->get( 'noheader' ) ) {
			require_once( ABSPATH . 'wp-admin/admin-header.php' );
		}
		/**
		 * Used to call the registered callback for a plugin screen.
		 *
		 * @ignore
		 * @since 1.5.0
		 */
		do_action( $page_hook );

		include( ABSPATH . 'wp-admin/admin-footer.php' );

	} else {
		if ( validate_file( $plugin_page ) ) {
			wp_die( __( 'Invalid plugin page' ) );
		}

		if ( ! (
			file_exists( WP_PLUGIN_DIR . "/{$plugin_page}" ) &&
			is_file( WP_PLUGIN_DIR . "/{$plugin_page}" ) ) &&
			! (
				file_exists( WPMU_PLUGIN_DIR . "/{$plugin_page}" ) &&
				is_file( WPMU_PLUGIN_DIR . "/{$plugin_page}" )
			)
		) {
			wp_die( sprintf( __( 'Cannot load %s.' ), htmlentities( $plugin_page ) ) );
		}
		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where the file to load is directly included, rather than the use of a function.
		 *
		 * The dynamic portion of the hook name, `$plugin_page`, refers to the plugin basename.
		 *
		 * @see plugin_basename()
		 *
		 * @since 1.5.0
		 */
		do_action( "load-{$plugin_page}" );

		if ( ! $_get->get( 'noheader' ) ) {
			require_once( ABSPATH . 'wp-admin/admin-header.php' );
		}

		if ( file_exists( WPMU_PLUGIN_DIR . "/{$plugin_page}" ) ) {
			include( WPMU_PLUGIN_DIR . "/{$plugin_page}" );
		} else {
			include( WP_PLUGIN_DIR . "/{$plugin_page}" );
		}

		include( ABSPATH . 'wp-admin/admin-footer.php' );
	}

	exit();
} elseif ( $_get->get( 'import' ) ) {

	$importer = $_get->get( 'import' );

	if ( ! current_user_can( 'import' ) ) {
		wp_die( __( 'Sorry, you are not allowed to import content.' ) );
	}

	if ( validate_file( $importer) ) {
		wp_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	if ( ! isset( $app->importers[ $importer ] ) || ! is_callable( $app->importers[ $importer ][2] ) ) {
		wp_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	/**
	 * Fires before an importer screen is loaded.
	 *
	 * The dynamic portion of the hook name, `$importer`, refers to the importer slug.
	 *
	 * @since 3.5.0
	 */
	do_action( "load-importer-{$importer}" );

	$app->set( 'parent_file', 'tools.php' );
	$app->current_screen->set_parentage( $app->get( 'parent_file' ) );
	$app->set( 'submenu_file', 'import.php' );
	$app->set( 'title', __( 'Import' ) );

	if ( ! $_get->get( 'noheader' ) ) {
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
	}
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	define( 'WP_IMPORTING', true );

	/**
	 * Whether to filter imported data through kses on import.
	 *
	 * Multisite uses this hook to filter all data through kses by default,
	 * as a super administrator may be assisting an untrusted user.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $force Whether to force data to be filtered through kses. Default false.
	 */
	if ( apply_filters( 'force_filtered_html_on_import', false ) ) {
		kses_init_filters();  // Always filter imported data with kses on multisite.
	}

	call_user_func( $app->importers[ $importer ][2] );

	include( ABSPATH . 'wp-admin/admin-footer.php' );

	// Make sure rules are flushed
	flush_rewrite_rules( false );

	exit();
} else {
	/**
	 * Fires before a particular screen is loaded.
	 *
	 * The load-* hook fires in a number of contexts. This hook is for core screens.
	 *
	 * The dynamic portion of the hook name, `$pagenow`, is a global variable
	 * referring to the filename of the current page, such as 'admin.php',
	 * 'post-new.php' etc. A complete hook for the latter would be
	 * 'load-post-new.php'.
	 *
	 * @since 2.1.0
	 */
	do_action( "load-{$pagenow}" );

	/*
	 * The following hooks are fired to ensure backward compatibility.
	 * In all other cases, 'load-' . $pagenow should be used instead.
	 */
	if ( $typenow == 'page' ) {
		if ( $pagenow == 'post-new.php' ) {
			do_action( 'load-page-new.php' );
		} elseif ( $pagenow == 'post.php' ) {
			do_action( 'load-page.php' );
		}
	} elseif ( $pagenow == 'edit-tags.php' ) {
		if ( $taxnow == 'category' ) {
			do_action( 'load-categories.php' );
		} elseif ( $taxnow == 'link_category' ) {
			do_action( 'load-edit-link-categories.php' );
		}
	} elseif( 'term.php' === $pagenow ) {
		do_action( 'load-edit-tags.php' );
	}
}

$action = $_request->get( 'action' );
if ( ! empty( $action ) ) {
	/**
	 * Fires when an 'action' request variable is sent.
	 *
	 * The dynamic portion of the hook name, `$_*['action']`,
	 * refers to the action derived from the `GET` or `POST` request.
	 *
	 * @since 2.6.0
	 */
	do_action( 'admin_action_' . $action );
}