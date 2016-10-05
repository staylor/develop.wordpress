<?php
/**
 * WordPress Query API
 *
 * The query API attempts to get which part of WordPress the user is on. It
 * also provides functionality for getting URL query information.
 *
 * @link https://codex.wordpress.org/The_Loop More information on The Loop.
 *
 * @package WordPress
 * @subpackage Query
 */

use function WP\getApp;

/**
 * Retrieve variable in the WP_Query class.
 *
 * @since 1.5.0
 * @since 3.9.0 The `$default` argument was introduced.
 *
 * @param string $var       The variable key to retrieve.
 * @param mixed  $default   Optional. Value to return if the query variable is not set. Default empty.
 * @return mixed Contents of the query variable.
 */
function get_query_var( $var, $default = '' ) {
	$app = getApp();
	return $app['wp']->current_query->get( $var, $default );
}

/**
 * Retrieve the currently-queried object.
 *
 * Wrapper for WP_Query::get_queried_object().
 *
 * @since 3.1.0
 * @access public
 *
 * @return object Queried object.
 */
function get_queried_object() {
	$app = getApp();
	return $app['wp']->current_query->get_queried_object();
}

/**
 * Retrieve ID of the current queried object.
 *
 * Wrapper for WP_Query::get_queried_object_id().
 *
 * @since 3.1.0
 *
 * @return int ID of the queried object.
 */
function get_queried_object_id() {
	$app = getApp();
	return $app['wp']->current_query->get_queried_object_id();
}

/**
 * Set query variable.
 *
 * @since 2.2.0
 *
 * @param string $var   Query variable key.
 * @param mixed  $value Query variable value.
 */
function set_query_var( $var, $value ) {
	$app = getApp();
	$app['wp']->current_query->set( $var, $value );
}

/**
 * Sets up The Loop with query parameters.
 *
 * Note: This function will completely override the main query and isn't intended for use
 * by plugins or themes. Its overly-simplistic approach to modifying the main query can be
 * problematic and should be avoided wherever possible. In most cases, there are better,
 * more performant options for modifying the main query such as via the {@see 'pre_get_posts'}
 * action within WP_Query.
 *
 * This must not be used within the WordPress Loop.
 *
 * @since 1.5.0
 *
 * @param array|string $query Array or string of WP_Query arguments.
 * @return array List of post objects.
 */
function query_posts($query) {
	$app = getApp();
	$app['wp']->current_query = new \WP_Query();
	return $app['wp']->current_query->query($query);
}

/**
 * Destroys the previous query and sets up a new query.
 *
 * This should be used after query_posts() and before another query_posts().
 * This will remove obscure bugs that occur when the previous WP_Query object
 * is not destroyed properly before another is set up.
 *
 * @since 2.3.0
 */
function wp_reset_query() {
	$app = getApp();
	$app['wp']->current_query = $app['wp']->query;
	wp_reset_postdata();
}

/**
 * After looping through a separate query, this function restores
 * the $post global to the current post in the main query.
 *
 * @since 3.0.0
 */
function wp_reset_postdata() {
	$app = getApp();
	if ( isset( $app['wp']->current_query ) ) {
		$app['wp']->current_query->reset_postdata();
	}
}

/*
 * Query type checks.
 */

