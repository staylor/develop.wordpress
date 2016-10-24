<?php
/**
 * Edit Tags Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Admin\View\Term as TermView;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! $taxnow ) {
	wp_die( __( 'Invalid taxonomy.' ) );
}

$tax = get_taxonomy( $taxnow );

if ( ! $tax ) {
	wp_die( __( 'Invalid taxonomy.' ) );
}

if ( ! in_array( $tax->name, get_taxonomies( array( 'show_ui' => true ) ) ) ) {
   wp_die( __( 'Sorry, you are not allowed to manage these items.' ) );
}

if ( ! current_user_can( $tax->cap->manage_terms ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to manage these items.' ) . '</p>',
		403
	);
}

$view = new TermView( $app );

$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );
$pagenum = $wp_list_table->get_pagenum();

$app->set( 'title', $tax->labels->name );

if ( 'post' !== $typenow ) {
	$app->set( 'parent_file', ( 'attachment' == $typenow ) ? 'upload.php' : "edit.php?post_type={$typenow}" );
	$app->set( 'submenu_file', "edit-tags.php?taxonomy={$taxnow}&amp;post_type={$typenow}" );
} elseif ( 'link_category' == $tax->name ) {
	$app->set( 'parent_file', 'link-manager.php' );
	$app->set( 'submenu_file', 'edit-tags.php?taxonomy=link_category' );
} else {
	$app->set( 'parent_file', 'edit.php' );
	$app->set( 'submenu_file', "edit-tags.php?taxonomy={$taxnow}" );
}

$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

add_screen_option( 'per_page', array( 'default' => 20, 'option' => 'edit_' . $tax->name . '_per_page' ) );

get_current_screen()->set_screen_reader_content( array(
	'heading_pagination' => $tax->labels->items_list_navigation,
	'heading_list'       => $tax->labels->items_list,
) );

$location = false;
$referer = wp_get_referer();
// For POST requests.
if ( ! $referer ) {
	$referer = wp_unslash( $app['request.uri'] );
}
$referer = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'error', 'message', 'paged' ), $referer );

switch ( $wp_list_table->current_action() ) {

case 'add-tag':
	check_admin_referer( 'add-tag', '_wpnonce_add-tag' );

	if ( ! current_user_can( $tax->cap->edit_terms ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to add this item.' ) . '</p>',
			403
		);
	}

	$ret = wp_insert_term( $view->_post->get( 'tag-name' ), $taxonomy, $view->_post->all() );
	if ( $ret && ! is_wp_error( $ret ) ) {
		$location = add_query_arg( 'message', 1, $location );
	} else {
		$location = add_query_arg( [ 'error' => true, 'message' => 4 ], $referer );
	}
	break;

case 'delete':
	$tag_ID = $view->_request->getInt( 'tag_ID' );
	if ( ! $tag_ID ) {
		break;
	}

	check_admin_referer( 'delete-tag_' . $tag_ID );

	if ( ! current_user_can( 'delete_term', $tag_ID ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to delete this item.' ) . '</p>',
			403
		);
	}

	wp_delete_term( $tag_ID, $taxnow );

	$location = add_query_arg( 'message', 2, $referer );

	break;

case 'bulk-delete':
	check_admin_referer( 'bulk-tags' );

	if ( ! current_user_can( $tax->cap->delete_terms ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to delete these items.' ) . '</p>',
			403
		);
	}

	$tags = (array) $view->_request->get( 'delete_tags' );
	foreach ( $tags as $tag_ID ) {
		wp_delete_term( $tag_ID, $taxnow );
	}

	$location = add_query_arg( 'message', 6, $referer );

	break;

case 'edit':
	$term_id = $view->_request->getInt( 'tag_ID' );
	if ( ! $term_id ) {
		break;
	}

	$term = get_term( $term_id );

	if ( ! $term instanceof WP_Term ) {
		wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );
	}

	wp_redirect( esc_url_raw( get_edit_term_link( $term_id, $taxnow, $typenow ) ) );
	exit;

case 'editedtag':
	$tag_ID = $view->_post->getInt( 'tag_ID' );
	check_admin_referer( 'update-tag_' . $tag_ID );

	if ( ! current_user_can( 'edit_term', $tag_ID ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to edit this item.' ) . '</p>',
			403
		);
	}

	$tag = get_term( $tag_ID, $taxnow );
	if ( ! $tag ) {
		wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );
	}
	$ret = wp_update_term( $tag_ID, $taxnow, $view->_post->all() );

	if ( $ret && ! is_wp_error( $ret ) ) {
		$location = add_query_arg( 'message', 3, $referer );
	} else {
		$location = add_query_arg( [ 'error' => true, 'message' => 5 ], $referer );
	}
	break;
default:
	if ( ! $wp_list_table->current_action() || ! $view->_request->get( 'delete_tags' ) ) {
		break;
	}
	check_admin_referer( 'bulk-tags' );
	$tags = (array) $view->_request->get( 'delete_tags' );
	/**
	 * Fires when a custom bulk action should be handled.
	 *
	 * The sendback link should be modified with success or failure feedback
	 * from the action to be used to display feedback to the user.
	 *
	 * @since 4.7.0
	 *
	 * @param string $location The redirect URL.
	 * @param string $action   The action being taken.
	 * @param array  $tags     The tag IDs to take the action on.
	 */
	$location = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $location, $wp_list_table->current_action(), $tags );
	break;
}

