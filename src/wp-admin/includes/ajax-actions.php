<?php
/**
 * Administration API: Core Ajax handlers
 *
 * @package WordPress
 * @subpackage Administration
 * @since 2.1.0
 */

use function WP\getApp;

//
// No-privilege Ajax handlers.
//

/**
 * Ajax handler for the Heartbeat API in
 * the no-privilege context.
 *
 * Runs when the user is not logged in.
 *
 * @since 3.6.0
 */
function wp_ajax_nopriv_heartbeat() {
	$app = getApp();
	$_post = $app['request']->request;

	$response = [];

	// screen_id is the same as $current_screen->id and the JS global 'pagenow'.
	if ( $_post->get( 'screen_id' ) ) {
		$screen_id = sanitize_key( $_post->get( 'screen_id' ) );
	} else {
		$screen_id = 'front';
	}

	if ( $_post->get( 'data' ) ) {
		$data = wp_unslash( (array) $_post->get( 'data') );

		/**
		 * Filters Heartbeat Ajax response in no-privilege environments.
		 *
		 * @since 3.6.0
		 *
		 * @param array|object $response  The no-priv Heartbeat response object or array.
		 * @param array        $data      An array of data passed via $_POST.
		 * @param string       $screen_id The screen id.
		 */
		$response = apply_filters( 'heartbeat_nopriv_received', $response, $data, $screen_id );
	}

	/**
	 * Filters Heartbeat Ajax response when no data is passed.
	 *
	 * @since 3.6.0
	 *
	 * @param array|object $response  The Heartbeat response object or array.
	 * @param string       $screen_id The screen id.
	 */
	$response = apply_filters( 'heartbeat_nopriv_send', $response, $screen_id );

	/**
	 * Fires when Heartbeat ticks in no-privilege environments.
	 *
	 * Allows the transport to be easily replaced with long-polling.
	 *
	 * @since 3.6.0
	 *
	 * @param array|object $response  The no-priv Heartbeat response.
	 * @param string       $screen_id The screen id.
	 */
	do_action( 'heartbeat_nopriv_tick', $response, $screen_id );

	// Send the current time according to the server.
	$response['server_time'] = time();

	wp_send_json($response);
}

//
// GET-based Ajax handlers.
//

/**
 * Ajax handler for fetching a list table.
 *
 * @since 3.1.0
 */
function wp_ajax_fetch_list() {
	$app = getApp();
	$_get = $app['request']->query;
	$list_args = $_get->get( 'list_args' );
	$list_class = $list_args['class'];
	check_ajax_referer( "fetch-list-$list_class", '_ajax_fetch_list_nonce' );

	$wp_list_table = _get_list_table( $list_class, array( 'screen' => $list_args['screen']['id'] ) );
	if ( ! $wp_list_table ) {
		wp_die( 0 );
	}

	if ( ! $wp_list_table->ajax_user_can() ) {
		wp_die( -1 );
	}

	$wp_list_table->ajax_response();

	wp_die( 0 );
}

/**
 * Ajax handler for tag search.
 *
 * @since 3.1.0
 */
function wp_ajax_ajax_tag_search() {
	$app = getApp();
	$_get = $app['request']->query;

	if ( ! $_get->get( 'tax' ) ) {
		wp_die( 0 );
	}

	$taxonomy = sanitize_key( $_get->get( 'tax' ) );
	$tax = get_taxonomy( $taxonomy );
	if ( ! $tax ) {
		wp_die( 0 );
	}

	if ( ! current_user_can( $tax->cap->assign_terms ) ) {
		wp_die( -1 );
	}

	$s = wp_unslash( $_get->get( 'q' ) );

	$comma = _x( ',', 'tag delimiter' );
	if ( ',' !== $comma ) {
		$s = str_replace( $comma, ',', $s );
	}
	if ( false !== strpos( $s, ',' ) ) {
		$s = explode( ',', $s );
		$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );

	/**
	 * Filters the minimum number of characters required to fire a tag search via Ajax.
	 *
	 * @since 4.0.0
	 *
	 * @param int         $characters The minimum number of characters required. Default 2.
	 * @param WP_Taxonomy $tax        The taxonomy object.
	 * @param string      $s          The search term.
	 */
	$term_search_min_chars = (int) apply_filters( 'term_search_min_chars', 2, $tax, $s );

	/*
	 * Require $term_search_min_chars chars for matching (default: 2)
	 * ensure it's a non-negative, non-zero integer.
	 */
	if ( ( $term_search_min_chars == 0 ) || ( strlen( $s ) < $term_search_min_chars ) ){
		wp_die();
	}

	$results = get_terms( $taxonomy, array( 'name__like' => $s, 'fields' => 'names', 'hide_empty' => false ) );

	echo join( $results, "\n" );
	wp_die();
}

/**
 * Ajax handler for compression testing.
 *
 * @since 3.1.0
 */
function wp_ajax_wp_compression_test() {
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( -1 );
	}

	if ( ini_get('zlib.output_compression') || 'ob_gzhandler' == ini_get('output_handler') ) {
		update_site_option('can_compress_scripts', 0);
		wp_die( 0 );
	}

	$app = getApp();
	$_get = $app['request']->query;
	if ( $_get->get( 'test' ) ) {
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		header('Content-Type: application/javascript; charset=UTF-8');
		$force_gzip = ( defined('ENFORCE_GZIP') && ENFORCE_GZIP );
		$test_str = '"wpCompressionTest Lorem ipsum dolor sit amet consectetuer mollis sapien urna ut a. Eu nonummy condimentum fringilla tempor pretium platea vel nibh netus Maecenas. Hac molestie amet justo quis pellentesque est ultrices interdum nibh Morbi. Cras mattis pretium Phasellus ante ipsum ipsum ut sociis Suspendisse Lorem. Ante et non molestie. Porta urna Vestibulum egestas id congue nibh eu risus gravida sit. Ac augue auctor Ut et non a elit massa id sodales. Elit eu Nulla at nibh adipiscing mattis lacus mauris at tempus. Netus nibh quis suscipit nec feugiat eget sed lorem et urna. Pellentesque lacus at ut massa consectetuer ligula ut auctor semper Pellentesque. Ut metus massa nibh quam Curabitur molestie nec mauris congue. Volutpat molestie elit justo facilisis neque ac risus Ut nascetur tristique. Vitae sit lorem tellus et quis Phasellus lacus tincidunt nunc Fusce. Pharetra wisi Suspendisse mus sagittis libero lacinia Integer consequat ac Phasellus. Et urna ac cursus tortor aliquam Aliquam amet tellus volutpat Vestibulum. Justo interdum condimentum In augue congue tellus sollicitudin Quisque quis nibh."';

		 if ( 1 == $_get->get( 'test' ) ) {
			echo $test_str;
			wp_die();
		 } elseif ( 2 == $_get->get( 'test' ) ) {
			$accept_encoding = $_server->get( 'HTTP_ACCEPT_ENCODING' );
			if ( ! $accept_encoding ) {
				wp_die( -1 );
			}

			if ( false !== stripos( $accept_encoding, 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
				header('Content-Encoding: deflate');
				$out = gzdeflate( $test_str, 1 );
			} elseif ( false !== stripos( $accept_encoding, 'gzip') && function_exists('gzencode') ) {
				header('Content-Encoding: gzip');
				$out = gzencode( $test_str, 1 );
			} else {
				wp_die( -1 );
			}
			echo $out;
			wp_die();
		} elseif ( 'no' == $_get->get( 'test' ) ) {
			check_ajax_referer( 'update_can_compress_scripts' );
			update_site_option('can_compress_scripts', 0);
		} elseif ( 'yes' == $_get->get( 'test' ) ) {
			check_ajax_referer( 'update_can_compress_scripts' );
			update_site_option('can_compress_scripts', 1);
		}
	}

	wp_die( 0 );
}

/**
 * Ajax handler for image editor previews.
 *
 * @since 3.1.0
 */
function wp_ajax_imgedit_preview() {
	$app = getApp();
	$_get = $app['request']->query;

	$post_id = $_get->getInt( 'postid' );
	if ( empty($post_id) || !current_user_can('edit_post', $post_id) ) {
		wp_die( -1 );
	}

	check_ajax_referer( "image_editor-$post_id" );

	include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );
	if ( ! stream_preview_image($post_id) ) {
		wp_die( -1 );
	}

	wp_die();
}

/**
 * Ajax handler for oEmbed caching.
 *
 * @since 3.1.0
 *
 * @global WP_Embed $wp_embed
 */
function wp_ajax_oembed_cache() {
	$app = getApp();
	$_get = $app['request']->query;

	// this global is a classic...
	$GLOBALS['wp_embed']->cache_oembed( $_get->get( 'post' ) ); //NOSONAR
	wp_die( 0 );
}

/**
 * Ajax handler for user autocomplete.
 *
 * @since 3.4.0
 */
function wp_ajax_autocomplete_user() {
	if ( ! is_multisite() || ! current_user_can( 'promote_users' ) || wp_is_large_network( 'users' ) ) {
		wp_die( -1 );
	}

	/** This filter is documented in wp-admin/user-new.php */
	if ( ! is_super_admin() && ! apply_filters( 'autocomplete_users_for_site_admins', false ) ) {
		wp_die( -1 );
	}

	$return = [];

	$app = getApp();
	$_request = $app['request']->attributes;

	// Check the type of request
	// Current allowed values are `add` and `search`
	if ( $_request->has( 'autocomplete_type' ) && 'search' === $_request->get( 'autocomplete_type' ) ) {
		$type = $_request->get( 'autocomplete_type' );
	} else {
		$type = 'add';
	}

	// Check the desired field for value
	// Current allowed values are `user_email` and `user_login`
	if ( $_request->has( 'autocomplete_field' ) && 'user_email' === $_request->get( 'autocomplete_field' ) ) {
		$field = $_request->get( 'autocomplete_field' );
	} else {
		$field = 'user_login';
	}

	// Exclude current users of this blog
	if ( $_request->has( 'site_id' ) ) {
		$id = $_request->getInt( 'site_id' );
	} else {
		$id = get_current_blog_id();
	}

	$include_blog_users = ( $type == 'search' ? get_users( array( 'blog_id' => $id, 'fields' => 'ID' ) ) : [] );
	$exclude_blog_users = ( $type == 'add' ? get_users( array( 'blog_id' => $id, 'fields' => 'ID' ) ) : [] );

	$users = get_users( array(
		'blog_id' => false,
		'search'  => '*' . $_request->get( 'term' ) . '*',
		'include' => $include_blog_users,
		'exclude' => $exclude_blog_users,
		'search_columns' => [ 'user_login', 'user_nicename', 'user_email' ],
	) );

	foreach ( $users as $user ) {
		$return[] = [
			/* translators: 1: user_login, 2: user_email */
			'label' => sprintf( _x( '%1$s (%2$s)', 'user autocomplete result' ), $user->user_login, $user->user_email ),
			'value' => $user->$field,
		];
	}

	wp_die( wp_json_encode( $return ) );
}

/**
 * Ajax handler for dashboard widgets.
 *
 * @since 3.4.0
 */
function wp_ajax_dashboard_widgets() {
	require_once ABSPATH . 'wp-admin/includes/dashboard.php';

	$app = getApp();
	$_get = $app['request']->query;

	$pagenow =  $_get->get( 'pagenow' );
	if ( $pagenow === 'dashboard-user' || $pagenow === 'dashboard-network' || $pagenow === 'dashboard' ) {
		set_current_screen( $pagenow );
	}

	switch ( $_get->get( 'widget' ) ) {
	case 'dashboard_primary' :
		wp_dashboard_primary();
		break;
	}
	wp_die();
}

/**
 * Ajax handler for Customizer preview logged-in status.
 *
 * @since 3.4.0
 */
function wp_ajax_logged_in() {
	wp_die( 1 );
}

//
// Ajax helpers.
//

/**
 * Sends back current comment total and new page links if they need to be updated.
 *
 * Contrary to normal success Ajax response ("1"), die with time() on success.
 *
 * @access private
 * @since 2.7.0
 *
 * @param int $comment_id
 * @param int $delta
 */
function _wp_ajax_delete_comment_response( $comment_id, $delta = -1 ) {
	$app = getApp();
	$_post = $app['request']->request;

	$total    = $_post->getInt( '_total', 0 );
	$per_page = $_post->getInt( '_per_page', 0 );
	$page     = $_post->getInt( '_page', 0 );
	$url      = $_post->get( '_url' ) ? esc_url_raw( $_post->get( '_url' ) ) : '';

	// JS didn't send us everything we need to know. Just die with success message
	if ( ! $total || ! $per_page || ! $page || ! $url ) {
		$time           = time();
		$comment        = get_comment( $comment_id );
		$comment_status = '';
		$comment_link   = '';

		if ( $comment ) {
			$comment_status = $comment->comment_approved;
		}

		if ( 1 === (int) $comment_status ) {
			$comment_link = get_comment_link( $comment );
		}

		$counts = wp_count_comments();

		$x = new \WP\Ajax\Response( array(
			'what' => 'comment',
			// Here for completeness - not used.
			'id' => $comment_id,
			'supplemental' => array(
				'status' => $comment_status,
				'postId' => $comment ? $comment->comment_post_ID : '',
				'time' => $time,
				'in_moderation' => $counts->moderated,
				'i18n_comments_text' => sprintf(
					_n( '%s Comment', '%s Comments', $counts->approved ),
					number_format_i18n( $counts->approved )
				),
				'i18n_moderation_text' => sprintf(
					_nx( '%s in moderation', '%s in moderation', $counts->moderated, 'comments' ),
					number_format_i18n( $counts->moderated )
				),
				'comment_link' => $comment_link,
			)
		) );
		$x->send();
	}

	$total += $delta;
	if ( $total < 0 ) {
		$total = 0;
	}

	// Only do the expensive stuff on a page-break, and about 1 other time per page
	if ( 0 == $total % $per_page || 1 == mt_rand( 1, $per_page ) ) {
		$post_id = 0;
		// What type of comment count are we looking for?
		$status = 'all';
		$parsed = parse_url( $url );
		if ( isset( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $query_vars );
			if ( !empty( $query_vars['comment_status'] ) ) {
				$status = $query_vars['comment_status'];
			}
			if ( !empty( $query_vars['p'] ) ) {
				$post_id = (int) $query_vars['p'];
			}
			if ( ! empty( $query_vars['comment_type'] ) ) {
				$type = $query_vars['comment_type'];
			}
		}

		if ( empty( $type ) ) {
			// Only use the comment count if not filtering by a comment_type.
			$comment_count = wp_count_comments($post_id);

			// We're looking for a known type of comment count.
			if ( isset( $comment_count->$status ) ) {
				$total = $comment_count->$status;
			}
		}
		// Else use the decremented value from above.
	}

	// The time since the last comment count.
	$time = time();
	$comment = get_comment( $comment_id );

	$x = new \WP\Ajax\Response( array(
		'what' => 'comment',
		// Here for completeness - not used.
		'id' => $comment_id,
		'supplemental' => array(
			'status' => $comment ? $comment->comment_approved : '',
			'postId' => $comment ? $comment->comment_post_ID : '',
			'total_items_i18n' => sprintf( _n( '%s item', '%s items', $total ), number_format_i18n( $total ) ),
			'total_pages' => ceil( $total / $per_page ),
			'total_pages_i18n' => number_format_i18n( ceil( $total / $per_page ) ),
			'total' => $total,
			'time' => $time
		)
	) );
	$x->send();
}

