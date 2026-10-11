<?php
/**
 * Bootstrap for the plain APCu tests.
 *
 * These tests run the drop-in without WordPress, so the few WordPress
 * functions and constants it relies on are stubbed here.
 *
 * @package object-cache-apcu
 */

define( 'ABSPATH', '/tmp/object-cache-apcu-tests/' );
define( 'KB_IN_BYTES', 1024 );

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! extension_loaded( 'apcu' ) || ! apcu_enabled() ) {
	fwrite( STDERR, "APCu is not enabled. Run the tests with `make test-apcu`; it uses the Docker image.\n" );
	exit( 1 );
}

/**
 * Stub: whether this is a multisite install. Tests flip it with the global below.
 *
 * @return bool
 */
function is_multisite() {
	return ! empty( $GLOBALS['apcu_test_multisite'] );
}

/**
 * Stub: the real function reports whether cache additions are suspended.
 *
 * @return bool
 */
function wp_suspend_cache_addition() {
	return false;
}

/**
 * Stub for esc_html().
 *
 * @param string $text Text to escape.
 * @return string
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

/**
 * Stub for _deprecated_function().
 */
function _deprecated_function() {}

require_once dirname( __DIR__, 2 ) . '/object-cache.php';

require_once __DIR__ . '/class-apcu-test-case.php';
require_once __DIR__ . '/class-racing-object-cache.php';
