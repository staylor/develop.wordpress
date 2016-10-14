<?php
/**
 * Manage media uploaded file.
 *
 * There are many filters in here for media. Plugins can extend functionality
 * by hooking into the filters.
 *
 * @package WordPress
 * @subpackage Administration
 */
use WP\Media\Admin\Help as MediaHelp;

/** Load WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if (!current_user_can('upload_files'))
	wp_die(__('Sorry, you are not allowed to upload files.'));

wp_enqueue_script('plupload-handlers');

$post_id = $_request->getInt( 'post_id', 0 );
if ( $post_id ) {
	if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		$post_id = 0;
	}
}

if ( $_post->all() ) {
	if ( $_post->has( 'html-upload' ) && !empty( $_files->all() ) ) {
		check_admin_referer('media-form');
		// Upload File button was clicked
		$upload_id = media_handle_upload( 'async-upload', $post_id );
		if ( is_wp_error( $upload_id ) ) {
			wp_die( $upload_id );
		}
	}
	wp_redirect( admin_url( 'upload.php' ) );
	exit;
}

$app->set( 'title', __( 'Upload New Media' ) );
$app->parent_file = 'upload.php';
$app->current_screen->set_parentage( $app->parent_file );

( new MediaHelp( get_current_screen() ) )->addNew();

require_once( ABSPATH . 'wp-admin/admin-header.php' );

$form_class = 'media-upload-form type-form validate';

if ( get_user_setting('uploader') || $_get->get( 'browser-uploader' ) )
	$form_class .= ' html-uploader';
?>
<div class="wrap">
	<h1><?php echo esc_html( $app->get( 'title' ) ); ?></h1>

	<form enctype="multipart/form-data" method="post" action="<?php echo admin_url('media-new.php'); ?>" class="<?php echo esc_attr( $form_class ); ?>" id="file-form">

	<?php media_upload_form(); ?>

	<script type="text/javascript">
	var post_id = <?php echo $post_id; ?>, shortform = 3;
	</script>
	<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>" />
	<?php wp_nonce_field('media-form'); ?>
	<div id="media-items" class="hide-if-no-js"></div>
	</form>
</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