//
// POST-based Ajax handlers.
//

/**
 * Ajax handler for adding a hierarchical term.
 *
 * @access private
 * @since 3.1.0
 */
function _wp_ajax_add_hierarchical_term() {
	$app = getApp();
	$_post = $app['request']->request;

	$action = $_post->get( 'action' );
	$taxonomy = get_taxonomy(substr($action, 4));
	check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );
	if ( !current_user_can( $taxonomy->cap->edit_terms ) ) {
		wp_die( -1 );
	}
	$names = explode(',', $_post->get( 'new' . $taxonomy->name ) );
	$parent = $_post->get( 'new' . $taxonomy->name . '_parent' ) ?
		$_post->getInt( 'new' . $taxonomy->name . '_parent' ) : 0;

	if ( 0 > $parent ) {
		$parent = 0;
	}

	if ( $taxonomy->name == 'category' ) {
		$post_category = (array) $_post->get( 'post_category', [] );
	} else {
		$input = $_post->get( 'tax_input' );
		$post_category = isset( $input[ $taxonomy->name ] ) ? (array) $input[ $taxonomy->name ] : [];
	}
	$checked_categories = array_map( 'absint', (array) $post_category );
	$popular_ids = wp_popular_terms_checklist($taxonomy->name, 0, 10, false);

	foreach ( $names as $cat_name ) {
		$cat_name = trim($cat_name);
		$category_nicename = sanitize_title($cat_name);
		if ( '' === $category_nicename ) {
			continue;
		}
		if ( !$cat_id = term_exists( $cat_name, $taxonomy->name, $parent ) ) {
			$cat_id = wp_insert_term( $cat_name, $taxonomy->name, array( 'parent' => $parent ) );
		}
		if ( is_wp_error( $cat_id ) ) {
			continue;
		} elseif ( is_array( $cat_id ) ) {
			$cat_id = $cat_id['term_id'];
		}
		$checked_categories[] = $cat_id;
		if ( $parent ) {
			// Do these all at once in a second
			continue;
		}

		ob_start();

		wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy->name, 'descendants_and_self' => $cat_id, 'selected_cats' => $checked_categories, 'popular_cats' => $popular_ids ));

		$data = ob_get_clean();

		$add = array(
			'what' => $taxonomy->name,
			'id' => $cat_id,
			'data' => str_replace( array("\n", "\t"), '', $data),
			'position' => -1
		);
	}

	// Foncy - replace the parent and all its children
	if ( $parent ) {
		$parent = get_term( $parent, $taxonomy->name );
		$term_id = $parent->term_id;

		// get the top parent
		while ( $parent->parent ) {
			$parent = get_term( $parent->parent, $taxonomy->name );
			if ( is_wp_error( $parent ) ) {
				break;
			}
			$term_id = $parent->term_id;
		}

		ob_start();

		wp_terms_checklist( 0, array('taxonomy' => $taxonomy->name, 'descendants_and_self' => $term_id, 'selected_cats' => $checked_categories, 'popular_cats' => $popular_ids));

		$data = ob_get_clean();

		$add = array(
			'what' => $taxonomy->name,
			'id' => $term_id,
			'data' => str_replace( array("\n", "\t"), '', $data),
			'position' => -1
		);
	}

	ob_start();

	wp_dropdown_categories( array(
		'taxonomy' => $taxonomy->name, 'hide_empty' => 0, 'name' => 'new'.$taxonomy->name.'_parent', 'orderby' => 'name',
		'hierarchical' => 1, 'show_option_none' => '&mdash; '.$taxonomy->labels->parent_item.' &mdash;'
	) );

	$sup = ob_get_clean();

	$add['supplemental'] = array( 'newcat_parent' => $sup );

	$x = new \WP\Ajax\Response( $add );
	$x->send();
}

/**
 * Ajax handler for deleting a comment.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_comment() {
	$app = getApp();
	$_post = $app['request']->request;

	$id = $_post->getInt( 'id', 0 );

	if ( !$comment = get_comment( $id ) ) {
		wp_die( time() );
	}
	if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) ) {
		wp_die( -1 );
	}

	check_ajax_referer( "delete-comment_$id" );
	$status = wp_get_comment_status( $comment );

	$delta = -1;
	if ( 1 == $_post->get( 'trash' ) ) {
		if ( 'trash' == $status ) {
			wp_die( time() );
		}
		$r = wp_trash_comment( $comment );
	} elseif ( 1 == $_post->get( 'untrash' ) ) {
		if ( 'trash' != $status ) {
			wp_die( time() );
		}
		$r = wp_untrash_comment( $comment );
		if ( ! $_post->get( 'comment_status' ) || $_post->get( 'comment_status' ) != 'trash' ) {
			// undo trash, not in trash
			$delta = 1;
		}
	} elseif ( 1 == $_post->get( 'spam' ) ) {
		if ( 'spam' == $status ) {
			wp_die( time() );
		}
		$r = wp_spam_comment( $comment );
	} elseif ( 1 == $_post->get( 'unspam' ) ) {
		if ( 'spam' != $status ) {
			wp_die( time() );
		}
		$r = wp_unspam_comment( $comment );
		if ( ! $_post->get( 'comment_status' ) || $_post->get( 'comment_status' ) != 'spam' ) {
			// undo spam, not in spam
			$delta = 1;
		}
	} elseif ( 1 == $_post->get( 'delete' ) ) {
		$r = wp_delete_comment( $comment );
	} else {
		wp_die( -1 );
	}

	if ( $r ) {
		// Decide if we need to send back '1' or a more complicated response including page links and comment counts
		_wp_ajax_delete_comment_response( $comment->comment_ID, $delta );
	}
	wp_die( 0 );
}

/**
 * Ajax handler for deleting a tag.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_tag() {
	$app = getApp();
	$_post = $app['request']->request;

	$tag_id = $_post->getInt( 'tag_ID' );
	check_ajax_referer( "delete-tag_$tag_id" );

	if ( ! current_user_can( 'delete_term', $tag_id ) ) {
		wp_die( -1 );
	}

	$taxonomy = $_post->get( 'taxonomy', 'post_tag' );
	$tag = get_term( $tag_id, $taxonomy );
	if ( !$tag || is_wp_error( $tag ) ) {
		wp_die( 1 );
	}

	if ( wp_delete_term($tag_id, $taxonomy)) {
		wp_die( 1 );
	} else {
		wp_die( 0 );
	}
}

/**
 * Ajax handler for deleting a link.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_link() {
	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	check_ajax_referer( "delete-bookmark_$id" );
	if ( !current_user_can( 'manage_links' ) ) {
		wp_die( -1 );
	}

	$link = get_bookmark( $id );
	if ( !$link || is_wp_error( $link ) ) {
		wp_die( 1 );
	}

	if ( wp_delete_link( $id ) ) {
		wp_die( 1 );
	} else {
		wp_die( 0 );
	}
}

/**
 * Ajax handler for deleting meta.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_meta() {
	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	check_ajax_referer( "delete-meta_$id" );
	if ( !$meta = get_metadata_by_mid( 'post', $id ) ) {
		wp_die( 1 );
	}

	if ( is_protected_meta( $meta->meta_key, 'post' ) || ! current_user_can( 'delete_post_meta',  $meta->post_id, $meta->meta_key ) ) {
		wp_die( -1 );
	}
	if ( delete_meta( $meta->meta_id ) ) {
		wp_die( 1 );
	}
	wp_die( 0 );
}

/**
 * Ajax handler for deleting a post.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_delete_post( $action ) {
	if ( empty( $action ) ) {
		$action = 'delete-post';
	}

	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	check_ajax_referer( "{$action}_$id" );
	if ( !current_user_can( 'delete_post', $id ) ) {
		wp_die( -1 );
	}

	if ( !get_post( $id ) ) {
		wp_die( 1 );
	}

	if ( wp_delete_post( $id ) ) {
		wp_die( 1 );
	} else {
		wp_die( 0 );
	}
}

/**
 * Ajax handler for sending a post to the trash.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_trash_post( $action ) {
	if ( empty( $action ) ) {
		$action = 'trash-post';
	}

	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	check_ajax_referer( "{$action}_$id" );
	if ( !current_user_can( 'delete_post', $id ) ) {
		wp_die( -1 );
	}

	if ( !get_post( $id ) ) {
		wp_die( 1 );
	}

	if ( 'trash-post' == $action ) {
		$done = wp_trash_post( $id );
	} else {
		$done = wp_untrash_post( $id );
	}

	if ( $done ) {
		wp_die( 1 );
	}

	wp_die( 0 );
}

/**
 * Ajax handler to restore a post from the trash.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_untrash_post( $action ) {
	if ( empty( $action ) ) {
		$action = 'untrash-post';
	}
	wp_ajax_trash_post( $action );
}

/**
 * @since 3.1.0
 *
 * @param string $action
 */
function wp_ajax_delete_page( $action ) {
	if ( empty( $action ) ) {
		$action = 'delete-page';
	}

	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	check_ajax_referer( "{$action}_$id" );
	if ( !current_user_can( 'delete_page', $id ) ) {
		wp_die( -1 );
	}

	if ( ! get_post( $id ) ) {
		wp_die( 1 );
	}

	if ( wp_delete_post( $id ) ) {
		wp_die( 1 );
	} else {
		wp_die( 0 );
	}
}

/**
 * Ajax handler to dim a comment.
 *
 * @since 3.1.0
 */
function wp_ajax_dim_comment() {
	$app = getApp();
	$_post = $app['request']->request;
	$id = $_post->getInt( 'id', 0 );

	if ( !$comment = get_comment( $id ) ) {
		$x = new \WP\Ajax\Response( array(
			'what' => 'comment',
			'id' => new WP_Error('invalid_comment', sprintf(__('Comment %d does not exist'), $id))
		) );
		$x->send();
	}

	if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) && ! current_user_can( 'moderate_comments' ) ) {
		wp_die( -1 );
	}

	$current = wp_get_comment_status( $comment );
	if ( $_post->get( 'new' ) && $_post->get( 'new' ) == $current ) {
		wp_die( time() );
	}

	check_ajax_referer( "approve-comment_$id" );
	if ( in_array( $current, array( 'unapproved', 'spam' ) ) ) {
		$result = wp_set_comment_status( $comment, 'approve', true );
	} else {
		$result = wp_set_comment_status( $comment, 'hold', true );
	}

	if ( is_wp_error($result) ) {
		$x = new \WP\Ajax\Response( array(
			'what' => 'comment',
			'id' => $result
		) );
		$x->send();
	}

	// Decide if we need to send back '1' or a more complicated response including page links and comment counts
	_wp_ajax_delete_comment_response( $comment->comment_ID );
	wp_die( 0 );
}

/**
 * Ajax handler for adding a link category.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_add_link_category( $action ) {
	if ( empty( $action ) ) {
		$action = 'add-link-category';
	}
	check_ajax_referer( $action );
	$tax = get_taxonomy( 'link_category' );
	if ( ! current_user_can( $tax->cap->manage_terms ) ) {
		wp_die( -1 );
	}

	$app = getApp();
	$_post = $app['request']->request;
	$names = explode(',', wp_unslash( $_post->get( 'newcat' ) ) );
	$x = new \WP\Ajax\Response();
	foreach ( $names as $cat_name ) {
		$cat_name = trim($cat_name);
		$slug = sanitize_title($cat_name);
		if ( '' === $slug ) {
			continue;
		}
		if ( !$cat_id = term_exists( $cat_name, 'link_category' ) ) {
			$cat_id = wp_insert_term( $cat_name, 'link_category' );
		}
		if ( is_wp_error( $cat_id ) ) {
			continue;
		} elseif ( is_array( $cat_id ) ) {
			$cat_id = $cat_id['term_id'];
		}
		$cat_name = esc_html( $cat_name );
		$x->add( array(
			'what' => 'link-category',
			'id' => $cat_id,
			'data' => "<li id='link-category-$cat_id'><label for='in-link-category-$cat_id' class='selectit'><input value='" . esc_attr($cat_id) . "' type='checkbox' checked='checked' name='link_category[]' id='in-link-category-$cat_id'/> $cat_name</label></li>",
			'position' => -1
		) );
	}
	$x->send();
}

/**
 * Ajax handler to add a tag.
 *
 * @since 3.1.0
 */
function wp_ajax_add_tag() {
	$app = getApp();
	$_post = $app['request']->request;

	check_ajax_referer( 'add-tag', '_wpnonce_add-tag' );
	$taxonomy = $_post->get( 'taxonomy', 'post_tag' );
	$tax = get_taxonomy($taxonomy);

	if ( !current_user_can( $tax->cap->edit_terms ) ) {
		wp_die( -1 );
	}

	$x = new \WP\Ajax\Response();

	$tag = wp_insert_term( $_post->get( 'tag-name' ), $taxonomy, $_post->all() );

	if ( !$tag || is_wp_error($tag) || (!$tag = get_term( $tag['term_id'], $taxonomy )) ) {
		$message = __('An error has occurred. Please reload the page and try again.');
		if ( is_wp_error($tag) && $tag->get_error_message() ) {
			$message = $tag->get_error_message();
		}

		$x->add( array(
			'what' => 'taxonomy',
			'data' => new WP_Error('error', $message )
		) );
		$x->send();
	}

	$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => $_post->get( 'screen' ) ) );

	$level = 0;
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$level = count( get_ancestors( $tag->term_id, $taxonomy, 'taxonomy' ) );
		ob_start();
		$wp_list_table->single_row( $tag, $level );
		$noparents = ob_get_clean();
	}

	ob_start();
	$wp_list_table->single_row( $tag );
	$parents = ob_get_clean();

	$x->add( array(
		'what' => 'taxonomy',
		'supplemental' => compact('parents', 'noparents')
	) );
	$x->add( array(
		'what' => 'term',
		'position' => $level,
		'supplemental' => (array) $tag
	) );
	$x->send();
}

/**
 * Ajax handler for getting a tagcloud.
 *
 * @since 3.1.0
 */
function wp_ajax_get_tagcloud() {
	$app = getApp();
	$_post = $app['request']->request;

	if ( ! $_post->get( 'tax' ) ) {
		wp_die( 0 );
	}

	$taxonomy = sanitize_key( $_post->get( 'tax' ) );
	$tax = get_taxonomy( $taxonomy );
	if ( ! $tax ) {
		wp_die( 0 );
	}

	if ( ! current_user_can( $tax->cap->assign_terms ) ) {
		wp_die( -1 );
	}

	$tags = get_terms( $taxonomy, array( 'number' => 45, 'orderby' => 'count', 'order' => 'DESC' ) );

	if ( empty( $tags ) ) {
		wp_die( $tax->labels->not_found );
	}

	if ( is_wp_error( $tags ) ) {
		wp_die( $tags->get_error_message() );
	}

	foreach ( $tags as $key => $tag ) {
		$tags[ $key ]->link = '#';
		$tags[ $key ]->id = $tag->term_id;
	}

	// We need raw tag names here, so don't filter the output
	$return = wp_generate_tag_cloud( $tags, array('filter' => 0) );

	if ( empty($return) ) {
		wp_die( 0 );
	}

	echo $return;

	wp_die();
}

/**
 * Ajax handler for getting comments.
 *
 * @since 3.1.0
 *
 * @global int           $post_id
 *
 * @param string $action Action to perform.
 */
