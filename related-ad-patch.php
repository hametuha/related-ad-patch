<?php
/*
Plugin Name: Related Ad Patch
Plugin URI:
Description: Alternative
Author: Hametuha INC.
Author URI: https://hametuha.co.jp
Text Domain: rap
Domain Path: /languages/
License: GPL v3 or later.
Version: nightly
*/

// Do not access directly.
defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin.
add_action( 'plugins_loaded', function() {

	load_plugin_textdomain( 'rap', false, basename( __DIR__ ) . '/languages' );
	// Files.
	require_once __DIR__ . '/includes/functions.php';
	// Classes.
	\Hametuha\RelatedAdPatch\Template::get_instance();
	\Hametuha\RelatedAdPatch\AdPostType::get_instance();
	\Hametuha\RelatedAdPatch\Setting::get_instance();
} );
