<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WP_Ikigai
 */

// Composer autoloader muss geladen sein.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Lade den WordPress Testrahmen.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Konnte WordPress Test Suite nicht finden. Stellen Sie sicher, dass die Umgebungsvariable WP_TESTS_DIR korrekt ist." . PHP_EOL;
	exit( 1 );
}

// Gib das Plugin-Hauptverzeichnis an.
define( 'WP_IKIGAI_DIR', dirname( __DIR__ ) );

// Starte den WordPress-Test-Bootstrap-Prozess.
require_once $_tests_dir . '/includes/bootstrap.php';

/**
 * Plugin manuell laden, da es normalerweise von WordPress geladen würde.
 */
function _manually_load_plugin() {
	require WP_IKIGAI_DIR . '/wp_ikigai.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Plugin-spezifische Test-Hilfsfunktionen hier initialisieren.
require_once WP_IKIGAI_DIR . '/tests/test-helpers.php';