function wp_ajax_get_comments( $action ) {
	if ( empty( $action ) ) {
		$action = 'get-comments';
	}
	check_ajax_referer( $action );

	$app = getApp();
	$_request = $app['request']->attributes;
	$post_id = $app->get( 'post_id' );

	if ( empty( $post_id ) && ! empty( $_request->get( 'p' ) ) ) {
		$id = $_request->getInt( 'p' );
		if ( ! empty( $id ) ) {
			$post_id = $id;
		}
	}

	if ( empty( $post_id ) ) {
		wp_die( -1 );
	}

	$wp_list_table = _get_list_table( \WP\Comment\Admin\PostListTable::class, [
		'screen' => 'edit-comments'
	] );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( -1 );
	}

	$wp_list_table->prepare_items();

	if ( ! $wp_list_table->has_items() ) {
		wp_die( 1 );
	}

	$x = new \WP\Ajax\Response();
	ob_start();
	foreach ( $wp_list_table->items as $comment ) {
		if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) && 0 === $comment->comment_approved ) {
			continue;
		}
		get_comment( $comment );
		$wp_list_table->single_row( $comment );
	}
	$comment_list_item = ob_get_clean();

	$x->add( [
		'what' => 'comments',
		'data' => $comment_list_item
	] );
	$x->send();
}

/**
 * Ajax handler for replying to a comment.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_replyto_comment( $action ) {
	if ( empty( $action ) ) {
		$action = 'replyto-comment';
	}

	check_ajax_referer( $action, '_ajax_nonce-replyto-comment' );

	$app = getApp();
	$_post = $app['request']->request;
	$_request = $app['request']->attributes;

	$comment_post_ID = $_post->getInt( 'comment_post_ID' );
	$post = get_post( $comment_post_ID );
	if ( ! $post ) {
		wp_die( -1 );
	}

	if ( !current_user_can( 'edit_post', $comment_post_ID ) ) {
		wp_die( -1 );
	}

	if ( empty( $post->post_status ) ) {
		wp_die( 1 );
	} elseif ( in_array($post->post_status, array('draft', 'pending', 'trash') ) ) {
		wp_die( __('ERROR: you are replying to a comment on a draft post.') );
	}

	$user = wp_get_current_user();
	if ( $user->exists() ) {
		$user_ID = $user->ID;
		$comment_author       = wp_slash( $user->display_name );
		$comment_author_email = wp_slash( $user->user_email );
		$comment_author_url   = wp_slash( $user->user_url );
		$comment_content      = trim( $_post->get( 'content' ) );
		$comment_type         = trim( $_post->get( 'comment_type', '' ) );
		if ( current_user_can( 'unfiltered_html' ) ) {
			if ( ! $_post->get( '_wp_unfiltered_html_comment' ) ) {
				$_post->set( '_wp_unfiltered_html_comment', '' );
			}

			if ( wp_create_nonce( 'unfiltered-html-comment' ) != $_post->get( '_wp_unfiltered_html_comment' ) ) {
				// start with a clean slate
				kses_remove_filters();
				// set up the filters
				kses_init_filters();
			}
		}
	} else {
		wp_die( __( 'Sorry, you must be logged in to reply to a comment.' ) );
	}

	if ( '' == $comment_content ) {
		wp_die( __( 'ERROR: please type a comment.' ) );
	}

	$comment_parent = $_post->getInt( 'comment_ID', 0 );
	$comment_auto_approved = false;
	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

	// Automatically approve parent comment.
	if ( $_post->get( 'approve_parent' ) ) {
		$parent = get_comment( $comment_parent );

		if ( $parent && $parent->comment_approved === '0' && $parent->comment_post_ID == $comment_post_ID ) {
			if ( ! current_user_can( 'edit_comment', $parent->comment_ID ) ) {
				wp_die( -1 );
			}

			if ( wp_set_comment_status( $parent, 'approve' ) ) {
				$comment_auto_approved = true;
			}
		}
	}

	$comment_id = wp_new_comment( $commentdata );
	$comment = get_comment($comment_id);
	if ( ! $comment ) {
		wp_die( 1 );
	}

	$position = $_post->getInt( 'position', '-1' );

	ob_start();
	if ( 'dashboard' === $_request->get( 'mode' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );
		_wp_dashboard_recent_comments_row( $comment );
	} else {
		if ( 'single' === $_request->get( 'mode' ) ) {
			$wp_list_table = _get_list_table( \WP\Comment\Admin\PostListTable::class, array( 'screen' => 'edit-comments' ) );
		} else {
			$wp_list_table = _get_list_table( \WP\Comment\Admin\ListTable::class, array( 'screen' => 'edit-comments' ) );
		}
		$wp_list_table->single_row( $comment );
	}
	$comment_list_item = ob_get_clean();

	$response =  array(
		'what' => 'comment',
		'id' => $comment->comment_ID,
		'data' => $comment_list_item,
		'position' => $position
	);

	$counts = wp_count_comments();
	$response['supplemental'] = array(
		'in_moderation' => $counts->moderated,
		'i18n_comments_text' => sprintf(
			_n( '%s Comment', '%s Comments', $counts->approved ),
			number_format_i18n( $counts->approved )
		),
		'i18n_moderation_text' => sprintf(
			_nx( '%s in moderation', '%s in moderation', $counts->moderated, 'comments' ),
			number_format_i18n( $counts->moderated )
		)
	);

	if ( $comment_auto_approved ) {
		$response['supplemental']['parent_approved'] = $parent->comment_ID;
		$response['supplemental']['parent_post_id'] = $parent->comment_post_ID;
	}

	$x = new \WP\Ajax\Response();
	$x->add( $response );
	$x->send();
}

/**
 * Ajax handler for editing a comment.
 *
 * @since 3.1.0
 */
function wp_ajax_edit_comment() {
	check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment' );

	$app = getApp();
	$_post = $app['request']->request;
	$comment_id = $_post->getInt( 'comment_ID' );
	if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
		wp_die( -1 );
	}

	if ( '' == $_post->get( 'content' ) ) {
		wp_die( __( 'ERROR: please type a comment.' ) );
	}

	if ( $_post->get( 'status' ) ) {
		$_post->set( 'comment_status', $_post->get( 'status' ) );
	}
	edit_comment();

	$position = $_post->getInt( 'position', '-1' );
	$checkbox = true == $_post->get( 'checkbox' ) ? 1 : 0;
	$wp_list_table = _get_list_table( $checkbox ?
		\WP\Comment\Admin\ListTable::class :
		\WP\Comment\Admin\PostListTable::class,
		array( 'screen' => 'edit-comments' )
	);

	$comment = get_comment( $comment_id );
	if ( empty( $comment->comment_ID ) ) {
		wp_die( -1 );
	}

	ob_start();
	$wp_list_table->single_row( $comment );
	$comment_list_item = ob_get_clean();

	$x = new \WP\Ajax\Response();

	$x->add( array(
		'what' => 'edit_comment',
		'id' => $comment->comment_ID,
		'data' => $comment_list_item,
		'position' => $position
	));

	$x->send();
}

/**
 * Ajax handler for adding a menu item.
 *
 * @since 3.1.0
 */
function wp_ajax_add_menu_item() {
	check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}

	require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

	$app = getApp();
	$_post = $app['request']->request;

	// For performance reasons, we omit some object properties from the checklist.
	// The following is a hacky way to restore them when adding non-custom items.

	$menu_items_data = [];
	foreach ( (array) $_post->get( 'menu-item' ) as $menu_item_data ) {
		if (
			! empty( $menu_item_data['menu-item-type'] ) &&
			'custom' != $menu_item_data['menu-item-type'] &&
			! empty( $menu_item_data['menu-item-object-id'] )
		) {
			switch( $menu_item_data['menu-item-type'] ) {
				case 'post_type' :
					$_object = get_post( $menu_item_data['menu-item-object-id'] );
				break;

				case 'post_type_archive' :
					$_object = get_post_type_object( $menu_item_data['menu-item-object'] );
				break;

				case 'taxonomy' :
					$_object = get_term( $menu_item_data['menu-item-object-id'], $menu_item_data['menu-item-object'] );
				break;
			}

			$_menu_items = array_map( 'wp_setup_nav_menu_item', array( $_object ) );
			$_menu_item = reset( $_menu_items );

			// Restore the missing menu item properties
			$menu_item_data['menu-item-description'] = $_menu_item->description;
		}

		$menu_items_data[] = $menu_item_data;
	}

	$item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
	if ( is_wp_error( $item_ids ) ) {
		wp_die( 0 );
	}

	$menu_items = [];

	foreach ( (array) $item_ids as $menu_item_id ) {
		$menu_obj = get_post( $menu_item_id );
		if ( ! empty( $menu_obj->ID ) ) {
			$menu_obj = wp_setup_nav_menu_item( $menu_obj );
			// don't show "(pending)" in ajax-added items
			$menu_obj->label = $menu_obj->title;
			$menu_items[] = $menu_obj;
		}
	}

	/** This filter is documented in wp-admin/includes/nav-menu.php */
	$walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $_post->get( 'menu' ) );

	if ( ! class_exists( $walker_class_name ) ) {
		wp_die( 0 );
	}

	if ( ! empty( $menu_items ) ) {
		$args = array(
			'after' => '',
			'before' => '',
			'link_after' => '',
			'link_before' => '',
			'walker' => new $walker_class_name,
		);
		echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
	}
	wp_die();
}

/**
 * Ajax handler for adding meta.
 *
 * @since 3.1.0
 */
function wp_ajax_add_meta() {
	check_ajax_referer( 'add-meta', '_ajax_nonce-add-meta' );

	$app = getApp();
	$_post = $app['request']->request;

	$c = 0;
	$pid = $_post->getInt( 'post_id' );
	$post = get_post( $pid );

	if ( $_post->get( 'metakeyselect' ) || $_post->get( 'metakeyinput' ) ) {
		if ( !current_user_can( 'edit_post', $pid ) ) {
			wp_die( -1 );
		}
		if ( $_post->get( 'metakeyselect' ) && '#NONE#' == $_post->get( 'metakeyselect' ) && empty( $_post->get( 'metakeyinput' ) ) ) {
			wp_die( 1 );
		}

		// If the post is an autodraft, save the post as a draft and then attempt to save the meta.
		if ( $post->post_status == 'auto-draft' ) {
			$post_data = [];
			// Warning fix
			$post_data['action'] = 'draft';
			$post_data['post_ID'] = $pid;
			$post_data['post_type'] = $post->post_type;
			$post_data['post_status'] = 'draft';
			$now = current_time('timestamp', 1);
			$post_data['post_title'] = sprintf( __( 'Draft created on %1$s at %2$s' ), date( __( 'F j, Y' ), $now ), date( __( 'g:i a' ), $now ) );

			$pid = edit_post( $post_data );
			if ( $pid ) {
				if ( is_wp_error( $pid ) ) {
					$x = new \WP\Ajax\Response( array(
						'what' => 'meta',
						'data' => $pid
					) );
					$x->send();
				}

				if ( !$mid = add_meta( $pid ) ) {
					wp_die( __( 'Please provide a custom field value.' ) );
				}
			} else {
				wp_die( 0 );
			}
		} elseif ( ! $mid = add_meta( $pid ) ) {
			wp_die( __( 'Please provide a custom field value.' ) );
		}

		$meta = get_metadata_by_mid( 'post', $mid );
		$pid = (int) $meta->post_id;
		$meta = get_object_vars( $meta );
		$x = new \WP\Ajax\Response( array(
			'what' => 'meta',
			'id' => $mid,
			'data' => _list_meta_row( $meta, $c ),
			'position' => 1,
			'supplemental' => array('postid' => $pid)
		) );
	// Update?
	} else {
		$m = $_post->get( 'meta' );
		$mid = (int) key( $m );
		$key = wp_unslash( $m[ $mid ]['key'] );
		$value = wp_unslash( $m[ $mid ]['value'] );
		if ( '' == trim($key) ) {
			wp_die( __( 'Please provide a custom field name.' ) );
		}
		if ( '' == trim($value) ) {
			wp_die( __( 'Please provide a custom field value.' ) );
		}
		if ( ! $meta = get_metadata_by_mid( 'post', $mid ) ) {
			wp_die( 0 );
		}
		// if meta doesn't exist
		if ( is_protected_meta( $meta->meta_key, 'post' ) || is_protected_meta( $key, 'post' ) ||
			! current_user_can( 'edit_post_meta', $meta->post_id, $meta->meta_key ) ||
			! current_user_can( 'edit_post_meta', $meta->post_id, $key ) ) {
			wp_die( -1 );
		}
		if (
			( $meta->meta_value != $value || $meta->meta_key != $key ) &&
			! update_metadata_by_mid( 'post', $mid, $value, $key )
		) {
			wp_die( 0 );
			// We know meta exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
		}

		$x = new \WP\Ajax\Response( array(
			'what' => 'meta',
			'id' => $mid, 'old_id' => $mid,
			'data' => _list_meta_row( array(
				'meta_key' => $key,
				'meta_value' => $value,
				'meta_id' => $mid
			), $c ),
			'position' => 0,
			'supplemental' => array('postid' => $meta->post_id)
		) );
	}
	$x->send();
}

/**
 * Ajax handler for adding a user.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_add_user( $action ) {
	if ( empty( $action ) ) {
		$action = 'add-user';
	}

	check_ajax_referer( $action );
	if ( ! current_user_can('create_users') ) {
		wp_die( -1 );
	}
	if ( ! $user_id = edit_user() ) {
		wp_die( 0 );
	} elseif ( is_wp_error( $user_id ) ) {
		$x = new \WP\Ajax\Response( array(
			'what' => 'user',
			'id' => $user_id
		) );
		$x->send();
	}
	$user_object = get_userdata( $user_id );

	$wp_list_table = _get_list_table('WP_Users_List_Table');

	$role = current( $user_object->roles );

	$x = new \WP\Ajax\Response( array(
		'what' => 'user',
		'id' => $user_id,
		'data' => $wp_list_table->single_row( $user_object, '', $role ),
		'supplemental' => array(
			'show-link' => sprintf(
				/* translators: %s: the new user */
				__( 'User %s added' ),
				'<a href="#user-' . $user_id . '">' . $user_object->user_login . '</a>'
			),
			'role' => $role,
		)
	) );
	$x->send();
}

/**
 * Ajax handler for closed post boxes.
 *
 * @since 3.1.0
 */
function wp_ajax_closed_postboxes() {
	$app = getApp();
	$_post = $app['request']->request;

	check_ajax_referer( 'closedpostboxes', 'closedpostboxesnonce' );
	$closed = $_post->get( 'closed' ) ? explode( ',', $_post->get( 'closed' ) ) : [];
	$closed = array_filter($closed);

	$hidden = $_post->get( 'hidden' ) ? explode( ',', $_post->get( 'hidden' ) ) : [];
	$hidden = array_filter($hidden);

	$page = $_post->get( 'page', '' );

	if ( $page != sanitize_key( $page ) ) {
		wp_die( 0 );
	}

	if ( ! $user = wp_get_current_user() ) {
		wp_die( -1 );
	}

	if ( is_array($closed) ) {
		update_user_option($user->ID, "closedpostboxes_$page", $closed, true);
	}

	if ( is_array($hidden) ) {
		// postboxes that are always shown
		$hidden = array_diff( $hidden, array('submitdiv', 'linksubmitdiv', 'manage-menu', 'create-menu') );
		update_user_option($user->ID, "metaboxhidden_$page", $hidden, true);
	}

	wp_die( 1 );
}

