<?php
/**
 * Post advanced form for inclusion in the administration panels.
 *
 * @package WordPress
 * @subpackage Administration
 */

// don't load directly
if ( !defined('ABSPATH') ) {
	die('-1');
}

$post_type = get_current_screen()->post_type;
$post_type_object = get_post_type_object( $post_type );

wp_enqueue_script('post');
$_wp_editor_expand = $_content_editor_dfw = false;

/**
 * Filters whether to enable the 'expand' functionality in the post editor.
 *
 * @since 4.0.0
 * @since 4.1.0 Added the `$post_type` parameter.
 *
 * @param bool   $expand    Whether to enable the 'expand' functionality. Default true.
 * @param string $post_type Post type.
 */
if ( post_type_supports( $post_type, 'editor' ) && ! wp_is_mobile() &&
	 ! ( $app['is_IE'] && preg_match( '/MSIE [5678]/', $app['request.useragent'] ) ) &&
	 apply_filters( 'wp_editor_expand', true, $post_type ) ) {

	wp_enqueue_script('editor-expand');
	$_content_editor_dfw = true;
	$_wp_editor_expand = ( get_user_setting( 'editor_expand', 'on' ) === 'on' );
}

if ( wp_is_mobile() ) {
	wp_enqueue_script( 'jquery-touch-punch' );
}
/**
 * Post ID global
 * @name $post_ID
 * @var int
 */
$post_ID = isset($post_ID) ? (int) $post_ID : 0;
$user_ID = isset($user_ID) ? (int) $user_ID : 0;
$action = isset($action) ? $action : '';

if ( $post_ID == get_option( 'page_for_posts' ) && empty( $post->post_content ) ) {
	add_action( 'edit_form_after_title', '_wp_posts_page_notice' );
	remove_post_type_support( $post_type, 'editor' );
}

$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' );
if ( ! $thumbnail_support && 'attachment' === $post_type && $post->post_mime_type ) {
	if ( wp_attachment_is( 'audio', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
	} elseif ( wp_attachment_is( 'video', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
	}
}

if ( $thumbnail_support ) {
	add_thickbox();
	wp_enqueue_media( array( 'post' => $post_ID ) );
}

// Add the local autosave notice HTML
add_action( 'admin_footer', '_local_storage_notice' );

/*
 * @todo Document the $messages array(s).
 */
$permalink = get_permalink( $post_ID );
if ( ! $permalink ) {
	$permalink = '';
}

$messages = [];

$preview_post_link_html = $scheduled_post_link_html = $view_post_link_html = '';
$preview_page_link_html = $scheduled_page_link_html = $view_page_link_html = '';

$preview_url = get_preview_post_link( $post );

$viewable = is_post_type_viewable( $post_type_object );

if ( $viewable ) {

	// Preview post link.
	$preview_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview post' )
	);

	// Scheduled post preview link.
	$scheduled_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview post' )
	);

	// View post link.
	$view_post_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View post' )
	);

	// Preview page link.
	$preview_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview page' )
	);

	// Scheduled page preview link.
	$scheduled_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview page' )
	);

	// View page link.
	$view_page_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View page' )
	);

}

/* translators: Publish box date format, see https://secure.php.net/date */
$scheduled_date = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );

$revision = $app['request']->query->get( 'revision' );

