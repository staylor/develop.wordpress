<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */

// Load the WordPress library.
require_once( __DIR__ . '/wp-load.php' );

// Set up the WordPress query.
wp();

// Load the theme template.
require_once( ABSPATH . WPINC . '/template-loader.php' );
