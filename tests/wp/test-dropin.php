<?php
/**
 * Tests for the drop-in running inside a real WordPress.
 *
 * Run with `make test-wp` (single site) and `make test-wp-ms` (multisite). They use
 * WordPress's own test bootstrap and database, with this file installed as
 * wp-content/object-cache.php.
 *
 * @package object-cache-apcu
 */

/**
 * Behaviour that needs WordPress itself.
 */
class Test_Dropin extends WP_UnitTestCase {

	/**
	 * Every other test here is meaningless if the drop-in is not the cache in use.
	 */
	public function test_the_dropin_is_the_object_cache_in_use() {
		global $wp_object_cache;

		$this->assertTrue( wp_using_ext_object_cache(), 'WordPress should see an external object cache.' );
		$this->assertInstanceOf( WP_Object_Cache::class, $wp_object_cache );
		$this->assertTrue( method_exists( $wp_object_cache, 'get_apcu_available' ), 'This is core\'s cache, not the drop-in.' );
		$this->assertTrue( $wp_object_cache->get_apcu_available(), 'APCu should be enabled.' );
	}

	/**
	 * The drop-in advertises every feature core knows about.
	 */
	public function test_it_supports_all_core_features() {
		foreach ( array( 'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple', 'flush_runtime', 'flush_group' ) as $feature ) {
			$this->assertTrue( wp_cache_supports( $feature ), $feature );
		}
	}

	/**
	 * Plugins call core's cache methods on the global object, so ours must have them all.
	 *
	 * Reads the public methods from core's own class file, so a method added by a
	 * newer WordPress (the nightly trunk run) shows up here.
	 */
	public function test_every_public_method_of_cores_cache_class_exists() {
		global $wp_object_cache;

		$source = file_get_contents( ABSPATH . WPINC . '/class-wp-object-cache.php' );
		$this->assertNotFalse( $source, 'Could not read core\'s class-wp-object-cache.php.' );

		preg_match_all( '/^\s*public\s+function\s+&?\s*(\w+)\s*\(/m', $source, $matches );
		$this->assertContains( 'flush_group', $matches[1], 'The parse found no methods; the check is not looking at core\'s class.' );

		foreach ( $matches[1] as $method ) {
			$this->assertTrue( method_exists( $wp_object_cache, $method ), 'Missing WP_Object_Cache::' . $method . '()' );
		}
	}

	/**
	 * Values written through WordPress's functions live in APCu, not just in memory.
	 */
	public function test_values_survive_clearing_the_runtime_cache() {
		wp_cache_set( 'wp-test-key', 'wp-test-value', 'wp-test-group' );

		wp_cache_flush_runtime();

		$this->assertSame( 'wp-test-value', wp_cache_get( 'wp-test-key', 'wp-test-group', false, $found ) );
		$this->assertTrue( $found );
	}

	/**
	 * The multiple-key functions behave as core documents them.
	 */
	public function test_multiple_key_functions() {
		$this->assertSame(
			array(
				'a' => true,
				'b' => true,
			),
			wp_cache_set_multiple(
				array(
					'a' => 1,
					'b' => 2,
				),
				'wp-test-group'
			)
		);

		$this->assertSame(
			array(
				'a' => 1,
				'b' => 2,
				'c' => false,
			),
			wp_cache_get_multiple( array( 'a', 'b', 'c' ), 'wp-test-group' )
		);

		$this->assertSame(
			array(
				'a' => true,
				'z' => false,
			),
			wp_cache_delete_multiple( array( 'a', 'z' ), 'wp-test-group' )
		);
	}

	/**
	 * Options go through the cache and come back intact, including the alloptions cache.
	 */
	public function test_options_round_trip_through_the_cache() {
		update_option( 'wp_test_option', array( 'a' => 1 ) );

		wp_cache_flush_runtime();

		$this->assertSame( array( 'a' => 1 ), get_option( 'wp_test_option' ) );

		delete_option( 'wp_test_option' );
		$this->assertFalse( get_option( 'wp_test_option' ) );
	}

	/**
	 * With an external cache, WordPress keeps transients in it instead of the database.
	 */
	public function test_transients_are_stored_in_the_cache() {
		set_transient( 'wp_test_transient', 'value', HOUR_IN_SECONDS );

		$this->assertSame( 'value', get_transient( 'wp_test_transient' ) );
		$this->assertSame( 'value', wp_cache_get( 'wp_test_transient', 'transient' ) );

		delete_transient( 'wp_test_transient' );
		$this->assertFalse( get_transient( 'wp_test_transient' ) );
	}

	/**
	 * Site groups are separate per site, global groups are shared.
	 *
	 * @group ms-required
	 */
	public function test_switch_to_blog_separates_site_groups_but_not_global_groups() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only: run `make test-wp-ms`.' );
		}

		$other_site = self::factory()->blog->create();

		wp_cache_set( 'shared-key', 'site-one', 'wp-test-group' );
		wp_cache_set( 'shared-key', 'global', 'users' );

		switch_to_blog( $other_site );

		$this->assertFalse( wp_cache_get( 'shared-key', 'wp-test-group' ), 'Site groups must not leak between sites.' );
		$this->assertSame( 'global', wp_cache_get( 'shared-key', 'users' ), 'Global groups are shared by every site.' );

		wp_cache_set( 'shared-key', 'site-two', 'wp-test-group' );

		restore_current_blog();

		$this->assertSame( 'site-one', wp_cache_get( 'shared-key', 'wp-test-group' ) );
	}
}