if ( ! $location && $view->_request->get( '_wp_http_referer' ) ) {
	$location = remove_query_arg(
		[ '_wp_http_referer', '_wpnonce' ],
		wp_unslash( $app['request.uri'] )
	);
}

if ( $location ) {
	if ( $pagenum > 1 ) {
		$location = add_query_arg( 'paged', $pagenum, $location ); // $pagenum takes care of $total_pages.
	}

	/**
	 * Filters the taxonomy redirect destination URL.
	 *
	 * @since 4.6.0
	 *
	 * @param string $location The destination URL.
	 * @param object $tax      The taxonomy object.
	 */
	wp_redirect( apply_filters( 'redirect_term_location', $location, $tax ) );
	exit;
}

$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

wp_enqueue_script( 'admin-tags' );
if ( current_user_can( $tax->cap->edit_terms) ) {
	wp_enqueue_script( 'inline-edit-tax' );
}

if ( 'category' == $taxnow || 'link_category' == $taxnow || 'post_tag' == $taxnow  ) {
	$view->help->addEditTags( $taxnow );
}

require_once( ABSPATH . 'wp-admin/admin-header.php' );

/** Also used by the Edit Tag  form */
require_once( ABSPATH . 'wp-admin/includes/edit-tag-messages.php' );

$class = $view->_request->get( 'error' ) ? 'error' : 'updated';

if ( is_plugin_active( 'wpcat2tag-importer/wpcat2tag-importer.php' ) ) {
	$import_link = admin_url( 'admin.php?import=wpcat2tag' );
} else {
	$import_link = admin_url( 'import.php' );
}

?>

<div class="wrap nosubsub">
<h1><?php echo esc_html( $app->get( 'title' ) );
if ( strlen( $view->_request->get( 's' ) ) ) {
	/* translators: %s: search keywords */
	printf(
		'<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>',
		esc_html( wp_unslash( $view->_request->get( 's' ) ) )
	);
}
?>
</h1>

<?php if ( $message ) : ?>
<div id="message" class="<?php echo $class; ?> notice is-dismissible"><p><?php echo $message; ?></p></div>
<?php
$_server->set( 'REQUEST_URI', remove_query_arg(
	[ 'message', 'error' ],
	$app['request.uri']
) );
endif; ?>
<div id="ajax-response"></div>

<form class="search-form wp-clearfix" method="get">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxnow ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $typenow ); ?>" />

<?php $wp_list_table->search_box( $tax->labels->search_items, 'tag' ); ?>

</form>

<div id="col-container" class="wp-clearfix">

<div id="col-left">
<div class="col-wrap">

<?php