$messages['post'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Post updated.' ) . $view_post_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Post updated.' ),
	/* translators: %s: date and time of the revision */
	 5 => $revision ? sprintf( __( 'Post restored to revision from %s.' ), wp_post_revision_title( (int) $revision, false ) ) : false,
	 6 => __( 'Post published.' ) . $view_post_link_html,
	 7 => __( 'Post saved.' ),
	 8 => __( 'Post submitted.' ) . $preview_post_link_html,
	 9 => sprintf( __( 'Post scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
	10 => __( 'Post draft updated.' ) . $preview_post_link_html,
);
$messages['page'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Page updated.' ) . $view_page_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Page updated.' ),
	/* translators: %s: date and time of the revision */
	 5 => $revision ? sprintf( __( 'Page restored to revision from %s.' ), wp_post_revision_title( (int) $revision, false ) ) : false,
	 6 => __( 'Page published.' ) . $view_page_link_html,
	 7 => __( 'Page saved.' ),
	 8 => __( 'Page submitted.' ) . $preview_page_link_html,
	 9 => sprintf( __( 'Page scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_page_link_html,
	10 => __( 'Page draft updated.' ) . $preview_page_link_html,
);
$messages['attachment'] = array_fill( 1, 10, __( 'Media file updated.' ) ); // Hack, for now.

/**
 * Filters the post updated messages.
 *
 * @since 3.0.0
 *
 * @param array $messages Post updated messages. For defaults @see $messages declarations above.
 */
$messages = apply_filters( 'post_updated_messages', $messages );

$message = $app['request']->query->get( 'message', false );

if ( $message ) {
	$message = (int) $message;
	if ( isset( $messages[ $post_type ][ $message ] ) ) {
		$message = $messages[ $post_type ][ $message ];
	} elseif ( ! isset( $messages[ $post_type ] ) && isset( $messages['post'][ $message ] ) ) {
		$message = $messages['post'][ $message ];
	}
}

$notice = false;
$form_extra = '';
if ( 'auto-draft' == $post->post_status ) {
	if ( 'edit' == $action ) {
		$post->post_title = '';
	}
	$autosave = false;
	$form_extra .= "<input type='hidden' id='auto_draft' name='auto_draft' value='1' />";
} else {
	$autosave = wp_get_post_autosave( $post_ID );
}

$form_action = 'editpost';
$nonce_action = 'update-post_' . $post_ID;
$form_extra .= "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";

// Detect if there exists an autosave newer than the post and if that autosave is different than the post
if ( $autosave && mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
	foreach ( _wp_post_revision_fields( $post ) as $autosave_field => $_autosave_field ) {
		if ( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
			$notice = sprintf( __( 'There is an autosave of this post that is more recent than the version below. <a href="%s">View the autosave</a>' ), get_edit_post_link( $autosave->ID ) );
			break;
		}
	}
	// If this autosave isn't different from the current post, begone.
	if ( ! $notice ) {
		wp_delete_post_revision( $autosave->ID );
	}
	unset($autosave_field, $_autosave_field);
}

$post_type_object = get_post_type_object($post_type);

// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
require_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );


$publish_callback_args = null;
if ( post_type_supports($post_type, 'revisions') && 'auto-draft' != $post->post_status ) {
	$revisions = wp_get_post_revisions( $post_ID );

	// We should aim to show the revisions meta box only when there are revisions.
	if ( count( $revisions ) > 1 ) {
		reset( $revisions ); // Reset pointer for key()
		$publish_callback_args = array( 'revisions_count' => count( $revisions ), 'revision_id' => key( $revisions ) );
		add_meta_box('revisionsdiv', __('Revisions'), 'post_revisions_meta_box', null, 'normal', 'core');
	}
}

if ( 'attachment' == $post_type ) {
	wp_enqueue_script( 'image-edit' );
	wp_enqueue_style( 'imgareaselect' );
	add_meta_box( 'submitdiv', __('Save'), 'attachment_submit_meta_box', null, 'side', 'core' );
	add_action( 'edit_form_after_title', 'edit_form_image_editor' );

	if ( wp_attachment_is( 'audio', $post ) ) {
		add_meta_box( 'attachment-id3', __( 'Metadata' ), 'attachment_id3_data_meta_box', null, 'normal', 'core' );
	}
} else {
	add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', null, 'side', 'core', $publish_callback_args );
}

if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ) {
	add_meta_box( 'formatdiv', _x( 'Format', 'post format' ), 'post_format_meta_box', null, 'side', 'core' );
}