/**
 * Ajax handler for hidden columns.
 *
 * @since 3.1.0
 */
function wp_ajax_hidden_columns() {
	$app = getApp();
	$_post = $app['request']->request;

	check_ajax_referer( 'screen-options-nonce', 'screenoptionnonce' );
	$page = $_post->get( 'page', '' );

	if ( $page != sanitize_key( $page ) ) {
		wp_die( 0 );
	}

	if ( ! $user = wp_get_current_user() ) {
		wp_die( -1 );
	}

	$hidden = ! empty( $_post->get( 'hidden' ) ) ? explode( ',', $_post->get( 'hidden' ) ) : [];
	update_user_option( $user->ID, "manage{$page}columnshidden", $hidden, true );

	wp_die( 1 );
}

/**
 * Ajax handler for updating whether to display the welcome panel.
 *
 * @since 3.1.0
 */
function wp_ajax_update_welcome_panel() {
	check_ajax_referer( 'welcome-panel-nonce', 'welcomepanelnonce' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}

	$app = getApp();
	$_post = $app['request']->request;
	update_user_meta( get_current_user_id(), 'show_welcome_panel', empty( $_post->get( 'visible' ) ) ? 0 : 1 );

	wp_die( 1 );
}

/**
 * Ajax handler for retrieving menu meta boxes.
 *
 * @since 3.1.0
 */
function wp_ajax_menu_get_metabox() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}

	require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

	$app = getApp();
	$_post = $app['request']->request;

	if ( 'post_type' == $_post->get( 'item-type' ) ) {
		$type = 'posttype';
		$callback = 'wp_nav_menu_item_post_type_meta_box';
		$items = (array) get_post_types( array( 'show_in_nav_menus' => true ), 'object' );
	} elseif ( 'taxonomy' == $_post->get( 'item-type' ) ) {
		$type = 'taxonomy';
		$callback = 'wp_nav_menu_item_taxonomy_meta_box';
		$items = (array) get_taxonomies( array( 'show_ui' => true ), 'object' );
	}

	$obj = $_post->get( 'item-object' );
	if ( $obj && isset( $items[ $obj ] ) ) {
		$menus_meta_box_object = $items[ $obj ];

		/** This filter is documented in wp-admin/includes/nav-menu.php */
		$item = apply_filters( 'nav_menu_meta_box_object', $menus_meta_box_object );
		ob_start();
		call_user_func_array($callback, array(
			null,
			array(
				'id' => 'add-' . $item->name,
				'title' => $item->labels->name,
				'callback' => $callback,
				'args' => $item,
			)
		));

		$markup = ob_get_clean();

		echo wp_json_encode(array(
			'replace-id' => $type . '-' . $item->name,
			'markup' => $markup,
		));
	}

	wp_die();
}

/**
 * Ajax handler for internal linking.
 *
 * @since 3.1.0
 */
function wp_ajax_wp_link_ajax() {
	check_ajax_referer( 'internal-linking', '_ajax_linking_nonce' );

	$args = [];

	$app = getApp();
	$_post = $app['request']->request;

	if ( $_post->get( 'search' ) ) {
		$args['s'] = wp_unslash( $_post->get( 'search' ) );
	}

	if ( $_post->get( 'term' ) ) {
		$args['s'] = wp_unslash( $_post->get( 'term' ) );
	}

	$args['pagenum'] = $_post->getInt( 'page', 1 );

	$results = _WP_Editors::wp_link_query( $args );

	if ( ! isset( $results ) ) {
		wp_die( 0 );
	}

	echo wp_json_encode( $results );
	echo "\n";

	wp_die();
}

/**
 * Ajax handler for menu locations save.
 *
 * @since 3.1.0
 */
function wp_ajax_menu_locations_save() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}
	check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( ! $_post->get( 'menu-locations' ) ) {
		wp_die( 0 );
	}
	set_theme_mod( 'nav_menu_locations', array_map( 'absint', $_post->get( 'menu-locations' ) ) );
	wp_die( 1 );
}

/**
 * Ajax handler for saving the meta box order.
 *
 * @since 3.1.0
 */
function wp_ajax_meta_box_order() {
	check_ajax_referer( 'meta-box-order' );

	$app = getApp();
	$_post = $app['request']->request;

	$order = $_post->get( 'order' ) ? (array) $_post->get( 'order' ) : false;
	$page_columns = $_post->get( 'page_columns', 'auto' );

	if ( $page_columns != 'auto' ) {
		$page_columns = (int) $page_columns;
	}

	$page = $_post->get( 'page', '' );

	if ( $page != sanitize_key( $page ) ) {
		wp_die( 0 );
	}

	if ( ! $user = wp_get_current_user() ) {
		wp_die( -1 );
	}

	if ( $order ) {
		update_user_option($user->ID, "meta-box-order_$page", $order, true);
	}

	if ( $page_columns ) {
		update_user_option($user->ID, "screen_layout_$page", $page_columns, true);
	}

	wp_die( 1 );
}

/**
 * Ajax handler for menu quick searching.
 *
 * @since 3.1.0
 */
function wp_ajax_menu_quick_search() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}

	require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

	$app = getApp();
	$_post = $app['request']->request;
	_wp_ajax_menu_quick_search( $_post->all() );

	wp_die();
}

/**
 * Ajax handler to retrieve a permalink.
 *
 * @since 3.1.0
 */
function wp_ajax_get_permalink() {
	check_ajax_referer( 'getpermalink', 'getpermalinknonce' );

	$app = getApp();
	$_post = $app['request']->request;
	$post_id = $_post->getInt( 'post_id', 0 );
	wp_die( get_preview_post_link( $post_id ) );
}

/**
 * Ajax handler to retrieve a sample permalink.
 *
 * @since 3.1.0
 */
function wp_ajax_sample_permalink() {
	check_ajax_referer( 'samplepermalink', 'samplepermalinknonce' );

	$app = getApp();
	$_post = $app['request']->request;

	$post_id = $_post->getInt( 'post_id', 0 );
	$title = $_post->get( 'new_title', '' );
	$slug = $_post->get( 'new_slug', null );
	wp_die( get_sample_permalink_html( $post_id, $title, $slug ) );
}

/**
 * Ajax handler for Quick Edit saving a post from a list table.
 *
 * @since 3.1.0
 */
function wp_ajax_inline_save() {
	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( ! $_post->get( 'post_ID' ) || ! ( $post_ID = $_post->getInt( 'post_ID' ) ) ) {
		wp_die();
	}

	if ( 'page' == $_post->get( 'post_type' ) ) {
		if ( ! current_user_can( 'edit_page', $post_ID ) ) {
			wp_die( __( 'Sorry, you are not allowed to edit this page.' ) );
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_ID ) ) {
			wp_die( __( 'Sorry, you are not allowed to edit this post.' ) );
		}
	}

	if ( $last = wp_check_post_lock( $post_ID ) ) {
		$last_user = get_userdata( $last );
		$last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
		printf(
			$_post->get( 'post_type' ) == 'page' ?
				__( 'Saving is disabled: %s is currently editing this page.' ) :
				__( 'Saving is disabled: %s is currently editing this post.' ),
			esc_html( $last_user_name )
		);
		wp_die();
	}

	$data = &$_post->all();

	$post = get_post( $post_ID, ARRAY_A );

	// Since it's coming from the database.
	$post = wp_slash($post);

	$data['content'] = $post['post_content'];
	$data['excerpt'] = $post['post_excerpt'];

	// Rename.
	$data['user_ID'] = get_current_user_id();

	if ( isset($data['post_parent']) ) {
		$data['parent_id'] = $data['post_parent'];
	}

	// Status.
	if ( isset( $data['keep_private'] ) && 'private' == $data['keep_private'] ) {
		$data['visibility']  = 'private';
		$data['post_status'] = 'private';
	} else {
		$data['post_status'] = $data['_status'];
	}

	if ( empty($data['comment_status']) ) {
		$data['comment_status'] = 'closed';
	}
	if ( empty($data['ping_status']) ) {
		$data['ping_status'] = 'closed';
	}

	// Exclude terms from taxonomies that are not supposed to appear in Quick Edit.
	if ( ! empty( $data['tax_input'] ) ) {
		foreach ( $data['tax_input'] as $taxonomy => $terms ) {
			$tax_object = get_taxonomy( $taxonomy );
			/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
			if ( ! apply_filters( 'quick_edit_show_taxonomy', $tax_object->show_in_quick_edit, $taxonomy, $post['post_type'] ) ) {
				unset( $data['tax_input'][ $taxonomy ] );
			}
		}
	}

	// Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
	if ( ! empty( $data['post_name'] ) && in_array( $post['post_status'], array( 'draft', 'pending' ) ) ) {
		$post['post_status'] = 'publish';
		$data['post_name'] = wp_unique_post_slug( $data['post_name'], $post['ID'], $post['post_status'], $post['post_type'], $post['post_parent'] );
	}

	// Update the post.
	edit_post();

	$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => $_post->get( 'screen' ) ) );

	$app->set( 'mode', $_post->get( 'post_view' ) === 'excerpt' ? 'excerpt' : 'list' );

	$level = 0;
	if ( is_post_type_hierarchical( $wp_list_table->screen->post_type ) ) {
		$request_post = array( get_post( $_post->get( 'post_ID' ) ) );
		$parent       = $request_post[0]->post_parent;

		while ( $parent > 0 ) {
			$parent_post = get_post( $parent );
			$parent      = $parent_post->post_parent;
			$level++;
		}
	}

	$wp_list_table->display_rows( array( get_post( $_post->get( 'post_ID' ) ) ), $level );

	wp_die();
}

/**
 * Ajax handler for quick edit saving for a term.
 *
 * @since 3.1.0
 */
function wp_ajax_inline_save_tax() {
	check_ajax_referer( 'taxinlineeditnonce', '_inline_edit' );

	$app = getApp();
	$_post = $app['request']->request;

	$taxonomy = sanitize_key( $_post->get( 'taxonomy' ) );
	$tax = get_taxonomy( $taxonomy );
	if ( ! $tax ) {
		wp_die( 0 );
	}

	if ( ! $_post->get( 'tax_ID' ) || ! ( $id = $_post->getInt( 'tax_ID' ) ) ) {
		wp_die( -1 );
	}

	if ( ! current_user_can( 'edit_term', $id ) ) {
		wp_die( -1 );
	}

	$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-' . $taxonomy ) );

	$tag = get_term( $id, $taxonomy );
	$_post->set( 'description', $tag->description );

	$updated = wp_update_term( $id, $taxonomy, $_post->all() );
	if ( $updated && !is_wp_error($updated) ) {
		$tag = get_term( $updated['term_id'], $taxonomy );
		if ( !$tag || is_wp_error( $tag ) ) {
			if ( is_wp_error($tag) && $tag->get_error_message() ) {
							wp_die( $tag->get_error_message() );
			}
			wp_die( __( 'Item not updated.' ) );
		}
	} else {
		if ( is_wp_error($updated) && $updated->get_error_message() ) {
			wp_die( $updated->get_error_message() );
		}
		wp_die( __( 'Item not updated.' ) );
	}
	$level = 0;
	$parent = $tag->parent;
	while ( $parent > 0 ) {
		$parent_tag = get_term( $parent, $taxonomy );
		$parent = $parent_tag->parent;
		$level++;
	}
	$wp_list_table->single_row( $tag, $level );
	wp_die();
}

/**
 * Ajax handler for querying posts for the Find Posts modal.
 *
 * @see window.findPosts
 *
 * @since 3.1.0
 */
function wp_ajax_find_posts() {
	check_ajax_referer( 'find-posts' );

	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	unset( $post_types['attachment'] );

	$app = getApp();
	$_post = $app['request']->request;

	$s = wp_unslash( $_post->get( 'ps' ) );
	$args = array(
		'post_type' => array_keys( $post_types ),
		'post_status' => 'any',
		'posts_per_page' => 50,
	);
	if ( '' !== $s ) {
		$args['s'] = $s;
	}

	$posts = get_posts( $args );

	if ( ! $posts ) {
		wp_send_json_error( __( 'No items found.' ) );
	}

	$html = '<table class="widefat"><thead><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th class="no-break">'.__('Type').'</th><th class="no-break">'.__('Date').'</th><th class="no-break">'.__('Status').'</th></tr></thead><tbody>';
	$alt = '';
	foreach ( $posts as $post ) {
		$title = trim( $post->post_title ) ? $post->post_title : __( '(no title)' );
		$alt = ( 'alternate' == $alt ) ? '' : 'alternate';

		switch ( $post->post_status ) {
		case 'publish' :
		case 'private' :
			$stat = __('Published');
			break;
		case 'future' :
			$stat = __('Scheduled');
			break;
		case 'pending' :
			$stat = __('Pending Review');
			break;
		case 'draft' :
			$stat = __('Draft');
			break;
		}

		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$time = '';
		} else {
			/* translators: date format in table columns, see https://secure.php.net/date */
			$time = mysql2date(__('Y/m/d'), $post->post_date);
		}

		$html .= '<tr class="' . trim( 'found-posts ' . $alt ) . '"><td class="found-radio"><input type="radio" id="found-'.$post->ID.'" name="found_post_id" value="' . esc_attr($post->ID) . '"></td>';
		$html .= '<td><label for="found-'.$post->ID.'">' . esc_html( $title ) . '</label></td><td class="no-break">' . esc_html( $post_types[$post->post_type]->labels->singular_name ) . '</td><td class="no-break">'.esc_html( $time ) . '</td><td class="no-break">' . esc_html( $stat ). ' </td></tr>' . "\n\n";
	}

	$html .= '</tbody></table>';

	wp_send_json_success( $html );
}

/**
 * Ajax handler for saving the widgets order.
 *
 * @since 3.1.0
 */
function wp_ajax_widgets_order() {
	check_ajax_referer( 'save-sidebar-widgets', 'savewidgets' );

	if ( !current_user_can('edit_theme_options') ) {
		wp_die( -1 );
	}

	$app = getApp();
	$_post = $app['request']->request;

	$_post->remove( 'savewidgets' );
	$_post->remove( 'action' );

	// Save widgets order for all sidebars.
	if ( is_array( $_post->get( 'sidebars' ) ) ) {
		$sidebars = [];
		foreach ( $_post->get( 'sidebars' ) as $key => $val ) {
			$sb = [];
			if ( !empty($val) ) {
				$val = explode(',', $val);
				foreach ( $val as $k => $v ) {
					if ( strpos($v, 'widget-') === false ) {
						continue;
					}

					$sb[$k] = substr($v, strpos($v, '_') + 1);
				}
			}
			$sidebars[$key] = $sb;
		}
		wp_set_sidebars_widgets($sidebars);
		wp_die( 1 );
	}

	wp_die( -1 );
}

/**
 * Ajax handler for saving a widget.
 *
 * @since 3.1.0
 */
