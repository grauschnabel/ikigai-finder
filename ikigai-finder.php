<?php

/**
 * Plugin Name: Ikigai Finder
 * Plugin URI: https://github.com/grauschnabel/ikigai-finder
 * Description: Ein WordPress-Plugin, das einen KI-gestützten Ikigai-Finder bereitstellt, um Benutzer durch ihren persönlichen Ikigai-Entdeckungsprozess zu führen.
 * Author: Martin Kaffanke
 * Author URI: https://kaffanke.info
 * Version: 0.1.7
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ikigai-finder
 * Domain Path: /languages
 *
 * @package Ikigai_Finder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
define( 'IKIGAI_FINDER_VERSION', '0.1.7' );

/**
 * Plugin directory path.
 *
 * @var string
 */
define( 'IKIGAI_FINDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @var string
 */
define( 'IKIGAI_FINDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once IKIGAI_FINDER_PLUGIN_DIR . 'includes/class-ikigai-finder-block.php';
require_once IKIGAI_FINDER_PLUGIN_DIR . 'includes/class-ikigai-finder-settings.php';

/**
 * Initialize plugin functionality.
 *
 * @return void
 */
function ikigai_finder_init() {
	// Debug information.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Ikigai Finder: Current locale: ' . get_locale() );
		error_log( 'Ikigai Finder: Plugin path: ' . plugin_dir_path( __FILE__ ) );
		error_log( 'Ikigai Finder: Language path: ' . dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	// Load text domain for translations.
	$loaded = load_plugin_textdomain( 'ikigai-finder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Ikigai Finder: Textdomain loaded: ' . ( $loaded ? 'yes' : 'no' ) );

		// Test translation.
		$test_string = __( 'Ikigai Finder Settings', 'ikigai-finder' );
		error_log( 'Ikigai Finder: Test translation: ' . $test_string );
	}

	Ikigai_Finder_Block::init();
	Ikigai_Finder_Settings::init();
}
add_action( 'plugins_loaded', 'ikigai_finder_init' );

/**
 * Filter to show available languages.
 *
 * @param string $mofile Path to the MO file.
 * @param string $domain Text domain.
 * @return string Modified MO file path.
 */
function ikigai_finder_load_textdomain_mofile( $mofile, $domain ) {
	if ( 'ikigai-finder' === $domain && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Ikigai Finder: Trying to load MO file: ' . $mofile );
		error_log( 'Ikigai Finder: File exists: ' . ( file_exists( $mofile ) ? 'yes' : 'no' ) );
		error_log( 'Ikigai Finder: File readable: ' . ( is_readable( $mofile ) ? 'yes' : 'no' ) );
	}
	return $mofile;
}
add_filter( 'load_textdomain_mofile', 'ikigai_finder_load_textdomain_mofile', 10, 2 );