// all taxonomies
foreach ( get_object_taxonomies( $post ) as $tax_name ) {
	$taxonomy = get_taxonomy( $tax_name );
	if ( ! $taxonomy->show_ui || false === $taxonomy->meta_box_cb ) {
		continue;
	}

	$label = $taxonomy->labels->name;

	if ( ! is_taxonomy_hierarchical( $tax_name ) ) {
		$tax_meta_box_id = 'tagsdiv-' . $tax_name;
	} else {
		$tax_meta_box_id = $tax_name . 'div';
	}
	add_meta_box( $tax_meta_box_id, $label, $taxonomy->meta_box_cb, null, 'side', 'core', array( 'taxonomy' => $tax_name ) );
}

if ( post_type_supports($post_type, 'page-attributes') ) {
	add_meta_box('pageparentdiv', 'page' == $post_type ? __('Page Attributes') : __('Attributes'), 'page_attributes_meta_box', null, 'side', 'core');
}
if ( $thumbnail_support && current_user_can( 'upload_files' ) ) {
	add_meta_box('postimagediv', esc_html( $post_type_object->labels->featured_image ), 'post_thumbnail_meta_box', null, 'side', 'low');
}
if ( post_type_supports($post_type, 'excerpt') ) {
	add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', null, 'normal', 'core');
}
if ( post_type_supports($post_type, 'trackbacks') ) {
	add_meta_box('trackbacksdiv', __('Send Trackbacks'), 'post_trackback_meta_box', null, 'normal', 'core');
}
if ( post_type_supports($post_type, 'custom-fields') ) {
	add_meta_box('postcustom', __('Custom Fields'), 'post_custom_meta_box', null, 'normal', 'core');
}
/**
 * Fires in the middle of built-in meta box registration.
 *
 * @since 2.1.0
 * @deprecated 3.7.0 Use 'add_meta_boxes' instead.
 *
 * @param WP_Post $post Post object.
 */
do_action( 'dbx_post_advanced', $post );

// Allow the Discussion meta box to show up if the post type supports comments,
// or if comments or pings are open.
if ( comments_open( $post ) || pings_open( $post ) || post_type_supports( $post_type, 'comments' ) ) {
	add_meta_box( 'commentstatusdiv', __( 'Discussion' ), 'post_comment_status_meta_box', null, 'normal', 'core' );
}

$stati = get_post_stati( array( 'public' => true ) );
if ( empty( $stati ) ) {
	$stati = array( 'publish' );
}
$stati[] = 'private';

if (
	in_array( get_post_status( $post ), $stati ) &&
	// If the post type support comments, or the post has comments, allow the
	// Comments meta box.
	( comments_open( $post ) || pings_open( $post ) || $post->comment_count > 0 || post_type_supports( $post_type, 'comments' ) )
) {
	add_meta_box( 'commentsdiv', __( 'Comments' ), 'post_comment_meta_box', null, 'normal', 'core' );
}

if ( ! ( 'pending' == get_post_status( $post ) && ! current_user_can( $post_type_object->cap->publish_posts ) ) ) {
	add_meta_box('slugdiv', __('Slug'), 'post_slug_meta_box', null, 'normal', 'core');
}

if (
	post_type_supports($post_type, 'author') &&
	( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) )
) {
	add_meta_box('authordiv', __('Author'), 'post_author_meta_box', null, 'normal', 'core');
}

/**
 * Fires after all built-in meta boxes have been added.
 *
 * @since 3.0.0
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Post object.
 */
do_action( 'add_meta_boxes', $post_type, $post );

/**
 * Fires after all built-in meta boxes have been added, contextually for the given post type.
 *
 * The dynamic portion of the hook, `$post_type`, refers to the post type of the post.
 *
 * @since 3.0.0
 *
 * @param WP_Post $post Post object.
 */
do_action( "add_meta_boxes_{$post_type}", $post );

/**
 * Fires after meta boxes have been added.
 *
 * Fires once for each of the default meta box contexts: normal, advanced, and side.
 *
 * @since 3.0.0
 *
 * @param string  $post_type Post type of the post.
 * @param string  $context   string  Meta box context.
 * @param WP_Post $post      Post object.
 */
