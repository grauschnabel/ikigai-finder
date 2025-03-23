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

/**
 * Plugin version.
 *
 * @var string
 */
define( 'WP_IKIGAI_VERSION', '0.1.5' );

/**
 * Plugin directory path.
 *
 * @var string
 */
define( 'WP_IKIGAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @var string
 */
define( 'WP_IKIGAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-block.php';
require_once WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-settings.php';

/**
 * Initialize plugin functionality.
 *
 * @return void
 */
function wp_ikigai_init() {
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
}
add_action( 'plugins_loaded', 'wp_ikigai_init' );

/**
 * Filter to show available languages.
 *
 * @param string $mofile Path to the MO file.
 * @param string $domain Text domain.
 * @return string Modified MO file path.
 */
function wp_ikigai_load_textdomain_mofile( $mofile, $domain ) {
	if ( 'wp-ikigai' === $domain ) {
		error_log( 'WP Ikigai: Trying to load MO file: ' . $mofile );
		error_log( 'WP Ikigai: File exists: ' . ( file_exists( $mofile ) ? 'yes' : 'no' ) );
		error_log( 'WP Ikigai: File readable: ' . ( is_readable( $mofile ) ? 'yes' : 'no' ) );
	}
	return $mofile;
}
add_filter( 'load_textdomain_mofile', 'wp_ikigai_load_textdomain_mofile', 10, 2 );
?>
