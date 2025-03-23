<?php
/**
 * Plugin Name: WP Ikigai Finder
 * Plugin URI: https://github.com/grauschnabel/wp-ikigai
 * Description: An AI-powered Ikigai discovery tool using ChatGPT
 * Version: 0.1.5
 * Author: Martin Kaffanke
 * Author URI: https://github.com/grauschnabel
 * Text Domain: wp-ikigai
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package WP_Ikigai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants.
define( 'WP_IKIGAI_VERSION', '0.1.5' );
define( 'WP_IKIGAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_IKIGAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-block.php';
require_once WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-settings.php';

// Initialize Plugin Classes.
add_action( 'plugins_loaded', function() {
	// Debug information.
	error_log( 'WP Ikigai: Current locale: ' . get_locale() );
	error_log( 'WP Ikigai: Plugin path: ' . plugin_dir_path( __FILE__ ) );
	error_log( 'WP Ikigai: Language path: ' . dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// Load text domain for translations.
	$loaded = load_plugin_textdomain( 'wp-ikigai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	error_log( 'WP Ikigai: Textdomain loaded: ' . ( $loaded ? 'yes' : 'no' ) );
	
	// Test translation.
	$test_string = __( 'WP Ikigai Settings', 'wp-ikigai' );
	error_log( 'WP Ikigai: Test translation: ' . $test_string );
	
	WP_Ikigai_Block::init();
	WP_Ikigai_Settings::init();
});

// Add filter to show available languages.
add_filter( 'load_textdomain_mofile', function( $mofile, $domain ) {
	if ( 'wp-ikigai' === $domain ) {
		error_log( 'WP Ikigai: Trying to load MO file: ' . $mofile );
		error_log( 'WP Ikigai: File exists: ' . ( file_exists( $mofile ) ? 'yes' : 'no' ) );
		error_log( 'WP Ikigai: File readable: ' . ( is_readable( $mofile ) ? 'yes' : 'no' ) );
	}
	return $mofile;
}, 10, 2 );
?>