<?php
/**
 * Media management action handler.
 *
 * @package WordPress
 * @subpackage Administration
 */
use WP\Media\Admin\Help as MediaHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

$app->parent_file = 'upload.php';
$app->current_screen->set_parentage( $app->parent_file );
$app->submenu_file = 'upload.php';

wp_reset_vars(array('action'));

switch ( $action ) {
case 'editattachment' :
	$attachment_id = $_post->getInt( 'attachment_id' );
	check_admin_referer('media-form');

	if ( !current_user_can('edit_post', $attachment_id) )
		wp_die ( __('Sorry, you are not allowed to edit this attachment.') );

	$errors = media_upload_form_handler();

	if ( empty($errors) ) {
		$location = 'media.php';
		if ( $referer = wp_get_original_referer() ) {
			if ( false !== strpos($referer, 'upload.php') || ( url_to_postid($referer) == $attachment_id )  )
				$location = $referer;
		}
		if ( false !== strpos($location, 'upload.php') ) {
			$location = remove_query_arg('message', $location);
			$location = add_query_arg('posted',	$attachment_id, $location);
		} elseif ( false !== strpos($location, 'media.php') ) {
			$location = add_query_arg('message', 'updated', $location);
		}
		wp_redirect($location);
		exit;
	}

	// No break.
case 'edit' :
	$app->set( 'title', __( 'Edit Media' ) );

	if ( empty($errors) )
		$errors = null;

	if ( empty( $_get->get( 'attachment_id' ) ) ) {
		wp_redirect( admin_url('upload.php') );
		exit();
	}
	$att_id = $_get->getInt( 'attachment_id' );

	if ( !current_user_can('edit_post', $att_id) )
		wp_die ( __('Sorry, you are not allowed to edit this attachment.') );

	$att = get_post($att_id);

	if ( empty($att->ID) ) wp_die( __('You attempted to edit an attachment that doesn&#8217;t exist. Perhaps it was deleted?') );
	if ( 'attachment' !== $att->post_type ) wp_die( __('You attempted to edit an item that isn&#8217;t an attachment. Please go back and try again.') );
	if ( $att->post_status == 'trash' ) wp_die( __('You can&#8217;t edit this attachment because it is in the Trash. Please move it out of the Trash and try again.') );

	add_filter('attachment_fields_to_edit', 'media_single_attachment_fields_to_edit', 10, 2);

	wp_enqueue_script( 'wp-ajax-response' );
	wp_enqueue_script('image-edit');
	wp_enqueue_style('imgareaselect');

	( new MediaHelp( get_current_screen() ) )->addEdit();

	require( ABSPATH . 'wp-admin/admin-header.php' );

	$app->parent_file = 'upload.php';
	$app->current_screen->set_parentage( $app->parent_file );
	$message = '';
	$class = '';
	if ( $_get->get( 'message' ) ) {
		switch ( $_get->get( 'message' ) ) {
		case 'updated' :
			$message = __('Media file updated.');
			$class = 'updated';
			break;
		}
	}
	if ( $message )
		echo "<div id='message' class='$class'><p>$message</p></div>\n";

?>

<div class="wrap">
<h1>
<?php
echo esc_html( $app->get( 'title' ) );
if ( current_user_can( 'upload_files' ) ) { ?>
	<a href="media-new.php" class="page-title-action"><?php echo esc_html_x('Add New', 'file'); ?></a>
<?php } ?>
</h1>

<form method="post" class="media-upload-form" id="media-single-form">
<p class="submit" style="padding-bottom: 0;">
<?php submit_button( __( 'Update Media' ), 'primary', 'save', false ); ?>
</p>

<div class="media-single">
<div id="media-item-<?php echo $att_id; ?>" class="media-item">
<?php echo get_media_item( $att_id, array( 'toggle' => false, 'send' => false, 'delete' => false, 'show_title' => false, 'errors' => !empty($errors[$att_id]) ? $errors[$att_id] : null ) ); ?>
</div>
</div>

<?php submit_button( __( 'Update Media' ), 'primary', 'save' ); ?>
<input type="hidden" name="post_id" id="post_id" value="<?php echo isset($post_id) ? esc_attr($post_id) : ''; ?>" />
<input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo esc_attr($att_id); ?>" />
<input type="hidden" name="action" value="editattachment" />
<?php wp_original_referer_field(true, 'previous'); ?>
<?php wp_nonce_field('media-form'); ?>

</form>

</div>

<?php

	require( ABSPATH . 'wp-admin/admin-footer.php' );

	exit;

default:
	wp_redirect( admin_url('upload.php') );
	exit;

}
