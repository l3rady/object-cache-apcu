<?php
/**
 * Base class for the plain APCu tests.
 *
 * @package object-cache-apcu
 */

use PHPUnit\Framework\TestCase;

/**
 * Gives every test an empty APCu and a fresh cache object, and helpers to
 * simulate other requests and the different ways the cache can be configured.
 */
abstract class APCu_Test_Case extends TestCase {

	/**
	 * Empty APCu and reset the multisite stub before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		apcu_clear_cache();
		unset( $GLOBALS['apcu_test_multisite'] );
	}

	/**
	 * Creates the global cache the way WordPress does (wp_cache_init()) and
	 * optionally forces how it is configured.
	 *
	 * Calling this again simulates another request: the object is new, so all
	 * in-request state is gone, but anything stored in APCu is still there.
	 *
	 * @param bool $apcu  Whether the cache may use APCu. False exercises the in-memory fallback.
	 * @param bool $local Whether the in-request array cache is used (WP_APCU_LOCAL_CACHE).
	 * @return WP_Object_Cache
	 */
	protected function new_request( $apcu = true, $local = true ) {
		wp_cache_init();

		$cache = $GLOBALS['wp_object_cache'];

		$this->set_private( $cache, 'apcu_available', $apcu );
		$this->set_private( $cache, 'local_cache_enabled', $local );

		$cache->add_non_persistent_groups( 'np' );
		$cache->add_global_groups( 'global' );

		return $cache;
	}

	/**
	 * Reads a private property.
	 *
	 * @param object $instance Object to read from.
	 * @param string $property Property name.
	 * @return mixed
	 */
	protected function get_private( $instance, $property ) {
		$reflection = new ReflectionProperty( $instance, $property );
		if ( PHP_VERSION_ID < 80100 ) {
			// Needed before PHP 8.1; deprecated (and a no-op) after.
			$reflection->setAccessible( true );
		}

		return $reflection->getValue( $instance );
	}

	/**
	 * Calls a private method.
	 *
	 * @param object $instance Object to call the method on.
	 * @param string $method   Method name.
	 * @param array  $args     Arguments.
	 * @return mixed
	 */
	protected function call_private( $instance, $method, array $args = array() ) {
		$reflection = new ReflectionMethod( $instance, $method );
		if ( PHP_VERSION_ID < 80100 ) {
			// Needed before PHP 8.1; deprecated (and a no-op) after.
			$reflection->setAccessible( true );
		}

		return $reflection->invokeArgs( $instance, $args );
	}

	/**
	 * Writes a private property.
	 *
	 * @param object $instance Object to modify.
	 * @param string $property Property name.
	 * @param mixed  $value    New value.
	 */
	protected function set_private( $instance, $property, $value ) {
		$reflection = new ReflectionProperty( $instance, $property );
		if ( PHP_VERSION_ID < 80100 ) {
			// Needed before PHP 8.1; deprecated (and a no-op) after.
			$reflection->setAccessible( true );
		}
		$reflection->setValue( $instance, $value );
	}

	/**
	 * The ways the cache can be configured, with a group that is persistent
	 * under that configuration.
	 *
	 * Columns: use APCu, use local cache, group, and whether the group is stored
	 * in APCu (true) or only in memory (false).
	 *
	 * @return array
	 */
	public function scenarios() {
		return array(
			'apcu, local cache'            => array( true, true, 'posts', true ),
			'apcu, no local cache'         => array( true, false, 'posts', true ),
			'non persistent group'         => array( true, true, 'np', false ),
			'no apcu (in-memory fallback)' => array( false, true, 'posts', false ),
		);
	}
}