function wp_ajax_save_widget() {
	check_ajax_referer( 'save-sidebar-widgets', 'savewidgets' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( !current_user_can('edit_theme_options') || ! $_post->get( 'id_base' ) ) {
		wp_die( -1 );
	}

	$_post->remove( 'savewidgets' );
	$_post->remove( 'action' );

	/**
	 * Fires early when editing the widgets displayed in sidebars.
	 *
	 * @since 2.8.0
	 */
	do_action( 'load-widgets.php' );

	/**
	 * Fires early when editing the widgets displayed in sidebars.
	 *
	 * @since 2.8.0
	 */
	do_action( 'widgets.php' );

	/** This action is documented in wp-admin/widgets.php */
	do_action( 'sidebar_admin_setup' );

	$id_base = $_post->get( 'id_base' );
	$widget_id = $_post->get( 'widget-id' );
	$sidebar_id = $_post->get( 'sidebar' );
	$multi_number = $_post->getInt( 'multi_number', 0 );
	$settings = $_post->get( 'widget-' . $id_base ) && is_array( $_post->get( 'widget-' . $id_base ) ) ?
		$_post->get( 'widget-' . $id_base ) :
		false;
	$error = '<p>' . __('An error has occurred. Please reload the page and try again.') . '</p>';

	$sidebars = wp_get_sidebars_widgets();
	$sidebar = isset($sidebars[$sidebar_id]) ? $sidebars[$sidebar_id] : [];

	// Delete.
	if ( $_post->get( 'delete_widget' ) ) {

		if ( ! isset( $app->widgets['registered'][ $widget_id ] ) ) {
			wp_die( $error );
		}

		$sidebar = array_diff( $sidebar, array($widget_id) );
		$_post->replace( [
			'sidebar' => $sidebar_id,
			'widget-' . $id_base => [],
			'the-widget-id' => $widget_id,
			'delete_widget' => '1'
		] );

		/** This action is documented in wp-admin/widgets.php */
		do_action( 'delete_widget', $widget_id, $sidebar_id, $id_base );

	} elseif ( $settings && preg_match( '/__i__|%i%/', key($settings) ) ) {
		if ( !$multi_number ) {
			wp_die( $error );
		}

		$_post->set( 'widget-' . $id_base, [ $multi_number => reset( $settings ) ] );
		$widget_id = $id_base . '-' . $multi_number;
		$sidebar[] = $widget_id;
	}
	$_post->set( 'widget-id', $sidebar );

	foreach ( (array) $app->widgets['updates'] as $name => $control ) {

		if ( $name == $id_base ) {
			if ( !is_callable( $control['callback'] ) ) {
				continue;
			}

			ob_start();
				call_user_func_array( $control['callback'], $control['params'] );
			ob_end_clean();
			break;
		}
	}

	if ( $_post->get( 'delete_widget' ) ) {
		$sidebars[$sidebar_id] = $sidebar;
		wp_set_sidebars_widgets($sidebars);
		echo "deleted:$widget_id";
		wp_die();
	}

	if ( $_post->get( 'add_new' ) ) {
		wp_die();
	}

	if ( $form = $app->widgets['controls'][$widget_id] ) {
		call_user_func_array( $form['callback'], $form['params'] );
	}

	wp_die();
}

/**
 * Ajax handler for saving a widget.
 *
 * @since 3.9.0
 */
function wp_ajax_update_widget() {
	$app = getApp();
	$app->get( 'customize' )->widgets->wp_ajax_update_widget();
}

/**
 * Ajax handler for removing inactive widgets.
 *
 * @since 4.4.0
 */
function wp_ajax_delete_inactive_widgets() {
	check_ajax_referer( 'remove-inactive-widgets', 'removeinactivewidgets' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( -1 );
	}

	$app = getApp();
	$_post = $app['request']->request;

	$_post->remove( 'removeinactivewidgets' );
	$_post->remove( 'action' );

	do_action( 'load-widgets.php' );
	do_action( 'widgets.php' );
	do_action( 'sidebar_admin_setup' );

	$sidebars_widgets = wp_get_sidebars_widgets();

	foreach ( $sidebars_widgets['wp_inactive_widgets'] as $key => $widget_id ) {
		$pieces = explode( '-', $widget_id );
		$multi_number = array_pop( $pieces );
		$id_base = implode( '-', $pieces );
		$widget = get_option( 'widget_' . $id_base );
		unset( $widget[$multi_number] );
		update_option( 'widget_' . $id_base, $widget );
		unset( $sidebars_widgets['wp_inactive_widgets'][$key] );
	}

	wp_set_sidebars_widgets( $sidebars_widgets );

	wp_die();
}

/**
 * Ajax handler for uploading attachments
 *
 * @since 3.3.0
 */
function wp_ajax_upload_attachment() {
	check_ajax_referer( 'media-form' );
	/*
	 * This function does not use wp_send_json_success() / wp_send_json_error()
	 * as the html4 Plupload handler requires a text/html content-type for older IE.
	 * See https://core.trac.wordpress.org/ticket/31037
	 */
	$app = getApp();
	$_files = $app['request']->files;
	$_request = $app['request']->attributes;
	$async_upload = $_files->get( 'async-upload' );

	if ( ! current_user_can( 'upload_files' ) ) {
		echo wp_json_encode( array(
			'success' => false,
			'data'    => array(
				'message'  => __( 'Sorry, you are not allowed to upload files.' ),
				'filename' => $async_upload['name'],
			)
		) );

		wp_die();
	}

	if ( $_request->has( 'post_id' ) ) {
		$post_id = $_request->getInt( 'post_id' );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			echo wp_json_encode( array(
				'success' => false,
				'data'    => array(
					'message'  => __( 'Sorry, you are not allowed to attach files to this post.' ),
					'filename' => $async_upload['name'],
				)
			) );

			wp_die();
		}
	} else {
		$post_id = null;
	}

	$post_data = $_request->has( 'post_data' ) ? $_request->get( 'post_data' ) : [];

	// If the context is custom header or background, make sure the uploaded file is an image.
	if ( isset( $post_data['context'] ) && in_array( $post_data['context'], array( 'custom-header', 'custom-background' ) ) ) {
		$wp_filetype = wp_check_filetype_and_ext( $async_upload['tmp_name'], $async_upload['name'] );
		if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) ) {
			echo wp_json_encode( array(
				'success' => false,
				'data'    => array(
					'message'  => __( 'The uploaded file is not a valid image. Please try again.' ),
					'filename' => $async_upload['name'],
				)
			) );

			wp_die();
		}
	}

	$attachment_id = media_handle_upload( 'async-upload', $post_id, $post_data );

	if ( is_wp_error( $attachment_id ) ) {
		echo wp_json_encode( array(
			'success' => false,
			'data'    => array(
				'message'  => $attachment_id->get_error_message(),
				'filename' => $async_upload['name'],
			)
		) );

		wp_die();
	}

	if ( isset( $post_data['context'] ) && isset( $post_data['theme'] ) ) {
		if ( 'custom-background' === $post_data['context'] ) {
			update_post_meta( $attachment_id, '_wp_attachment_is_custom_background', $post_data['theme'] );
		}

		if ( 'custom-header' === $post_data['context'] ) {
			update_post_meta( $attachment_id, '_wp_attachment_is_custom_header', $post_data['theme'] );
		}
	}

	if ( ! $attachment = wp_prepare_attachment_for_js( $attachment_id ) ) {
			wp_die();
	}

	echo wp_json_encode( array(
		'success' => true,
		'data'    => $attachment,
	) );

	wp_die();
}

/**
 * Ajax handler for image editing.
 *
 * @since 3.1.0
 */
function wp_ajax_image_editor() {
	$app = getApp();
	$_post = $app['request']->request;

	$attachment_id = $_post->getInt( 'postid' );
	if ( ! $attachment_id || !current_user_can('edit_post', $attachment_id) ) {
		wp_die( -1 );
	}

	check_ajax_referer( "image_editor-$attachment_id" );
	include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );

	$msg = false;
	switch ( $_post->get( 'do' ) ) {
	case 'save' :
		$msg = wp_save_image($attachment_id);
		$msg = wp_json_encode($msg);
		wp_die( $msg );
		break;
	case 'scale' :
		$msg = wp_save_image($attachment_id);
		break;
	case 'restore' :
		$msg = wp_restore_image($attachment_id);
		break;
	}

	wp_image_editor($attachment_id, $msg);
	wp_die();
}

/**
 * Ajax handler for setting the featured image.
 *
 * @since 3.1.0
 */
function wp_ajax_set_post_thumbnail() {
	$app = getApp();
	$_request = $app['request']->attributes;
	$_post = $app['request']->request;

	// New-style request
	$json = ! empty( $_request->get( 'json' ) );

	$post_ID = $_post->getInt( 'post_id' );
	if ( ! current_user_can( 'edit_post', $post_ID ) ) {
		wp_die( -1 );
	}

	$thumbnail_id = $_post->getInt( 'thumbnail_id' );

	if ( $json ) {
		check_ajax_referer( "update-post_$post_ID" );
	} else {
		check_ajax_referer( "set_post_thumbnail-$post_ID" );
	}

	if ( $thumbnail_id == '-1' ) {
		if ( delete_post_thumbnail( $post_ID ) ) {
			$return = _wp_post_thumbnail_html( null, $post_ID );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		} else {
			wp_die( 0 );
		}
	}

	if ( set_post_thumbnail( $post_ID, $thumbnail_id ) ) {
		$return = _wp_post_thumbnail_html( $thumbnail_id, $post_ID );
		$json ? wp_send_json_success( $return ) : wp_die( $return );
	}

	wp_die( 0 );
}

/**
 * Ajax handler for retrieving HTML for the featured image.
 *
 * @since 4.6.0
 */
function wp_ajax_get_post_thumbnail_html() {
	$app = getApp();
	$_post = $app['request']->request;

	$post_ID = $_post->getInt( 'post_id' );

	check_ajax_referer( "update-post_$post_ID" );

	if ( ! current_user_can( 'edit_post', $post_ID ) ) {
		wp_die( -1 );
	}

	$thumbnail_id = $_post->getInt( 'thumbnail_id' );

	// For backward compatibility, -1 refers to no featured image.
	if ( -1 === $thumbnail_id ) {
		$thumbnail_id = null;
	}

	$return = _wp_post_thumbnail_html( $thumbnail_id, $post_ID );
	wp_send_json_success( $return );
}

/**
 * Ajax handler for setting the featured image for an attachment.
 *
 * @since 4.0.0
 *
 * @see set_post_thumbnail()
 */
function wp_ajax_set_attachment_thumbnail() {
	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'urls' ) ) || ! is_array( $_post->get( 'urls' ) ) ) {
		wp_send_json_error();
	}

	$thumbnail_id = $_post->getInt( 'thumbnail_id' );
	if ( empty( $thumbnail_id ) ) {
		wp_send_json_error();
	}

	$post_ids = [];
	// For each URL, try to find its corresponding post ID.
	foreach ( $_post->get( 'urls' ) as $url ) {
		$post_id = attachment_url_to_postid( $url );
		if ( ! empty( $post_id ) ) {
			$post_ids[] = $post_id;
		}
	}

	if ( empty( $post_ids ) ) {
		wp_send_json_error();
	}

	$success = 0;
	// For each found attachment, set its thumbnail.
	foreach ( $post_ids as $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			continue;
		}

		if ( set_post_thumbnail( $post_id, $thumbnail_id ) ) {
			$success++;
		}
	}

	if ( 0 === $success ) {
		wp_send_json_error();
	} else {
		wp_send_json_success();
	}

	wp_send_json_error();
}

/**
 * Ajax handler for date formatting.
 *
 * @since 3.1.0
 */
function wp_ajax_date_format() {
	$app = getApp();
	$_post = $app['request']->request;

	wp_die( date_i18n( sanitize_option( 'date_format', wp_unslash( $_post->get( 'date' ) ) ) ) );
}

/**
 * Ajax handler for time formatting.
 *
 * @since 3.1.0
 */
function wp_ajax_time_format() {
	$app = getApp();
	$_post = $app['request']->request;

	wp_die( date_i18n( sanitize_option( 'time_format', wp_unslash( $_post->get( 'date' ) ) ) ) );
}

/**
 * Ajax handler for saving posts from the fullscreen editor.
 *
 * @since 3.1.0
 * @deprecated 4.3.0
 */
function wp_ajax_wp_fullscreen_save_post() {
	$app = getApp();
	$_post = $app['request']->request;

	$post_id = $_post->getInt( 'post_ID', 0 );

	$post = null;

	if ( $post_id ) {
		$post = get_post( $post_id );
	}

	check_ajax_referer('update-post_' . $post_id, '_wpnonce');

	$post_id = edit_post();

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error();
	}

	if ( $post ) {
		$last_date = mysql2date( __( 'F j, Y' ), $post->post_modified );
		$last_time = mysql2date( __( 'g:i a' ), $post->post_modified );
	} else {
		$last_date = date_i18n( __( 'F j, Y' ) );
		$last_time = date_i18n( __( 'g:i a' ) );
	}

	if ( $last_id = get_post_meta( $post_id, '_edit_last', true ) ) {
		$last_user = get_userdata( $last_id );
		$last_edited = sprintf( __('Last edited by %1$s on %2$s at %3$s'), esc_html( $last_user->display_name ), $last_date, $last_time );
	} else {
		$last_edited = sprintf( __('Last edited on %1$s at %2$s'), $last_date, $last_time );
	}

	wp_send_json_success( array( 'last_edited' => $last_edited ) );
}

/**
 * Ajax handler for removing a post lock.
 *
 * @since 3.1.0
 */
function wp_ajax_wp_remove_post_lock() {
	$app = getApp();
	$_post = $app['request']->request;

	$post_id = $_post->getInt( 'post_ID' );
	if ( ! $post_id || empty( $_post->get( 'active_post_lock' ) ) ) {
		wp_die( 0 );
	}

	if ( ! get_post( $post_id ) ) {
		wp_die( 0 );
	}

	check_ajax_referer( 'update-post_' . $post_id );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( -1 );
	}

	$active_lock = array_map( 'absint', explode( ':', $_post->get( 'active_post_lock' ) ) );
	if ( $active_lock[1] != get_current_user_id() ) {
		wp_die( 0 );
	}

	/**
	 * Filters the post lock window duration.
	 *
	 * @since 3.3.0
	 *
	 * @param int $interval The interval in seconds the post lock duration
	 *                      should last, plus 5 seconds. Default 150.
	 */
	$new_lock = ( time() - apply_filters( 'wp_check_post_lock_window', 150 ) + 5 ) . ':' . $active_lock[1];
	update_post_meta( $post_id, '_edit_lock', $new_lock, implode( ':', $active_lock ) );
	wp_die( 1 );
}

/**
 * Ajax handler for dismissing a WordPress pointer.
 *
 * @since 3.1.0
 */
function wp_ajax_dismiss_wp_pointer() {
	$app = getApp();
	$_post = $app['request']->request;

	$pointer = $_post->get( 'pointer' );
	if ( $pointer != sanitize_key( $pointer ) ) {
		wp_die( 0 );
	}

//	check_ajax_referer( 'dismiss-pointer_' . $pointer );

	$dismissed = array_filter( explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) );

	if ( in_array( $pointer, $dismissed ) ) {
		wp_die( 0 );
	}

	$dismissed[] = $pointer;
	$dismissed = implode( ',', $dismissed );

	update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed );
	wp_die( 1 );
}

/**
 * Ajax handler for getting an attachment.
 *
 * @since 3.5.0
 */
