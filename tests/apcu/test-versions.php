<?php
/**
 * Tests for how version tokens are read and created, and for a failed multiple write.
 *
 * @package object-cache-apcu
 */

/**
 * Every key carries version tokens, so reading them wrongly serves stale data.
 */
class Test_Versions extends APCu_Test_Case {

	/**
	 * Reads a version through the private method, as the cache does for each key.
	 *
	 * @param WP_Object_Cache $cache The cache.
	 * @param string          $key   The version key.
	 * @return string
	 */
	private function read_version( $cache, $key ) {
		return $this->call_private( $cache, 'get_cache_version', array( $key ) );
	}

	/**
	 * Makes the version key for a group, as the cache does.
	 *
	 * @param WP_Object_Cache $cache The cache.
	 * @return string
	 */
	private function group_version_key( $cache ) {
		return $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) );
	}

	/**
	 * A missing version is created, stored, and then reused.
	 */
	public function test_a_missing_version_is_created_and_stored_in_apcu() {
		$cache = $this->new_request();
		$key   = $this->group_version_key( $cache );

		$version = $this->read_version( $cache, $key );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{12}$/', $version );
		$this->assertSame( $version, apcu_fetch( $key ), 'The new version must be in APCu for other requests.' );
		$this->assertSame( $version, $this->read_version( $cache, $key ), 'The next read uses the same version.' );
	}

	/**
	 * A version that exists in APCu is used, not replaced.
	 */
	public function test_an_existing_version_is_read_from_apcu() {
		$cache = $this->new_request();
		$key   = $this->group_version_key( $cache );
		apcu_store( $key, 'written-elsewhere' );

		$this->assertSame( 'written-elsewhere', $this->read_version( $cache, $key ) );
		$this->assertSame( 'written-elsewhere', apcu_fetch( $key ) );
	}

	/**
	 * If another request creates the version first, both requests agree on that one.
	 */
	public function test_the_version_created_by_another_request_wins_the_race() {
		wp_cache_init();
		$cache = new Version_Racing_Object_Cache();
		$key   = $this->group_version_key( $cache );

		$cache->other_request_key = $key;
		$version                  = $this->read_version( $cache, $key );

		$this->assertSame( 'from-another-request', $version );
		$this->assertSame( 'from-another-request', apcu_fetch( $key ), 'Our own token must not overwrite it.' );
	}

	/**
	 * Without APCu the version lives in memory and stays the same for the request.
	 */
	public function test_without_apcu_a_version_is_created_once_and_kept_in_memory() {
		$cache = $this->new_request( false );
		$key   = $this->group_version_key( $cache );

		$first = $this->read_version( $cache, $key );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{12}$/', $first );
		$this->assertSame( $first, $this->read_version( $cache, $key ), 'The second read finds the stored version.' );
		$this->assertSame( $first, $this->get_private( $cache, 'non_persistent_cache' )[ $key ] );
		$this->assertFalse( apcu_fetch( $key ), 'Nothing is written to APCu.' );
	}

	/**
	 * A write APCu refuses must not leave the old value in the local copy.
	 */
	public function test_a_refused_set_multiple_drops_the_local_copy() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'in-apcu', 'posts' );

		// Put a different value in the local copy, as if another request had changed APCu.
		$local              = $this->get_private( $cache, 'local_cache' );
		$full_key           = array_key_first( $local );
		$local[ $full_key ] = 'stale-local-copy';
		$this->set_private( $cache, 'local_cache', $local );

		// A value larger than all of APCu's memory is refused.
		$too_big = str_repeat( 'x', 80 * 1024 * 1024 );
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- APCu warns that it cannot allocate memory.
			static function () {
				return true;
			}
		);
		$result = wp_cache_set_multiple( array( 'key' => $too_big ), 'posts' );
		restore_error_handler();

		$this->assertSame( array( 'key' => false ), $result );
		$this->assertSame( 'in-apcu', wp_cache_get( 'key', 'posts' ), 'The read must ask APCu, not trust the old local copy.' );
	}
}