if ( current_user_can( $tax->cap->edit_terms) ) {
	if ( 'category' == $taxnow ) {
		/**
 		 * Fires before the Add Category form.
		 *
		 * @since 2.1.0
		 * @deprecated 3.0.0 Use {$taxnow}_pre_add_form instead.
		 *
		 * @param object $arg Optional arguments cast to an object.
		 */
		do_action( 'add_category_form_pre', (object) array( 'parent' => 0 ) );
	} elseif ( 'link_category' == $taxnow ) {
		/**
		 * Fires before the link category form.
		 *
		 * @since 2.3.0
		 * @deprecated 3.0.0 Use {$taxnow}_pre_add_form instead.
		 *
		 * @param object $arg Optional arguments cast to an object.
		 */
		do_action( 'add_link_category_form_pre', (object) array( 'parent' => 0 ) );
	} else {
		/**
		 * Fires before the Add Tag form.
		 *
		 * @since 2.5.0
		 * @deprecated 3.0.0 Use {$taxnow}_pre_add_form instead.
		 *
		 * @param string $taxnow The taxonomy slug.
		 */
		do_action( 'add_tag_form_pre', $taxnow );
	}

	/**
	 * Fires before the Add Term form for all taxonomies.
	 *
	 * The dynamic portion of the hook name, `$taxnow`, refers to the taxonomy slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $taxnow The taxonomy slug.
	 */
	do_action( "{$taxnow}_pre_add_form", $taxnow );
?>

<div class="form-wrap">
<h2><?php echo $tax->labels->add_new_item; ?></h2>
<form id="addtag" method="post" action="edit-tags.php" class="validate"<?php
/**
 * Fires inside the Add Tag form tag.
 *
 * The dynamic portion of the hook name, `$taxnow`, refers to the taxonomy slug.
 *
 * @since 3.7.0
 */
do_action( "{$taxnow}_term_new_form_tag" );
?>>
<input type="hidden" name="action" value="add-tag" />
<input type="hidden" name="screen" value="<?php echo esc_attr( $current_screen->id ); ?>" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxnow ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $typenow ); ?>" />
<?php wp_nonce_field( 'add-tag', '_wpnonce_add-tag' ); ?>

<div class="form-field form-required term-name-wrap">
	<label for="tag-name"><?php _ex( 'Name', 'term name' ); ?></label>
	<input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true" />
	<p><?php _e( 'The name is how it appears on your site.' ); ?></p>
</div>
<?php if ( ! global_terms_enabled() ) : ?>
<div class="form-field term-slug-wrap">
	<label for="tag-slug"><?php _e( 'Slug' ); ?></label>
	<input name="slug" id="tag-slug" type="text" value="" size="40" />
	<p><?php _e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ); ?></p>
</div>
<?php endif; // global_terms_enabled() ?>
<?php if ( is_taxonomy_hierarchical( $taxnow ) ) : ?>
<div class="form-field term-parent-wrap">
	<label for="parent"><?php _ex( 'Parent', 'term parent' ); ?></label>
	<?php
	$dropdown_args = array(
		'hide_empty'       => 0,
		'hide_if_empty'    => false,
		'taxonomy'         => $taxnow,
		'name'             => 'parent',
		'orderby'          => 'name',
		'hierarchical'     => true,
		'show_option_none' => __( 'None' ),
	);

	/**
	 * Filters the taxonomy parent drop-down on the Edit Term page.
	 *
	 * @since 3.7.0
	 * @since 4.2.0 Added `$context` parameter.
	 *
	 * @param array  $dropdown_args {
	 *     An array of taxonomy parent drop-down arguments.
	 *
	 *     @type int|bool $hide_empty       Whether to hide terms not attached to any posts. Default 0|false.
	 *     @type bool     $hide_if_empty    Whether to hide the drop-down if no terms exist. Default false.
	 *     @type string   $taxnow           The taxonomy slug.
	 *     @type string   $name             Value of the name attribute to use for the drop-down select element.
	 *                                      Default 'parent'.
	 *     @type string   $orderby          The field to order by. Default 'name'.
	 *     @type bool     $hierarchical     Whether the taxonomy is hierarchical. Default true.
	 *     @type string   $show_option_none Label to display if there are no terms. Default 'None'.
	 * }
	 * @param string $taxnow   The taxonomy slug.
	 * @param string $context  Filter context. Accepts 'new' or 'edit'.
	 */
	$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxnow, 'new' );

	wp_dropdown_categories( $dropdown_args );
	?>
	<?php if ( 'category' == $taxnow ) : // @todo: Generic text for hierarchical taxonomies ?>
		<p><?php _e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.' ); ?></p>
	<?php endif; ?>