function wp_ajax_get_attachment() {
	$app = getApp();
	$_request = $app['request']->attributes;
	if ( ! $_request->has( 'id' ) ) {
		wp_send_json_error();
	}

	if ( ! $id = $_request->getInt( 'id' ) ) {
		wp_send_json_error();
	}

	if ( ! $post = get_post( $id ) ) {
		wp_send_json_error();
	}

	if ( 'attachment' != $post->post_type ) {
		wp_send_json_error();
	}

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error();
	}

	if ( ! $attachment = wp_prepare_attachment_for_js( $id ) ) {
		wp_send_json_error();
	}

	wp_send_json_success( $attachment );
}

/**
 * Ajax handler for querying attachments.
 *
 * @since 3.5.0
 */
function wp_ajax_query_attachments() {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error();
	}

	$app = getApp();
	$_request = $app['request']->attributes;
	$_query = $_request->has( 'query' ) ? (array) $_request->get( 'query' ) : [];
	$keys = array(
		's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
		'post_parent', 'post__in', 'post__not_in', 'year', 'monthnum'
	);
	foreach ( get_taxonomies_for_attachments( 'objects' ) as $t ) {
		if ( $t->query_var && isset( $_query[ $t->query_var ] ) ) {
			$keys[] = $t->query_var;
		}
	}

	$query = array_intersect_key( $_query, array_flip( $keys ) );
	$query['post_type'] = 'attachment';
	if ( MEDIA_TRASH
		&& ! empty( $_query['post_status'] )
		&& 'trash' === $_query['post_status'] ) {
		$query['post_status'] = 'trash';
	} else {
		$query['post_status'] = 'inherit';
	}

	if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) ) {
		$query['post_status'] .= ',private';
	}

	// Filter query clauses to include filenames.
	if ( isset( $query['s'] ) ) {
		add_filter( 'posts_clauses', '_filter_query_attachment_filenames' );
	}

	/**
	 * Filters the arguments passed to WP_Query during an Ajax
	 * call for querying attachments.
	 *
	 * @since 3.7.0
	 *
	 * @see WP_Query::parse_query()
	 *
	 * @param array $query An array of query variables.
	 */
	$query = apply_filters( 'ajax_query_attachments_args', $query );
	$query = new WP_Query( $query );

	$posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
	$posts = array_filter( $posts );

	wp_send_json_success( $posts );
}

/**
 * Ajax handler for updating attachment attributes.
 *
 * @since 3.5.0
 */
function wp_ajax_save_attachment() {
	$app = getApp();
	$_request = $app['request']->attributes;
	if ( ! $_request->has( 'id' ) || ! $_request->has( 'changes' ) ) {
		wp_send_json_error();
	}

	if ( ! $id = $_request->getInt( 'id' ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'update-post_' . $id, 'nonce' );

	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error();
	}

	$changes = $_request->get( 'changes' );
	$post    = get_post( $id, ARRAY_A );

	if ( 'attachment' != $post['post_type'] ) {
		wp_send_json_error();
	}

	if ( isset( $changes['parent'] ) ) {
		$post['post_parent'] = $changes['parent'];
	}

	if ( isset( $changes['title'] ) ) {
		$post['post_title'] = $changes['title'];
	}

	if ( isset( $changes['caption'] ) ) {
		$post['post_excerpt'] = $changes['caption'];
	}

	if ( isset( $changes['description'] ) ) {
		$post['post_content'] = $changes['description'];
	}

	if ( MEDIA_TRASH && isset( $changes['status'] ) ) {
		$post['post_status'] = $changes['status'];
	}

	if ( isset( $changes['alt'] ) ) {
		$alt = wp_unslash( $changes['alt'] );
		if ( $alt != get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
			$alt = wp_strip_all_tags( $alt, true );
			update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $alt ) );
		}
	}

	if ( wp_attachment_is( 'audio', $post['ID'] ) ) {
		$changed = false;
		$id3data = wp_get_attachment_metadata( $post['ID'] );
		if ( ! is_array( $id3data ) ) {
			$changed = true;
			$id3data = [];
		}
		foreach ( wp_get_attachment_id3_keys( (object) $post, 'edit' ) as $key => $label ) {
			if ( isset( $changes[ $key ] ) ) {
				$changed = true;
				$id3data[ $key ] = sanitize_text_field( wp_unslash( $changes[ $key ] ) );
			}
		}

		if ( $changed ) {
			wp_update_attachment_metadata( $id, $id3data );
		}
	}

	if ( MEDIA_TRASH && isset( $changes['status'] ) && 'trash' === $changes['status'] ) {
		wp_delete_post( $id );
	} else {
		wp_update_post( $post );
	}

	wp_send_json_success();
}

/**
 * Ajax handler for saving backward compatible attachment attributes.
 *
 * @since 3.5.0
 */
function wp_ajax_save_attachment_compat() {
	$app = getApp();
	$_request = $app['request']->attributes;
	if ( ! $_request->has( 'id' ) ) {
		wp_send_json_error();
	}

	if ( ! $id = $_request->getInt( 'id' ) ) {
		wp_send_json_error();
	}

	$attachments = $_request->get( 'attachments' );
	if ( empty( $attachments ) || empty( $attachments[ $id ] ) ) {
		wp_send_json_error();
	}
	$attachment_data = $attachments[ $id ];

	check_ajax_referer( 'update-post_' . $id, 'nonce' );

	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error();
	}

	$post = get_post( $id, ARRAY_A );

	if ( 'attachment' != $post['post_type'] ) {
		wp_send_json_error();
	}

	/** This filter is documented in wp-admin/includes/media.php */
	$post = apply_filters( 'attachment_fields_to_save', $post, $attachment_data );

	if ( isset( $post['errors'] ) ) {
		unset( $post['errors'] );
	}

	wp_update_post( $post );

	foreach ( get_attachment_taxonomies( $post ) as $taxonomy ) {
		if ( isset( $attachment_data[ $taxonomy ] ) ) {
			wp_set_object_terms( $id, array_map( 'trim', preg_split( '/,+/', $attachment_data[ $taxonomy ] ) ), $taxonomy, false );
		}
	}

	if ( ! $attachment = wp_prepare_attachment_for_js( $id ) ) {
		wp_send_json_error();
	}

	wp_send_json_success( $attachment );
}

/**
 * Ajax handler for saving the attachment order.
 *
 * @since 3.5.0
 */
function wp_ajax_save_attachment_order() {
	$app = getApp();
	$_request = $app['request']->attributes;
	if ( ! $_request->has( 'post_id' ) ) {
		wp_send_json_error();
	}

	if ( ! $post_id = $_request->getInt( 'post_id' ) ) {
		wp_send_json_error();
	}

	$attachments = $_request->get( 'attachments' );
	if ( empty( $attachments ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'update-post_' . $post_id, 'nonce' );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error();
	}

	foreach ( $attachments as $attachment_id => $menu_order ) {
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			continue;
		}
		if ( ! $attachment = get_post( $attachment_id ) ) {
			continue;
		}
		if ( 'attachment' != $attachment->post_type ) {
			continue;
		}

		wp_update_post( array( 'ID' => $attachment_id, 'menu_order' => $menu_order ) );
	}

	wp_send_json_success();
}

/**
 * Ajax handler for sending an attachment to the editor.
 *
 * Generates the HTML to send an attachment to the editor.
 * Backward compatible with the {@see 'media_send_to_editor'} filter
 * and the chain of filters that follow.
 *
 * @since 3.5.0
 */
function wp_ajax_send_attachment_to_editor() {
	check_ajax_referer( 'media-send-to-editor', 'nonce' );

	$app = getApp();
	$_post = $app['request']->request;

	$attachment = wp_unslash( $_post->get( 'attachment' ) );

	$id = intval( $attachment['id'] );

	if ( ! $post = get_post( $id ) ) {
		wp_send_json_error();
	}

	if ( 'attachment' != $post->post_type ) {
		wp_send_json_error();
	}

	if ( current_user_can( 'edit_post', $id ) ) {
		// If this attachment is unattached, attach it. Primarily a back compat thing.
		if ( 0 == $post->post_parent && $insert_into_post_id = $_post->getInt( 'post_id' ) ) {
			wp_update_post( array( 'ID' => $id, 'post_parent' => $insert_into_post_id ) );
		}
	}

	$url = empty( $attachment['url'] ) ? '' : $attachment['url'];
	$rel = ( strpos( $url, 'attachment_id') || get_attachment_link( $id ) == $url );

	remove_filter( 'media_send_to_editor', 'image_media_send_to_editor' );

	if ( 'image' === substr( $post->post_mime_type, 0, 5 ) ) {
		$align = isset( $attachment['align'] ) ? $attachment['align'] : 'none';
		$size = isset( $attachment['image-size'] ) ? $attachment['image-size'] : 'medium';
		$alt = isset( $attachment['image_alt'] ) ? $attachment['image_alt'] : '';

		// No whitespace-only captions.
		$caption = isset( $attachment['post_excerpt'] ) ? $attachment['post_excerpt'] : '';
		if ( '' === trim( $caption ) ) {
			$caption = '';
		}

		// We no longer insert title tags into <img> tags, as they are redundant.
		$title = '';
		$html = get_image_send_to_editor( $id, $caption, $title, $align, $url, $rel, $size, $alt );
	} elseif ( wp_attachment_is( 'video', $post ) || wp_attachment_is( 'audio', $post )  ) {
		$html = stripslashes_deep( $_post->get( 'html' ) );
	} else {
		$html = isset( $attachment['post_title'] ) ? $attachment['post_title'] : '';
		// Hard-coded string, $id is already sanitized
		$rel = $rel ? ' rel="attachment wp-att-' . $id . '"' : '';

		if ( ! empty( $url ) ) {
			$html = '<a href="' . esc_url( $url ) . '"' . $rel . '>' . $html . '</a>';
		}
	}

	/** This filter is documented in wp-admin/includes/media.php */
	$html = apply_filters( 'media_send_to_editor', $html, $id, $attachment );

	wp_send_json_success( $html );
}

/**
 * Ajax handler for sending a link to the editor.
 *
 * Generates the HTML to send a non-image embed link to the editor.
 *
 * Backward compatible with the following filters:
 * - file_send_to_editor_url
 * - audio_send_to_editor_url
 * - video_send_to_editor_url
 *
 * @since 3.5.0
 *
 * @global WP_Post  $post
 * @global WP_Embed $wp_embed
 */
function wp_ajax_send_link_to_editor() {
	$post = $GLOBALS['post']; //NOSONAR
	$wp_embed = $GLOBALS['wp_embed']; //NOSONAR

	check_ajax_referer( 'media-send-to-editor', 'nonce' );

	$app = getApp();
	$_post = $app['request']->request;
	if ( ! $src = wp_unslash( $_post->get( 'src' ) ) ) {
		wp_send_json_error();
	}

	if ( ! strpos( $src, '://' ) ) {
		$src = 'http://' . $src;
	}

	if ( ! $src = esc_url_raw( $src ) ) {
		wp_send_json_error();
	}

	if ( ! $link_text = trim( wp_unslash( $_post->get( 'link_text' ) ) ) ) {
		$link_text = wp_basename( $src );
	}

	$post = get_post( $_post->getInt( 'post_id', 0 ) );

	// Ping WordPress for an embed.
	$check_embed = $wp_embed->run_shortcode( '[embed]'. $src .'[/embed]' );

	// Fallback that WordPress creates when no oEmbed was found.
	$fallback = $wp_embed->maybe_make_link( $src );

	if ( $check_embed !== $fallback ) {
		// TinyMCE view for [embed] will parse this
		$html = '[embed]' . $src . '[/embed]';
	} elseif ( $link_text ) {
		$html = '<a href="' . esc_url( $src ) . '">' . $link_text . '</a>';
	} else {
		$html = '';
	}

	// Figure out what filter to run:
	$type = 'file';
	if ( ( $ext = preg_replace( '/^.+?\.([^.]+)$/', '$1', $src ) ) && ( $ext_type = wp_ext2type( $ext ) )
		&& ( 'audio' == $ext_type || 'video' == $ext_type ) ) {
		$type = $ext_type;
	}

	/** This filter is documented in wp-admin/includes/media.php */
	$html = apply_filters( $type . '_send_to_editor_url', $html, $src, $link_text );

	wp_send_json_success( $html );
}

/**
 * Ajax handler for the Heartbeat API.
 *
 * Runs when the user is logged in.
 *
 * @since 3.6.0
 */
function wp_ajax_heartbeat() {
	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( '_nonce' ) ) ) {
		wp_send_json_error();
	}

	$response = $data = [];
	$nonce_state = wp_verify_nonce( $_post->get( '_nonce' ), 'heartbeat-nonce' );

	// screen_id is the same as $current_screen->id and the JS global 'pagenow'.
	if ( ! empty( $_post->get( 'screen_id' ) ) ) {
		$screen_id = sanitize_key( $_post->get( 'screen_id' ) );
	} else {
		$screen_id = 'front';
	}

	if ( ! empty( $_post->get( 'data' ) ) ) {
		$data = wp_unslash( (array) $_post->get( 'data' ) );
	}

	if ( 1 !== $nonce_state ) {
		$response = apply_filters( 'wp_refresh_nonces', $response, $data, $screen_id );

		if ( false === $nonce_state ) {
			// User is logged in but nonces have expired.
			$response['nonces_expired'] = true;
			wp_send_json( $response );
		}
	}

	if ( ! empty( $data ) ) {
		/**
		 * Filters the Heartbeat response received.
		 *
		 * @since 3.6.0
		 *
		 * @param array  $response  The Heartbeat response.
		 * @param array  $data      The $_POST data sent.
		 * @param string $screen_id The screen id.
		 */
		$response = apply_filters( 'heartbeat_received', $response, $data, $screen_id );
	}

	/**
	 * Filters the Heartbeat response sent.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $response  The Heartbeat response.
	 * @param string $screen_id The screen id.
	 */
	$response = apply_filters( 'heartbeat_send', $response, $screen_id );

	/**
	 * Fires when Heartbeat ticks in logged-in environments.
	 *
	 * Allows the transport to be easily replaced with long-polling.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $response  The Heartbeat response.
	 * @param string $screen_id The screen id.
	 */
	do_action( 'heartbeat_tick', $response, $screen_id );

	// Send the current time according to the server
	$response['server_time'] = time();

	wp_send_json( $response );
}

/**
 * Ajax handler for getting revision diffs.
 *
 * @since 3.6.0
 */
function wp_ajax_get_revision_diffs() {
	require ABSPATH . 'wp-admin/includes/revision.php';

	$app = getApp();
	$_request = $app['request']->attributes;
	if ( ! $post = get_post( (int) $_request->getInt( 'post_id' ) ) ) {
		wp_send_json_error();
	}

	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_send_json_error();
	}

	// Really just pre-loading the cache here.
	if ( ! wp_get_post_revisions( $post->ID, array( 'check_enabled' => false ) ) ) {
		wp_send_json_error();
	}

	$return = [];
	@set_time_limit( 0 );

	foreach ( $_request->get( 'compare' ) as $compare_key ) {
		list( $compare_from, $compare_to ) = explode( ':', $compare_key ); // from:to

		$return[] = array(
			'id' => $compare_key,
			'fields' => wp_get_revision_ui_diff( $post, $compare_from, $compare_to ),
		);
	}
	wp_send_json_success( $return );
}

/**
 * Ajax handler for auto-saving the selected color scheme for
 * a user's own profile.
 *
 * @since 3.8.0
 */