do_action( 'do_meta_boxes', $post_type, 'normal', $post );
/** This action is documented in wp-admin/edit-form-advanced.php */
do_action( 'do_meta_boxes', $post_type, 'advanced', $post );
/** This action is documented in wp-admin/edit-form-advanced.php */
do_action( 'do_meta_boxes', $post_type, 'side', $post );

add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

( new \WP\Post\Admin\Help( $app->current_screen ) )->addEditFormAdvanced( $post_type );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1><?php
echo esc_html( $app->get( 'title' ) );
if ( isset( $post_new_file ) && current_user_can( $post_type_object->cap->create_posts ) ) {
	echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="page-title-action">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
} ?></h1>
<?php if ( $notice ) { ?>
<div id="notice" class="notice notice-warning"><p id="has-newer-autosave"><?php echo $notice ?></p></div>
<?php }

if ( $message ) { ?>
<div id="message" class="updated notice notice-success is-dismissible"><p><?php echo $message; ?></p></div>
<?php } ?>
<div id="lost-connection-notice" class="error hidden">
	<p><span class="spinner"></span> <?php _e( '<strong>Connection lost.</strong> Saving has been disabled until you&#8217;re reconnected.' ); ?>
	<span class="hide-if-no-sessionstorage"><?php _e( 'We&#8217;re backing up this post in your browser, just in case.' ); ?></span>
	</p>
</div>
<form name="post" action="post.php" method="post" id="post"<?php
/**
 * Fires inside the post editor form tag.
 *
 * @since 3.0.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'post_edit_form_tag', $post );

$referer = wp_get_referer();
?>>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post->post_status) ?>" />
<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>" />
<?php if ( ! empty( $active_post_lock ) ) { ?>
<input type="hidden" id="active_post_lock" value="<?php echo esc_attr( implode( ':', $active_post_lock ) ); ?>" />
<?php
}
if ( 'draft' != get_post_status( $post ) ) {
	wp_original_referer_field(true, 'previous');
}
echo $form_extra;

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>

<?php
/**
 * Fires at the beginning of the edit form.
 *
 * At this point, the required hidden fields and nonces have already been output.
 *
 * @since 3.7.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_top', $post ); ?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
<div id="post-body-content">

<?php if ( post_type_supports($post_type, 'title') ) { ?>
<div id="titlediv">
<div id="titlewrap">
	<?php
	/**
	 * Filters the title field placeholder text.
	 *
	 * @since 3.1.0
	 *
	 * @param string  $text Placeholder text. Default 'Enter title here'.
	 * @param WP_Post $post Post object.
	 */
	$title_placeholder = apply_filters( 'enter_title_here', __( 'Enter title here' ), $post );
	?>
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo $title_placeholder; ?></label>
	<input type="text" name="post_title" size="30" value="<?php echo esc_attr( $post->post_title ); ?>" id="title" spellcheck="true" autocomplete="off" />