</div>
<?php endif; // is_taxonomy_hierarchical() ?>
<div class="form-field term-description-wrap">
	<label for="tag-description"><?php _e( 'Description' ); ?></label>
	<textarea name="description" id="tag-description" rows="5" cols="40"></textarea>
	<p><?php _e( 'The description is not prominent by default; however, some themes may show it.' ); ?></p>
</div>

<?php
if ( ! is_taxonomy_hierarchical( $taxnow ) ) {
	/**
	 * Fires after the Add Tag form fields for non-hierarchical taxonomies.
	 *
	 * @since 3.0.0
	 *
	 * @param string $taxnow The taxonomy slug.
	 */
	do_action( 'add_tag_form_fields', $taxnow );
}

/**
 * Fires after the Add Term form fields.
 *
 * The dynamic portion of the hook name, `$taxnow`, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxnow The taxonomy slug.
 */
do_action( "{$taxnow}_add_form_fields", $taxnow );

submit_button( $tax->labels->add_new_item );

if ( 'category' == $taxnow ) {
	/**
	 * Fires at the end of the Edit Category form.
	 *
	 * @since 2.1.0
	 * @deprecated 3.0.0 Use {$taxnowy}_add_form instead.
	 *
	 * @param object $arg Optional arguments cast to an object.
	 */
	do_action( 'edit_category_form', (object) array( 'parent' => 0 ) );
} elseif ( 'link_category' == $taxnow ) {
	/**
	 * Fires at the end of the Edit Link form.
	 *
	 * @since 2.3.0
	 * @deprecated 3.0.0 Use {$taxnow}_add_form instead.
	 *
	 * @param object $arg Optional arguments cast to an object.
	 */
	do_action( 'edit_link_category_form', (object) array( 'parent' => 0 ) );
} else {
	/**
	 * Fires at the end of the Add Tag form.
	 *
	 * @since 2.7.0
	 * @deprecated 3.0.0 Use {$taxnow}_add_form instead.
	 *
	 * @param string $taxnow The taxonomy slug.
	 */
	do_action( 'add_tag_form', $taxnow );
}

/**
 * Fires at the end of the Add Term form for all taxonomies.
 *
 * The dynamic portion of the hook name, `$taxnow`, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxnow The taxonomy slug.
 */
do_action( "{$taxnow}_add_form", $taxnow );
?>
</form></div>
<?php } ?>

</div>
</div><!-- /col-left -->

<div id="col-right">
<div class="col-wrap">
<form id="posts-filter" method="post">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxnow ); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr( $typenow ); ?>" />

<?php $wp_list_table->display(); ?>

</form>

<?php if ( 'category' == $taxnow ) : ?>
<div class="form-wrap edit-term-notes">
<p>
	<?php
	echo '<strong>' . __( 'Note:' ) . '</strong><br />';
	printf(
		/* translators: %s: default category */
		__( 'Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the category %s.' ),
		/** This filter is documented in wp-includes/category-template.php */
		'<strong>' . apply_filters( 'the_category', get_cat_name( get_option( 'default_category' ) ) ) . '</strong>'
	);
	?>
</p>
<?php if ( current_user_can( 'import' ) ) : ?>
<p><?php printf( __( 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.' ), esc_url( $import_link ) ) ?></p>
<?php endif; ?>
</div>
<?php elseif ( 'post_tag' == $taxnow && current_user_can( 'import' ) ) : ?>
<div class="form-wrap edit-term-notes">
<p><?php printf( __( 'Tags can be selectively converted to categories using the <a href="%s">tag to category converter</a>.' ), esc_url( $import_link ) ) ;?></p>
</div>
<?php endif;

/**
 * Fires after the taxonomy list table.
 *
 * The dynamic portion of the hook name, `$taxnow`, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxnow The taxonomy name.
 */
do_action( "after-{$taxnow}-table", $taxnow );
?>

</div>
</div><!-- /col-right -->

</div><!-- /col-container -->
</div><!-- /wrap -->

<?php if ( ! wp_is_mobile() ) : ?>
<script type="text/javascript">
try{document.forms.addtag['tag-name'].focus();}catch(e){}
</script>
<?php
endif;

$wp_list_table->inline_edit();

include( ABSPATH . 'wp-admin/admin-footer.php' );