function wp_ajax_save_user_color_scheme() {
	check_ajax_referer( 'save-color-scheme', 'nonce' );

	$app = getApp();
	$_post = $app['request']->request;

	$color_scheme = sanitize_key( $_post->get( 'color_scheme' ) );

	if ( ! isset( $app->_wp_admin_css_colors[ $color_scheme ] ) ) {
		wp_send_json_error();
	}

	$previous_color_scheme = get_user_meta( get_current_user_id(), 'admin_color', true );
	update_user_meta( get_current_user_id(), 'admin_color', $color_scheme );

	wp_send_json_success( array(
		'previousScheme' => 'admin-color-' . $previous_color_scheme,
		'currentScheme'  => 'admin-color-' . $color_scheme
	) );
}

/**
 * Ajax handler for getting themes from themes_api().
 *
 * @since 3.9.0
 */
function wp_ajax_query_themes() {
	if ( ! current_user_can( 'install_themes' ) ) {
		wp_send_json_error();
	}

	$app = getApp();
	$_request = $app['request']->attributes;
	$args = wp_parse_args( wp_unslash( $_request->get( 'request' ) ), array(
		'per_page' => 20,
		'fields'   => $app->theme['field_defaults']
	) );

	if ( isset( $args['browse'] ) && 'favorites' === $args['browse'] && ! isset( $args['user'] ) ) {
		$user = get_user_option( 'wporg_favorites' );
		if ( $user ) {
			$args['user'] = $user;
		}
	}

	$old_filter = isset( $args['browse'] ) ? $args['browse'] : 'search';

	/** This filter is documented in wp-admin/includes/class-wp-theme-install-list-table.php */
	$args = apply_filters( 'install_themes_table_api_args_' . $old_filter, $args );

	$api = themes_api( 'query_themes', $args );

	if ( is_wp_error( $api ) ) {
		wp_send_json_error();
	}

	$update_php = network_admin_url( 'update.php?action=install-theme' );
	foreach ( $api->themes as &$theme ) {
		$theme->install_url = add_query_arg( array(
			'theme'    => $theme->slug,
			'_wpnonce' => wp_create_nonce( 'install-theme_' . $theme->slug )
		), $update_php );

		if ( current_user_can( 'switch_themes' ) ) {
			if ( is_multisite() ) {
				$theme->activate_url = add_query_arg( array(
					'action'   => 'enable',
					'_wpnonce' => wp_create_nonce( 'enable-theme_' . $theme->slug ),
					'theme'    => $theme->slug,
				), network_admin_url( 'themes.php' ) );
			} else {
				$theme->activate_url = add_query_arg( array(
					'action'     => 'activate',
					'_wpnonce'   => wp_create_nonce( 'switch-theme_' . $theme->slug ),
					'stylesheet' => $theme->slug,
				), admin_url( 'themes.php' ) );
			}
		}

		if ( ! is_multisite() && current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
			$theme->customize_url = add_query_arg( array(
				'return' => urlencode( network_admin_url( 'theme-install.php', 'relative' ) ),
			), wp_customize_url( $theme->slug ) );
		}

		$theme->name        = wp_kses( $theme->name, $app->theme['allowedtags'] );
		$theme->author      = wp_kses( $theme->author, $app->theme['allowedtags'] );
		$theme->version     = wp_kses( $theme->version, $app->theme['allowedtags'] );
		$theme->description = wp_kses( $theme->description, $app->theme['allowedtags'] );
		$theme->stars       = wp_star_rating( array( 'rating' => $theme->rating, 'type' => 'percent', 'number' => $theme->num_ratings, 'echo' => false ) );
		$theme->num_ratings = number_format_i18n( $theme->num_ratings );
		$theme->preview_url = set_url_scheme( $theme->preview_url );
	}

	wp_send_json_success( $api );
}

/**
 * Apply [embed] Ajax handlers to a string.
 *
 * @since 4.0.0
 *
 * @global WP_Post    $post       Global $post.
 * @global WP_Embed   $wp_embed   Embed API instance.
 */
function wp_ajax_parse_embed() {
	$post = $GLOBALS['post']; //NOSONAR
	$wp_embed = $GLOBALS['wp_embed']; //NOSONAR

	$app = getApp();
	$_post = $app['request']->request;

	if ( ! $post = get_post( $_post->getInt( 'post_ID' ) ) ) {
		wp_send_json_error();
	}

	if ( empty( $_post->get( 'shortcode' ) ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_send_json_error();
	}

	$shortcode = wp_unslash( $_post->get( 'shortcode' ) );

	preg_match( '/' . get_shortcode_regex() . '/s', $shortcode, $matches );
	$atts = shortcode_parse_atts( $matches[3] );
	if ( ! empty( $matches[5] ) ) {
		$url = $matches[5];
	} elseif ( ! empty( $atts['src'] ) ) {
		$url = $atts['src'];
	} else {
		$url = '';
	}

	$parsed = false;
	setup_postdata( $post );

	$wp_embed->return_false_on_fail = true;

	if ( is_ssl() && 0 === strpos( $url, 'http://' ) ) {
		// Admin is ssl and the user pasted non-ssl URL.
		// Check if the provider supports ssl embeds and use that for the preview.
		$ssl_shortcode = preg_replace( '%^(\\[embed[^\\]]*\\])http://%i', '$1https://', $shortcode );
		$parsed = $wp_embed->run_shortcode( $ssl_shortcode );

		if ( ! $parsed ) {
			$no_ssl_support = true;
		}
	}

	if ( $url && ! $parsed ) {
		$parsed = $wp_embed->run_shortcode( $shortcode );
	}

	if ( ! $parsed ) {
		wp_send_json_error( array(
			'type' => 'not-embeddable',
			'message' => sprintf( __( '%s failed to embed.' ), '<code>' . esc_html( $url ) . '</code>' ),
		) );
	}

	if ( has_shortcode( $parsed, 'audio' ) || has_shortcode( $parsed, 'video' ) ) {
		$styles = '';
		$mce_styles = wpview_media_sandbox_styles();
		foreach ( $mce_styles as $style ) {
			$styles .= sprintf( '<link rel="stylesheet" href="%s"/>', $style );
		}

		$html = do_shortcode( $parsed );

		$app = getApp();
		$app['scripts.global']->done = [];
		ob_start();
		wp_print_scripts( 'wp-mediaelement' );
		$scripts = ob_get_clean();

		$parsed = $styles . $html . $scripts;
	}


	if ( ! empty( $no_ssl_support ) || ( is_ssl() && ( preg_match( '%<(iframe|script|embed) [^>]*src="http://%', $parsed ) ||
		preg_match( '%<link [^>]*href="http://%', $parsed ) ) ) ) {
		// Admin is ssl and the embed is not. Iframes, scripts, and other "active content" will be blocked.
		wp_send_json_error( array(
			'type' => 'not-ssl',
			'message' => __( 'This preview is unavailable in the editor.' ),
		) );
	}

	wp_send_json_success( array(
		'body' => $parsed,
		'attr' => $wp_embed->last_attr
	) );
}

/**
 * @since 4.0.0
 *
 * @global WP_Post    $post
 */
function wp_ajax_parse_media_shortcode() {
	$post = $GLOBALS['post']; //NOSONAR

	$app = getApp();
	$_post = $app['request']->request;
	$_request = $app['request']->attributes;

	if ( empty( $_post->get( 'shortcode' ) ) ) {
		wp_send_json_error();
	}

	$shortcode = wp_unslash( $_post->get( 'shortcode' ) );

	if ( ! empty( $_post->get( 'post_ID' ) ) ) {
		$post = get_post( $_post->getInt( 'post_ID' ) );
	}

	// the embed shortcode requires a post
	if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
		if ( 'embed' === $shortcode ) {
			wp_send_json_error();
		}
	} else {
		setup_postdata( $post );
	}

	$parsed = do_shortcode( $shortcode  );

	if ( empty( $parsed ) ) {
		wp_send_json_error( array(
			'type' => 'no-items',
			'message' => __( 'No items found.' ),
		) );
	}

	$head = '';
	$styles = wpview_media_sandbox_styles();

	foreach ( $styles as $style ) {
		$head .= '<link type="text/css" rel="stylesheet" href="' . $style . '">';
	}

	$app['scripts.global']->done = [];

	ob_start();

	echo $parsed;

	if ( 'playlist' === $_request->get( 'type' ) ) {
		wp_underscore_playlist_templates();

		wp_print_scripts( 'wp-playlist' );
	} else {
		wp_print_scripts( array( 'froogaloop', 'wp-mediaelement' ) );
	}

	wp_send_json_success( array(
		'head' => $head,
		'body' => ob_get_clean()
	) );
}

/**
 * Ajax handler for destroying multiple open sessions for a user.
 *
 * @since 4.1.0
 */
function wp_ajax_destroy_sessions() {
	$app = getApp();
	$_post = $app['request']->request;

	$user = get_userdata( $_post->getInt( 'user_id' ) );
	if ( $user &&
		(
			! current_user_can( 'edit_user', $user->ID ) ||
			! wp_verify_nonce( $_post->get( 'nonce'), 'update-user_' . $user->ID )
		)
	) {
		wp_send_json_error( array(
			'message' => __( 'Could not log out user sessions. Please try again.' ),
		) );
	}

	$sessions = WP_Session_Tokens::get_instance( $user->ID );

	if ( $user->ID === get_current_user_id() ) {
		$sessions->destroy_others( wp_get_session_token() );
		$message = __( 'You are now logged out everywhere else.' );
	} else {
		$sessions->destroy_all();
		/* translators: 1: User's display name. */
		$message = sprintf( __( '%s has been logged out.' ), $user->display_name );
	}

	wp_send_json_success( [ 'message' => $message ] );
}

/**
 * Ajax handler for saving a post from Press This.
 *
 * @since 4.2.0
 */
function wp_ajax_press_this_save_post() {
	$wp_press_this = new WP_Press_This();
	$wp_press_this->save_post();
}

/**
 * Ajax handler for creating new category from Press This.
 *
 * @since 4.2.0
 */
function wp_ajax_press_this_add_category() {
	$wp_press_this = new WP_Press_This();
	$wp_press_this->add_category();
}

/**
 * Ajax handler for cropping an image.
 *
 * @since 4.3.0
 */
function wp_ajax_crop_image() {
	$app = getApp();
	$_post = $app['request']->request;

	$attachment_id = $_post->getInt( 'id' );

	check_ajax_referer( 'image_editor-' . $attachment_id, 'nonce' );
	if ( ! current_user_can( 'customize' ) ) {
		wp_send_json_error();
	}

	$context = str_replace( '_', '-', $_post->get( 'context' ) );
	$data    = array_map( 'absint', $_post->get( 'cropDetails' ) );
	$cropped = wp_crop_image( $attachment_id, $data['x1'], $data['y1'], $data['width'], $data['height'], $data['dst_width'], $data['dst_height'] );

	if ( ! $cropped || is_wp_error( $cropped ) ) {
		wp_send_json_error( array( 'message' => __( 'Image could not be processed.' ) ) );
	}

	switch ( $context ) {
	case 'site-icon':
		$wp_site_icon = new WP_Site_Icon();

		// Skip creating a new attachment if the attachment is a Site Icon.
		if ( get_post_meta( $attachment_id, '_wp_attachment_context', true ) == $context ) {

			// Delete the temporary cropped file, we don't need it.
			wp_delete_file( $cropped );

			// Additional sizes in wp_prepare_attachment_for_js().
			add_filter( 'image_size_names_choose', array( $wp_site_icon, 'additional_sizes' ) );
			break;
		}

		/** This filter is documented in wp-admin/custom-header.php */
		// For replication.
		$cropped = apply_filters( 'wp_create_file_in_uploads', $cropped, $attachment_id );
		$object  = $wp_site_icon->create_attachment_object( $cropped, $attachment_id );
		unset( $object['ID'] );

		// Update the attachment.
		add_filter( 'intermediate_image_sizes_advanced', array( $wp_site_icon, 'additional_sizes' ) );
		$attachment_id = $wp_site_icon->insert_attachment( $object, $cropped );
		remove_filter( 'intermediate_image_sizes_advanced', array( $wp_site_icon, 'additional_sizes' ) );

		// Additional sizes in wp_prepare_attachment_for_js().
		add_filter( 'image_size_names_choose', array( $wp_site_icon, 'additional_sizes' ) );
		break;

	default:

		/**
		 * Fires before a cropped image is saved.
		 *
		 * Allows to add filters to modify the way a cropped image is saved.
		 *
		 * @since 4.3.0
		 *
		 * @param string $context       The Customizer control requesting the cropped image.
		 * @param int    $attachment_id The attachment ID of the original image.
		 * @param string $cropped       Path to the cropped image file.
		 */
		do_action( 'wp_ajax_crop_image_pre_save', $context, $attachment_id, $cropped );

		/** This filter is documented in wp-admin/custom-header.php */
		// For replication.
		$cropped = apply_filters( 'wp_create_file_in_uploads', $cropped, $attachment_id );

		$parent_url = wp_get_attachment_url( $attachment_id );
		$url        = str_replace( basename( $parent_url ), basename( $cropped ), $parent_url );

		$size       = @getimagesize( $cropped );
		$image_type = ( $size ) ? $size['mime'] : 'image/jpeg';

		$object = array(
			'post_title'     => basename( $cropped ),
			'post_content'   => $url,
			'post_mime_type' => $image_type,
			'guid'           => $url,
			'context'        => $context,
		);

		$attachment_id = wp_insert_attachment( $object, $cropped );
		$metadata = wp_generate_attachment_metadata( $attachment_id, $cropped );

		/**
		 * Filters the cropped image attachment metadata.
		 *
		 * @since 4.3.0
		 *
		 * @see wp_generate_attachment_metadata()
		 *
		 * @param array $metadata Attachment metadata.
		 */
		$metadata = apply_filters( 'wp_ajax_cropped_attachment_metadata', $metadata );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		/**
		 * Filters the attachment ID for a cropped image.
		 *
		 * @since 4.3.0
		 *
		 * @param int    $attachment_id The attachment ID of the cropped image.
		 * @param string $context       The Customizer control requesting the cropped image.
		 */
		$attachment_id = apply_filters( 'wp_ajax_cropped_attachment_id', $attachment_id, $context );
	}

	wp_send_json_success( wp_prepare_attachment_for_js( $attachment_id ) );
}

/**
 * Ajax handler for generating a password.
 *
 * @since 4.4.0
 */
function wp_ajax_generate_password() {
	wp_send_json_success( wp_generate_password( 24 ) );
}

/**
 * Ajax handler for saving the user's WordPress.org username.
 *
 * @since 4.4.0
 */
function wp_ajax_save_wporg_username() {
	if ( ! current_user_can( 'install_themes' ) && ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'save_wporg_username_' . get_current_user_id() );

	$app = getApp();
	$_request = $app['request']->attributes;
	$username = $_request->has( 'username' ) ? wp_unslash( $_request->get( 'username' ) ) : false;

	if ( ! $username ) {
		wp_send_json_error();
	}

	wp_send_json_success( update_user_meta( get_current_user_id(), 'wporg_favorites', $username ) );
}

/**
 * Ajax handler for installing a theme.
 *
 * @since 4.6.0
 *
 * @see Theme_Upgrader
 */
