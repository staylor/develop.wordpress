<?php
/**
 * New Post Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

/**
 * @global WP_Post $post
 */
global $post;

$post_type = get_current_screen()->post_type;
if ( ! $post_type ) {
	wp_die( __( 'Invalid post type.' ) );
}
$post_type_object = get_post_type_object( $post_type );

if ( 'post' == $post_type ) {
	$app->set( 'parent_file', 'edit.php' );
	$app->set( 'submenu_file', 'post-new.php' );
} elseif ( 'attachment' == $post_type ) {
	if ( wp_redirect( admin_url( 'media-new.php' ) ) )
		exit;
} else {
	$app->set( 'submenu_file', "post-new.php?post_type=$post_type" );
	if ( isset( $post_type_object ) && $post_type_object->show_in_menu && $post_type_object->show_in_menu !== true ) {
		$app->set( 'parent_file', $post_type_object->show_in_menu );
		// What if there isn't a post-new.php item for this post type?
		if ( ! isset( $_registered_pages[ get_plugin_page_hookname( "post-new.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
			if (	isset( $_registered_pages[ get_plugin_page_hookname( "edit.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
				// Fall back to edit.php for that post type, if it exists
				$app->set( 'submenu_file', "edit.php?post_type=$post_type" );
			} else {
				// Otherwise, give up and highlight the parent
				$app->set( 'submenu_file', $app->get( 'parent_file' ) );
			}
		}
	} else {
		$app->set( 'parent_file', "edit.php?post_type=$post_type" );
	}
}
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

$app->set( 'title', $post_type_object->labels->add_new_item );

$editing = true;

if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to create posts as this user.' ) . '</p>',
		403
	);
}

// Schedule auto-draft cleanup
if ( ! wp_next_scheduled( 'wp_scheduled_auto_draft_delete' ) )
	wp_schedule_event( time(), 'daily', 'wp_scheduled_auto_draft_delete' );

wp_enqueue_script( 'autosave' );

if ( is_multisite() ) {
	add_action( 'admin_footer', '_admin_notice_post_locked' );
} else {
	$check_users = get_users( [ 'fields' => 'ID', 'number' => 2 ] );

	if ( count( $check_users ) > 1 )
		add_action( 'admin_footer', '_admin_notice_post_locked' );

	unset( $check_users );
}

// Show post form.
$post = get_default_post_to_edit( $post_type, true );
$post_ID = $post->ID;
include( ABSPATH . 'wp-admin/edit-form-advanced.php' );
include( ABSPATH . 'wp-admin/admin-footer.php' );
