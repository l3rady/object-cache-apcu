<?php
/**
 * Runs the drop-in in a fresh PHP process and prints what it found, as JSON.
 *
 * Used by test-availability.php, which starts it with different APCu settings
 * because those cannot be changed inside a running process.
 *
 * @package object-cache-apcu
 */

define( 'ABSPATH', '/tmp/object-cache-apcu-probe/' );
define( 'KB_IN_BYTES', 1024 );

/**
 * Stub: this is a single site.
 *
 * @return bool
 */
function is_multisite() {
	return false;
}

require_once dirname( __DIR__, 3 ) . '/object-cache.php';

$probe_cache = new WP_Object_Cache();

echo wp_json_encode_stub(
	array(
		'available' => $probe_cache->get_apcu_available(),
		'set'       => $probe_cache->set( 'key', 'value', 'posts' ),
		'get'       => $probe_cache->get( 'key', 'posts' ),
		'add_again' => $probe_cache->add( 'key', 'other', 'posts' ),
	)
);

/**
 * JSON encoding without WordPress.
 *
 * @param array $data Data to encode.
 * @return string
 */
function wp_json_encode_stub( $data ) {
	return json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WordPress is not loaded here.
}