function wp_ajax_install_theme() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_theme_specified',
			'errorMessage' => __( 'No theme specified.' ),
		) );
	}

	$slug = sanitize_key( wp_unslash( $_post->get( 'slug' ) ) );

	$status = array(
		'install' => 'theme',
		'slug'    => $slug,
	);

	if ( ! current_user_can( 'install_themes' ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to install themes on this site.' );
		wp_send_json_error( $status );
	}

	include_once( ABSPATH . 'wp-admin/includes/theme.php' );

	$api = themes_api( 'theme_information', array(
		'slug'   => $slug,
		'fields' => array( 'sections' => false ),
	) );

	if ( is_wp_error( $api ) ) {
		$status['errorMessage'] = $api->get_error_message();
		wp_send_json_error( $status );
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$status['debug'] = $skin->get_upgrade_messages();
	}

	if ( is_wp_error( $result ) ) {
		$status['errorCode']    = $result->get_error_code();
		$status['errorMessage'] = $result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( is_wp_error( $skin->result ) ) {
		$status['errorCode']    = $skin->result->get_error_code();
		$status['errorMessage'] = $skin->result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( $skin->get_errors()->get_error_code() ) {
		$status['errorMessage'] = $skin->get_error_messages();
		wp_send_json_error( $status );
	} elseif ( is_null( $result ) ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	$status['themeName'] = wp_get_theme( $slug )->get( 'Name' );

	if ( current_user_can( 'switch_themes' ) ) {
		if ( is_multisite() ) {
			$status['activateUrl'] = add_query_arg( array(
				'action'   => 'enable',
				'_wpnonce' => wp_create_nonce( 'enable-theme_' . $slug ),
				'theme'    => $slug,
			), network_admin_url( 'themes.php' ) );
		} else {
			$status['activateUrl'] = add_query_arg( array(
				'action'     => 'activate',
				'_wpnonce'   => wp_create_nonce( 'switch-theme_' . $slug ),
				'stylesheet' => $slug,
			), admin_url( 'themes.php' ) );
		}
	}

	if ( ! is_multisite() && current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
		$status['customizeUrl'] = add_query_arg( array(
			'return' => urlencode( network_admin_url( 'theme-install.php', 'relative' ) ),
		), wp_customize_url( $slug ) );
	}

	/*
	 * See WP_Theme_Install_List_Table::_get_theme_status() if we wanted to check
	 * on post-install status.
	 */
	wp_send_json_success( $status );
}

/**
 * Ajax handler for updating a theme.
 *
 * @since 4.6.0
 *
 * @see Theme_Upgrader
 */
function wp_ajax_update_theme() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_theme_specified',
			'errorMessage' => __( 'No theme specified.' ),
		) );
	}

	$stylesheet = preg_replace( '/[^A-z0-9_\-]/', '', wp_unslash( $_post->get( 'slug' ) ) );
	$status     = array(
		'update'     => 'theme',
		'slug'       => $stylesheet,
		'newVersion' => '',
	);

	if ( ! current_user_can( 'update_themes' ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to update themes for this site.' );
		wp_send_json_error( $status );
	}

	$current = get_site_transient( 'update_themes' );
	if ( empty( $current ) ) {
		wp_update_themes();
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );
	$result   = $upgrader->bulk_upgrade( array( $stylesheet ) );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$status['debug'] = $skin->get_upgrade_messages();
	}

	if ( is_wp_error( $skin->result ) ) {
		$status['errorCode']    = $skin->result->get_error_code();
		$status['errorMessage'] = $skin->result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( $skin->get_errors()->get_error_code() ) {
		$status['errorMessage'] = $skin->get_error_messages();
		wp_send_json_error( $status );
	} elseif ( is_array( $result ) && ! empty( $result[ $stylesheet ] ) ) {

		// Theme is already at the latest version.
		if ( true === $result[ $stylesheet ] ) {
			$status['errorMessage'] = $upgrader->strings['up_to_date'];
			wp_send_json_error( $status );
		}

		$theme = wp_get_theme( $stylesheet );
		if ( $theme->get( 'Version' ) ) {
			$status['newVersion'] = $theme->get( 'Version' );
		}

		wp_send_json_success( $status );
	} elseif ( false === $result ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	// An unhandled error occurred.
	$status['errorMessage'] = __( 'Update failed.' );
	wp_send_json_error( $status );
}

/**
 * Ajax handler for deleting a theme.
 *
 * @since 4.6.0
 *
 * @see delete_theme()
 */
function wp_ajax_delete_theme() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_theme_specified',
			'errorMessage' => __( 'No theme specified.' ),
		) );
	}

	$stylesheet = preg_replace( '/[^A-z0-9_\-]/', '', wp_unslash( $_post->get( 'slug' ) ) );
	$status     = array(
		'delete' => 'theme',
		'slug'   => $stylesheet,
	);

	if ( ! current_user_can( 'delete_themes' ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to delete themes on this site.' );
		wp_send_json_error( $status );
	}

	if ( ! wp_get_theme( $stylesheet )->exists() ) {
		$status['errorMessage'] = __( 'The requested theme does not exist.' );
		wp_send_json_error( $status );
	}

	// Check filesystem credentials. `delete_theme()` will bail otherwise.
	$url = wp_nonce_url( 'themes.php?action=delete&stylesheet=' . urlencode( $stylesheet ), 'delete-theme_' . $stylesheet );
	ob_start();
	$credentials = request_filesystem_credentials( $url );
	ob_end_clean();
	if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	include_once( ABSPATH . 'wp-admin/includes/theme.php' );

	$result = delete_theme( $stylesheet );

	if ( is_wp_error( $result ) ) {
		$status['errorMessage'] = $result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( false === $result ) {
		$status['errorMessage'] = __( 'Theme could not be deleted.' );
		wp_send_json_error( $status );
	}

	wp_send_json_success( $status );
}

/**
 * Ajax handler for installing a plugin.
 *
 * @since 4.6.0
 *
 * @see Plugin_Upgrader
 */
function wp_ajax_install_plugin() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_plugin_specified',
			'errorMessage' => __( 'No plugin specified.' ),
		) );
	}

	$status = array(
		'install' => 'plugin',
		'slug'    => sanitize_key( wp_unslash( $_post->get( 'slug' ) ) ),
	);

	if ( ! current_user_can( 'install_plugins' ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.' );
		wp_send_json_error( $status );
	}

	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

	$api = plugins_api( 'plugin_information', array(
		'slug'   => sanitize_key( wp_unslash( $_post->get( 'slug' ) ) ),
		'fields' => array(
			'sections' => false,
		),
	) );

	if ( is_wp_error( $api ) ) {
		$status['errorMessage'] = $api->get_error_message();
		wp_send_json_error( $status );
	}

	$status['pluginName'] = $api->name;

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$status['debug'] = $skin->get_upgrade_messages();
	}

	if ( is_wp_error( $result ) ) {
		$status['errorCode']    = $result->get_error_code();
		$status['errorMessage'] = $result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( is_wp_error( $skin->result ) ) {
		$status['errorCode']    = $skin->result->get_error_code();
		$status['errorMessage'] = $skin->result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( $skin->get_errors()->get_error_code() ) {
		$status['errorMessage'] = $skin->get_error_messages();
		wp_send_json_error( $status );
	} elseif ( is_null( $result ) ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	$install_status = install_plugin_install_status( $api );
	$pagenow = $_post->get( 'pagenow' ) ? sanitize_key( $_post->get( 'pagenow' ) ) : '';

	// If install request is coming from import page, do not return network activation link.
	$plugins_url = ( 'import' === $pagenow ) ? admin_url( 'plugins.php' ) : network_admin_url( 'plugins.php' );

	if ( current_user_can( 'activate_plugins' ) && is_plugin_inactive( $install_status['file'] ) ) {
		$status['activateUrl'] = add_query_arg( array(
			'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $install_status['file'] ),
			'action'   => 'activate',
			'plugin'   => $install_status['file'],
		), $plugins_url );
	}

	if ( is_multisite() && current_user_can( 'manage_network_plugins' ) && 'import' !== $pagenow ) {
		$status['activateUrl'] = add_query_arg( array( 'networkwide' => 1 ), $status['activateUrl'] );
	}

	wp_send_json_success( $status );
}

/**
 * Ajax handler for updating a plugin.
 *
 * @since 4.2.0
 *
 * @see Plugin_Upgrader
 */
function wp_ajax_update_plugin() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'plugin' ) ) || empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_plugin_specified',
			'errorMessage' => __( 'No plugin specified.' ),
		) );
	}

	$plugin = plugin_basename( sanitize_text_field( wp_unslash( $_post->get( 'plugin' ) ) ) );

	$status = array(
		'update'     => 'plugin',
		'slug'       => sanitize_key( wp_unslash( $_post->get( 'slug' ) ) ),
		'oldVersion' => '',
		'newVersion' => '',
	);

	if ( ! current_user_can( 'update_plugins' ) || 0 !== validate_file( $plugin ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to update plugins for this site.' );
		wp_send_json_error( $status );
	}

	$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
	$status['plugin']     = $plugin;
	$status['pluginName'] = $plugin_data['Name'];

	if ( $plugin_data['Version'] ) {
		/* translators: %s: Plugin version */
		$status['oldVersion'] = sprintf( __( 'Version %s' ), $plugin_data['Version'] );
	}

	wp_update_plugins();

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->bulk_upgrade( array( $plugin ) );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$status['debug'] = $skin->get_upgrade_messages();
	}

	if ( is_wp_error( $skin->result ) ) {
		$status['errorCode']    = $skin->result->get_error_code();
		$status['errorMessage'] = $skin->result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( $skin->get_errors()->get_error_code() ) {
		$status['errorMessage'] = $skin->get_error_messages();
		wp_send_json_error( $status );
	} elseif ( is_array( $result ) && ! empty( $result[ $plugin ] ) ) {
		$plugin_update_data = current( $result );

		/*
		 * If the `update_plugins` site transient is empty (e.g. when you update
		 * two plugins in quick succession before the transient repopulates),
		 * this may be the return.
		 *
		 * Preferably something can be done to ensure `update_plugins` isn't empty.
		 * For now, surface some sort of error here.
		 */
		if ( true === $plugin_update_data ) {
			$status['errorMessage'] = __( 'Plugin update failed.' );
			wp_send_json_error( $status );
		}

		$plugin_data = get_plugins( '/' . $result[ $plugin ]['destination_name'] );
		$plugin_data = reset( $plugin_data );

		if ( $plugin_data['Version'] ) {
			/* translators: %s: Plugin version */
			$status['newVersion'] = sprintf( __( 'Version %s' ), $plugin_data['Version'] );
		}
		wp_send_json_success( $status );
	} elseif ( false === $result ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	// An unhandled error occurred.
	$status['errorMessage'] = __( 'Plugin update failed.' );
	wp_send_json_error( $status );
}

/**
 * Ajax handler for deleting a plugin.
 *
 * @since 4.6.0
 *
 * @see delete_plugins()
 */
function wp_ajax_delete_plugin() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;

	if ( empty( $_post->get( 'plugin' ) ) || empty( $_post->get( 'slug' ) ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_plugin_specified',
			'errorMessage' => __( 'No plugin specified.' ),
		) );
	}

	$plugin = plugin_basename( sanitize_text_field( wp_unslash( $_post->get( 'plugin' ) ) ) );

	$status = array(
		'delete' => 'plugin',
		'slug'   => sanitize_key( wp_unslash( $_post->get( 'slug' ) ) ),
	);

	if ( ! current_user_can( 'delete_plugins' ) || 0 !== validate_file( $plugin ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to delete plugins for this site.' );
		wp_send_json_error( $status );
	}

	$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
	$status['plugin']     = $plugin;
	$status['pluginName'] = $plugin_data['Name'];

	if ( is_plugin_active( $plugin ) ) {
		$status['errorMessage'] = __( 'You cannot delete a plugin while it is active on the main site.' );
		wp_send_json_error( $status );
	}

	// Check filesystem credentials. `delete_plugins()` will bail otherwise.
	$url = wp_nonce_url( 'plugins.php?action=delete-selected&verify-delete=1&checked[]=' . $plugin, 'bulk-plugins' );
	ob_start();
	$credentials = request_filesystem_credentials( $url );
	ob_end_clean();
	if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
		$wp_filesystem = $GLOBALS['wp_filesystem']; //NOSONAR

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	$result = delete_plugins( array( $plugin ) );

	if ( is_wp_error( $result ) ) {
		$status['errorMessage'] = $result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( false === $result ) {
		$status['errorMessage'] = __( 'Plugin could not be deleted.' );
		wp_send_json_error( $status );
	}

	wp_send_json_success( $status );
}

/**
 * Ajax handler for searching plugins.
 *
 * @since 4.6.0
 *
 * @global string $s Search term.
 */
function wp_ajax_search_plugins() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;
	$_server = $app['request']->server;

	$pagenow = $_post->get( 'pagenow' ) ? sanitize_key( $_post->get( 'pagenow' ) ) : '';
	if ( 'plugins-network' === $pagenow || 'plugins' === $pagenow ) {
		set_current_screen( $pagenow );
	}

	/** @var WP_Plugins_List_Table $wp_list_table */
	$wp_list_table = _get_list_table( 'WP_Plugins_List_Table', array(
		'screen' => get_current_screen(),
	) );

	$status = [];

	if ( ! $wp_list_table->ajax_user_can() ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to manage plugins for this site.' );
		wp_send_json_error( $status );
	}

	// Set the correct requester, so pagination works.
	$_server->set( 'REQUEST_URI', add_query_arg( array_diff_key( $_post->all(), [
		'_ajax_nonce' => null,
		'action'      => null,
	] ), network_admin_url( 'plugins.php', 'relative' ) ) );

	// List tables are garbage
	$GLOBALS['s'] = wp_unslash( $_post->get( 's' ) ); //NOSONAR

	$wp_list_table->prepare_items();

	ob_start();
	$wp_list_table->display();
	$status['count'] = count( $wp_list_table->items );
	$status['items'] = ob_get_clean();

	wp_send_json_success( $status );
}

/**
 * Ajax handler for searching plugins to install.
 *
 * @since 4.6.0
 */
function wp_ajax_search_install_plugins() {
	check_ajax_referer( 'updates' );

	$app = getApp();
	$_post = $app['request']->request;
	$_server = $app['request']->server;

	$pagenow = $_post->get( 'pagenow' ) ? sanitize_key( $_post->get( 'pagenow' ) ) : '';
	if ( 'plugin-install-network' === $pagenow || 'plugin-install' === $pagenow ) {
		set_current_screen( $pagenow );
	}

	/** @var WP_Plugin_Install_List_Table $wp_list_table */
	$wp_list_table = _get_list_table( 'WP_Plugin_Install_List_Table', array(
		'screen' => get_current_screen(),
	) );

	$status = [];

	if ( ! $wp_list_table->ajax_user_can() ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to manage plugins for this site.' );
		wp_send_json_error( $status );
	}

	// Set the correct requester, so pagination works.
	$_server->set( 'REQUEST_URI', add_query_arg( array_diff_key( $_post->all(), [
		'_ajax_nonce' => null,
		'action'      => null,
	] ), network_admin_url( 'plugin-install.php', 'relative' ) ) );

	$wp_list_table->prepare_items();

	ob_start();
	$wp_list_table->display();
	$status['count'] = (int) $wp_list_table->get_pagination_arg( 'total_items' );
	$status['items'] = ob_get_clean();

	wp_send_json_success( $status );
}
