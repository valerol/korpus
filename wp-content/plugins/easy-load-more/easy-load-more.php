<?php
/*
 * Plugin Name: Easy Load More
 * Version: 1.0
 * Plugin URI: http://www.idiom.co/
 * Description: Add an ajax load more button to any of your archive pages without editing your templates.
 * Author: Idiom Interactive
 * Author URI: http://www.idiom.co/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: easy-load-more
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Idiom Interactive
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-easy-load-more.php' );
require_once( 'includes/class-easy-load-more-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-easy-load-more-admin-api.php' );

/**
 * Returns the main instance of Easy_Load_More to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Easy_Load_More
 */
function Easy_Load_More () {
	$instance = Easy_Load_More::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Easy_Load_More_Settings::instance( $instance );
	}

	return $instance;
}

Easy_Load_More();

function load_more_button() {
	Easy_Load_More()->build_load_more_button();
}