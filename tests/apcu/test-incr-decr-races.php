<?php
/**
 * Tests for losing the compare and swap in incr() and decr().
 *
 * @package object-cache-apcu
 */

/**
 * Losing the compare and swap to another request (issue #27).
 */
class Test_Incr_Decr_Races extends APCu_Test_Case {

	/**
	 * Creates the global cache as a Racing_Object_Cache.
	 *
	 * @param int $races How many reads go stale.
	 * @return Racing_Object_Cache
	 */
	private function racing_cache( $races ) {
		$cache = new Racing_Object_Cache();
		$cache->add_non_persistent_groups( 'np' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the test replaces the cache the way wp_cache_init() does.
		$GLOBALS['wp_object_cache'] = $cache;
		$cache->races               = $races;

		return $cache;
	}

	/**
	 * The change is retried against the new value, so neither change is lost.
	 */
	public function test_a_lost_race_is_retried_and_no_update_is_lost() {
		$cache = $this->racing_cache( 0 );
		wp_cache_set( 'n', 1, 'posts' );
		$cache->races = 2;

		// Two other requests each add 100 while this one is working.
		$this->assertSame( 202, wp_cache_incr( 'n', 1, 'posts' ) );
		$this->assertSame( 3, $cache->reads, 'Two lost races and one success.' );
		$this->assertSame( 202, wp_cache_get( 'n', 'posts', true ) );
	}

	/**
	 * Gives up after the maximum number of attempts, and says so by returning false.
	 */
	public function test_gives_up_after_the_maximum_number_of_attempts() {
		$cache = $this->racing_cache( 0 );
		wp_cache_set( 'n', 1, 'posts' );
		$cache->races = PHP_INT_MAX;

		$this->assertFalse( wp_cache_incr( 'n', 1, 'posts' ) );
		$this->assertSame( ( new ReflectionClassConstant( WP_Object_Cache::class, 'MAX_CAS_ATTEMPTS' ) )->getValue(), $cache->reads );
	}

	/**
	 * A missing key is reported at once, without retrying.
	 */
	public function test_a_missing_key_is_not_retried() {
		$cache = $this->racing_cache( PHP_INT_MAX );

		$this->assertFalse( wp_cache_incr( 'missing', 1, 'posts' ) );
		$this->assertSame( 1, $cache->reads );
	}
}
