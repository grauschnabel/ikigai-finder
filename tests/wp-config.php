<?php
/**
 * WordPress-Testkonfiguration
 *
 * @package WP_Ikigai
 */

// ** MySQL Einstellungen ** //
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Präfixe für Tabellen in der Datenbank.
$table_prefix = 'wptest_';

// Setze den Debug-Modus für Tests.
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Test-spezifische Einstellungen.
define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'WP_Ikigai Tests' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
define( 'WP_TESTS_MULTISITE', false );

// Schalte Cron ab, damit keine Jobs während der Tests laufen.
define( 'DISABLE_WP_CRON', true );

// ** Autosave deaktivieren ** //
define( 'AUTOSAVE_INTERVAL', 0 );
define( 'WP_POST_REVISIONS', 0 );

// Diese Einstellungen verwenden, um überhaupt keine Mails zu versenden.
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_EMAIL_PASS', 'password' );

// Absolute path zu WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', getenv( 'WP_CORE_DIR' ) );
}
