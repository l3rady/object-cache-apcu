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
define( 'MB_IN_BYTES', 1024 * KB_IN_BYTES );

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
 * Tests switch it on with the global below.
 *
 * @return bool
 */
function wp_suspend_cache_addition() {
	return ! empty( $GLOBALS['apcu_test_suspend_additions'] );
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
 * Stub for _doing_it_wrong(): remember the calls so tests can check them.
 *
 * @param string $function_name The function that was called wrongly.
 * @param string $message       Explanation.
 * @param string $version       Version the notice was added in.
 */
function _doing_it_wrong( $function_name, $message, $version ) {
	$GLOBALS['apcu_test_doing_it_wrong'][] = array( $function_name, $message, $version );
}

/**
 * Stub for _deprecated_function().
 *
 * @param string $function_name The function that is deprecated.
 * @param string $version       Version it was deprecated in.
 * @param string $replacement   Replacement to use instead.
 */
function _deprecated_function( $function_name, $version, $replacement = '' ) {}

require_once dirname( __DIR__, 2 ) . '/object-cache.php';

require_once __DIR__ . '/class-apcu-test-case.php';
require_once __DIR__ . '/class-racing-object-cache.php';