</div>
<?php
/**
 * Fires before the permalink field in the edit form.
 *
 * @since 4.1.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_before_permalink', $post );
?>
<div class="inside">
<?php
if ( $viewable ) :
$sample_permalink_html = $post_type_object->public ? get_sample_permalink_html($post->ID) : '';

// As of 4.4, the Get Shortlink button is hidden by default.
if ( has_filter( 'pre_get_shortlink' ) || has_filter( 'get_shortlink' ) ) {
	$shortlink = wp_get_shortlink($post->ID, 'post');

	if ( !empty( $shortlink ) && $shortlink !== $permalink && $permalink !== home_url('?page_id=' . $post->ID) ) {
    	$sample_permalink_html .= '<input id="shortlink" type="hidden" value="' . esc_attr($shortlink) . '" /><a href="#" class="button button-small" onclick="prompt(&#39;URL:&#39;, jQuery(\'#shortlink\').val()); return false;">' . __('Get Shortlink') . '</a>';
	}
}

if ( $post_type_object->public && ! ( 'pending' == get_post_status( $post ) && !current_user_can( $post_type_object->cap->publish_posts ) ) ) {
	$has_sample_permalink = $sample_permalink_html && 'auto-draft' != $post->post_status;
?>
	<div id="edit-slug-box" class="hide-if-no-js">
	<?php
		if ( $has_sample_permalink ) {
			echo $sample_permalink_html;
		}
	?>
	</div>
<?php
}
endif;
?>
</div>
<?php
wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false );
?>
</div><!-- /titlediv -->
<?php
}
/**
 * Fires after the title field.
 *
 * @since 3.5.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_after_title', $post );

if ( post_type_supports($post_type, 'editor') ) {
?>
<div id="postdivrich" class="postarea<?php if ( $_wp_editor_expand ) { echo ' wp-editor-expand'; } ?>">

<?php wp_editor( $post->post_content, 'content', array(
	'_content_editor_dfw' => $_content_editor_dfw,
	'drag_drop_upload' => true,
	'tabfocus_elements' => 'content-html,save-post',
	'editor_height' => 300,
	'tinymce' => array(
		'resize' => false,
		'wp_autoresize_on' => $_wp_editor_expand,
		'add_unload_trigger' => false,
	),
) ); ?>
<table id="post-status-info"><tbody><tr>
	<td id="wp-word-count" class="hide-if-no-js"><?php printf( __( 'Word count: %s' ), '<span class="word-count">0</span>' ); ?></td>
	<td class="autosave-info">
	<span class="autosave-message">&nbsp;</span>
<?php
	if ( 'auto-draft' != $post->post_status ) {
		echo '<span id="last-edit">';
		if ( $last_user = get_userdata( get_post_meta( $post_ID, '_edit_last', true ) ) ) {
			printf( __( 'Last edited by %1$s on %2$s at %3$s' ), esc_html( $last_user->display_name ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
		} else {
			printf( __( 'Last edited on %1$s at %2$s' ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
		}
		echo '</span>';
	} ?>
	</td>
	<td id="content-resize-handle" class="hide-if-no-js"><br /></td>
</tr></tbody></table>

</div>
<?php }
/**
 * Fires after the content editor.
 *
 * @since 3.5.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_after_editor', $post );
?>
</div><!-- /post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php

if ( 'page' == $post_type ) {
	/**
	 * Fires before meta boxes with 'side' context are output for the 'page' post type.
	 *
	 * The submitpage box is a meta box with 'side' context, so this hook fires just before it is output.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'submitpage_box', $post );
} else {
	/**
	 * Fires before meta boxes with 'side' context are output for all post types other than 'page'.
	 *
	 * The submitpost box is a meta box with 'side' context, so this hook fires just before it is output.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'submitpost_box', $post );
}


do_meta_boxes($post_type, 'side', $post);

?>
</div>
<div id="postbox-container-2" class="postbox-container">
<?php

do_meta_boxes(null, 'normal', $post);

if ( 'page' == $post_type ) {
	/**
	 * Fires after 'normal' context meta boxes have been output for the 'page' post type.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'edit_page_form', $post );
} else {
	/**
	 * Fires after 'normal' context meta boxes have been output for all post types other than 'page'.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'edit_form_advanced', $post );
}


do_meta_boxes(null, 'advanced', $post);

?>
</div>
<?php
/**
 * Fires after all meta box sections have been output, before the closing #post-body div.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'dbx_post_sidebar', $post );

?>
</div><!-- /post-body -->
<br class="clear" />
</div><!-- /poststuff -->
</form>
</div>

<?php
if ( post_type_supports( $post_type, 'comments' ) ) {
	wp_comment_reply();
}
?>

<?php if ( ! wp_is_mobile() && post_type_supports( $post_type, 'title' ) && '' === $post->post_title ) : ?>
<script type="text/javascript">
try{document.post.title.focus();}catch(e){}
</script>
<?php endif;