function _current_query_flag( $func, $args = [] ) {
	$app = getApp();

	if ( ! isset( $app['wp']->current_query ) ) {
		_doing_it_wrong( $func, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return call_user_func_array( [ $app['wp']->current_query, $func ], $args );
}

function _current_query_proxy( $func ) {
	$app = getApp();
	return call_user_func( [ $app['wp']->current_query, $func ] );
}

/**
 * Is the query for an existing archive page?
 *
 * Month, Year, Category, Author, Post Type archive...
 *
 * @since 1.5.0
 * @return bool
 */
function is_archive() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * Is the query for an existing post type archive page?
 *
 * @since 3.1.0
 * @param string|array $post_types Optional. Post type or array of posts types to check against.
 * @return bool
 */
function is_post_type_archive( $post_types = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing attachment page?
 *
 * @since 2.0.0
 * @param int|string|array|object $attachment Attachment ID, title, slug, or array of such.
 * @return bool
 */
function is_attachment( $attachment = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing author archive page?
 *
 * If the $author parameter is specified, this function will additionally
 * check if the query is for one of the authors specified.
 *
 * @since 1.5.0
 * @param mixed $author Optional. User ID, nickname, nicename, or array of User IDs, nicknames, and nicenames
 * @return bool
 */
function is_author( $author = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing category archive page?
 *
 * If the $category parameter is specified, this function will additionally
 * check if the query is for one of the categories specified.
 *
 * @since 1.5.0
 * @param mixed $category Optional. Category ID, name, slug, or array of Category IDs, names, and slugs.
 * @return bool
 */
function is_category( $category = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing tag archive page?
 *
 * If the $tag parameter is specified, this function will additionally
 * check if the query is for one of the tags specified.
 *
 * @since 2.3.0
 *
 * @param mixed $tag Optional. Tag ID, name, slug, or array of Tag IDs, names, and slugs.
 * @return bool
 */
function is_tag( $tag = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing custom taxonomy archive page?
 *
 * If the $taxonomy parameter is specified, this function will additionally
 * check if the query is for that specific $taxonomy.
 *
 * If the $term parameter is specified in addition to the $taxonomy parameter,
 * this function will additionally check if the query is for one of the terms
 * specified.
 *
 * @since 2.5.0
 *
 * @param string|array     $taxonomy Optional. Taxonomy slug or slugs.
 * @param int|string|array $term     Optional. Term ID, name, slug or array of Term IDs, names, and slugs.
 * @return bool True for custom taxonomy archive pages, false for built-in taxonomies (category and tag archives).
 */
function is_tax( $taxonomy = '', $term = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_date() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_day() {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 1.5.0
 * @param string|array $feeds Optional feed types to check.
 * @return bool
 */
function is_feed( $feeds = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 3.0.0
 * @return bool
 */
function is_comment_feed() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * Is the query for the front page of the site?
 *
 * This is for what is displayed at your site's main URL.
 *
 * Depends on the site's "Front page displays" Reading Settings 'show_on_front' and 'page_on_front'.
 *
 * If you set a static page for the front page of your site, this function will return
 * true when viewing that page.
 *
 * Otherwise the same as @see is_home()
 *
 * @since 2.5.0
 *
 * @return bool True, if front of site.
 */
function is_front_page() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * Determines if the query is for the blog homepage.
 *
 * The blog homepage is the page that shows the time-based blog content of the site.
 *
 * is_home() is dependent on the site's "Front page displays" Reading Settings 'show_on_front'
 * and 'page_for_posts'.
 *
 * If a static page is set for the front page of the site, this function will return true only
 * on the page you set as the "Posts page".
 *
 * @since 1.5.0
 *
 * @see is_front_page()
 *
 * @return bool True if blog view homepage, otherwise false.
 */
function is_home() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_month() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * Is the query for an existing single page?
 *
 * If the $page parameter is specified, this function will additionally
 * check if the query is for one of the pages specified.
 *
 * @see is_single()
 * @see is_singular()
 *
 * @since 1.5.0
 *
 * @param int|string|array $page Optional. Page ID, title, slug, or array of such. Default empty.
 * @return bool Whether the query is for an existing single page.
 */
function is_page( $page = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_paged() {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 2.0.0
 * @return bool
 */
function is_preview() {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 2.1.0
 * @return bool
 */
function is_robots() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_search() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * Is the query for an existing single post?
 *
 * Works for any post type, except attachments and pages
 *
 * If the $post parameter is specified, this function will additionally
 * check if the query is for one of the Posts specified.
 *
 * @see is_page()
 * @see is_singular()
 *
 * @since 1.5.0
 *
 * @param int|string|array $post Optional. Post ID, title, slug, or array of such. Default empty.
 * @return bool Whether the query is for an existing single post.
 */
function is_single( $post = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * Is the query for an existing single post of any post type (post, attachment, page, ... )?
 *
 * If the $post_types parameter is specified, this function will additionally
 * check if the query is for one of the Posts Types specified.
 *
 * @see is_page()
 * @see is_single()
 *
 * @since 1.5.0
 *
 * @param string|array $post_types Optional. Post type or array of post types. Default empty.
 * @return bool Whether the query is for an existing single post of any of the given post types.
 */
function is_singular( $post_types = '' ) {
	return _current_query_flag( __FUNCTION__, func_get_args() );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_time() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_trackback() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_year() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 1.5.0
 * @return bool
 */
function is_404() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 4.4.0
 * @return bool Whether we're in an embedded post or not.
 */
function is_embed() {
	return _current_query_flag( __FUNCTION__ );
}

/**
 * @since 3.3.0
 * @return bool
 */
function is_main_query() {
	if ( 'pre_get_posts' === current_filter() ) {
		$message = sprintf(
			/* translators: 1: pre_get_posts 2: WP_Query->is_main_query() 3: is_main_query() 4: link to codex is_main_query() page. */
			__( 'In %1$s, use the %2$s method, not the %3$s function. See %4$s.' ),
			'<code>pre_get_posts</code>',
			'<code>WP_Query->is_main_query()</code>',
			'<code>is_main_query()</code>',
			__( 'https://codex.wordpress.org/Function_Reference/is_main_query' )
		);
		_doing_it_wrong( __FUNCTION__, $message, '3.7.0' );
	}

	$app = getApp();
	return $app['wp']->current_query->is_main_query();
}

/*
 * The Loop. Post loop control.
 */

/**
 * @since 1.5.0
 * @return bool
 */
function have_posts() {
	return _current_query_proxy( __FUNCTION__ );
}

/**
 * @since 2.0.0
 * @return bool True if caller is within loop, false if loop hasn't started or ended.
 */
function in_the_loop() {
	$app = getApp();
	return $app['wp']->current_query->in_the_loop;
}

/**
 * @since 1.5.0
 */
function rewind_posts() {
	return _current_query_proxy( __FUNCTION__ );
}

/**
 * @since 1.5.0
 */
function the_post() {
	return _current_query_proxy( __FUNCTION__ );
}

/*
 * Comments loop.
 */

/**
 * @since 2.2.0
 * @return bool
 */
function have_comments() {
	return _current_query_proxy( __FUNCTION__ );
}

/**
 * @since 2.2.0
 * @return object
 */
function the_comment() {
	return _current_query_proxy( __FUNCTION__ );
}

/**
 * Redirect old slugs to the correct permalink.
 *
 * Attempts to find the current slug from the past slugs.
 *
 * @since 2.1.0
 */
function wp_old_slug_redirect() {
	if ( is_404() && '' !== get_query_var( 'name' ) ) {
		$app = getApp();
		$wpdb = $app['db'];

		// Guess the current post_type based on the query vars.
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		} elseif ( get_query_var( 'attachment' ) ) {
			$post_type = 'attachment';
		} elseif ( get_query_var( 'pagename' ) ) {
			$post_type = 'page';
		} else {
			$post_type = 'post';
		}

		if ( is_array( $post_type ) ) {
			if ( count( $post_type ) > 1 ) {
				return;
			}
			$post_type = reset( $post_type );
		}

		// Do not attempt redirect for hierarchical post types
		if ( is_post_type_hierarchical( $post_type ) ) {
			return;
		}

		$query = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta, $wpdb->posts WHERE ID = post_id AND post_type = %s AND meta_key = '_wp_old_slug' AND meta_value = %s", $post_type, get_query_var( 'name' ) );

		// if year, monthnum, or day have been specified, make our query more precise
		// just in case there are multiple identical _wp_old_slug values
		if ( get_query_var( 'year' ) ) {
			$query .= $wpdb->prepare(" AND YEAR(post_date) = %d", get_query_var( 'year' ) );
		}
		if ( get_query_var( 'monthnum' ) ) {
			$query .= $wpdb->prepare(" AND MONTH(post_date) = %d", get_query_var( 'monthnum' ) );
		}
		if ( get_query_var( 'day' ) ) {
			$query .= $wpdb->prepare(" AND DAYOFMONTH(post_date) = %d", get_query_var( 'day' ) );
		}

		$id = (int) $wpdb->get_var( $query );

		if ( ! $id ) {
			return;
		}

		$link = get_permalink( $id );

		if ( get_query_var( 'paged' ) > 1 ) {
			$link = user_trailingslashit( trailingslashit( $link ) . 'page/' . get_query_var( 'paged' ) );
		} elseif( is_embed() ) {
			$link = user_trailingslashit( trailingslashit( $link ) . 'embed' );
		}

		/**
		 * Filters the old slug redirect URL.
		 *
		 * @since 4.4.0
		 *
		 * @param string $link The redirect URL.
		 */
		$link = apply_filters( 'old_slug_redirect_url', $link );

		if ( ! $link ) {
			return;
		}

		wp_redirect( $link, 301 ); // Permanent redirect
		exit;
	}
}

/**
 * Set up global post data.
 *
 * @since 1.5.0
 * @since 4.4.0 Added the ability to pass a post ID to `$post`.
 *
 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
 * @return bool True when finished.
 */
function setup_postdata( $post ) {
	$app = getApp();

	if ( $app['wp']->current_query instanceof \WP_Query ) {
		return $app['wp']->current_query->setup_postdata( $post );
	}

	return false;
}
