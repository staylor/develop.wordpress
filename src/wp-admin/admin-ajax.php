<?php
/**
 * WordPress Ajax Process Execution
 *
 * @package WordPress
 * @subpackage Administration
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 */

/**
 * Executing Ajax process.
 *
 * @since 2.1.0
 */
const DOING_AJAX = true;
const WP_ADMIN = true;

/** Load WordPress Bootstrap */
require_once( dirname( __DIR__ ) . '/wp-load.php' );

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

$action_get = $app['request']->query->get( 'action' );
$action_post = $app['request']->request->get( 'action' );

$action = $action_get ?? $action_post;

// Require an action parameter
if ( empty( $action ) ) {
	die( '0' );
}

/** Load WordPress Administration APIs */
require_once( ABSPATH . 'wp-admin/includes/admin.php' );

/** Load Ajax Handlers for WordPress Core */
require_once( ABSPATH . 'wp-admin/includes/ajax-actions.php' );

header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

/** This action is documented in wp-admin/admin.php */
do_action( 'admin_init' );

$core_actions_get = [
	'fetch-list', 'ajax-tag-search', 'wp-compression-test', 'imgedit-preview', 'oembed-cache',
	'autocomplete-user', 'dashboard-widgets', 'logged-in',
];

$core_actions_post = [
	'oembed-cache', 'image-editor', 'delete-comment', 'delete-tag', 'delete-link',
	'delete-meta', 'delete-post', 'trash-post', 'untrash-post', 'delete-page', 'dim-comment',
	'add-link-category', 'add-tag', 'get-tagcloud', 'get-comments', 'replyto-comment',
	'edit-comment', 'add-menu-item', 'add-meta', 'add-user', 'closed-postboxes',
	'hidden-columns', 'update-welcome-panel', 'menu-get-metabox', 'wp-link-ajax',
	'menu-locations-save', 'menu-quick-search', 'meta-box-order', 'get-permalink',
	'sample-permalink', 'inline-save', 'inline-save-tax', 'find_posts', 'widgets-order',
	'save-widget', 'delete-inactive-widgets', 'set-post-thumbnail', 'date_format', 'time_format',
	'wp-remove-post-lock', 'dismiss-wp-pointer', 'upload-attachment', 'get-attachment',
	'query-attachments', 'save-attachment', 'save-attachment-compat', 'send-link-to-editor',
	'send-attachment-to-editor', 'save-attachment-order', 'heartbeat', 'get-revision-diffs',
	'save-user-color-scheme', 'update-widget', 'query-themes', 'parse-embed', 'set-attachment-thumbnail',
	'parse-media-shortcode', 'destroy-sessions', 'install-plugin', 'update-plugin', 'press-this-save-post',
	'press-this-add-category', 'crop-image', 'generate-password', 'save-wporg-username', 'delete-plugin',
	'search-plugins', 'search-install-plugins', 'activate-plugin', 'update-theme', 'delete-theme',
	'install-theme', 'get-post-thumbnail-html',
];

// Deprecated
$core_actions_post[] = 'wp-fullscreen-save-post';

// Register core Ajax calls.
if ( ! empty( $action_get ) && in_array( $action_get, $core_actions_get ) ) {
	add_action( 'wp_ajax_' . $action_get, 'wp_ajax_' . str_replace( '-', '_', $action_get ), 1 );
}

if ( ! empty( $action_post ) && in_array( $action_post, $core_actions_post ) ) {
	add_action( 'wp_ajax_' . $action_post, 'wp_ajax_' . str_replace( '-', '_', $action_post ), 1 );
}

add_action( 'wp_ajax_nopriv_heartbeat', 'wp_ajax_nopriv_heartbeat', 1 );

if ( is_user_logged_in() ) {
	/**
	 * Fires authenticated Ajax actions for logged-in users.
	 *
	 * The dynamic portion of the hook name, `$_REQUEST['action']`,
	 * refers to the name of the Ajax action callback being fired.
	 *
	 * @since 2.1.0
	 */
	do_action( 'wp_ajax_' . $action );
} else {
	/**
	 * Fires non-authenticated Ajax actions for logged-out users.
	 *
	 * The dynamic portion of the hook name, `$_REQUEST['action']`,
	 * refers to the name of the Ajax action callback being fired.
	 *
	 * @since 2.8.0
	 */
	do_action( 'wp_ajax_nopriv_' . $action );
}
// Default status
die( '0' );
