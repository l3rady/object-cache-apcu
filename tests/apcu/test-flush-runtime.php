<?php
/**
 * Tests for clearing the runtime cache.
 *
 * @package object-cache-apcu
 */

/**
 * The runtime cache is this request's memory only; APCu must never be touched (issue #25).
 */
class Test_Flush_Runtime extends APCu_Test_Case {

	/**
	 * Persistent data survives, whether or not the local cache is on.
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $local Use the local cache.
	 */
	public function test_persistent_data_survives( $local ) {
		$this->new_request( true, $local );
		wp_cache_set( 'key', 'value', 'posts' );
		wp_cache_set( 'other', 'value', 'comments' );

		$this->assertTrue( wp_cache_flush_runtime() );

		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'value', wp_cache_get( 'other', 'comments' ) );
		$this->assertGreaterThan( 0, apcu_cache_info( true )['num_entries'], 'APCu itself must still hold the data.' );
	}

	/**
	 * Other things stored in APCu by other software are left alone.
	 */
	public function test_unrelated_apcu_entries_survive() {
		$this->new_request( true, false );
		apcu_store( 'someone-elses-key', 'theirs' );

		wp_cache_flush_runtime();

		$this->assertSame( 'theirs', apcu_fetch( 'someone-elses-key' ) );
	}

	/**
	 * The request's own memory is emptied.
	 */
	public function test_in_memory_state_is_cleared() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );
		wp_cache_set( 'np-key', 'value', 'np' );

		wp_cache_flush_runtime();

		$this->assertSame( array(), $this->get_private( $cache, 'local_cache' ) );
		$this->assertSame( array(), $cache->get_non_persistent_cache() );
		$this->assertSame( array(), $cache->get_group_versions() );
		$this->assertSame( array(), $cache->get_site_versions() );
		$this->assertFalse( wp_cache_get( 'np-key', 'np' ), 'Non persistent groups only live in memory.' );
	}

	/**
	 * A flush done by another request is seen once the version memos are dropped.
	 */
	public function test_runtime_flush_picks_up_flushes_made_elsewhere() {
		$this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );

		// Another request flushes the group; this one still remembers the old version.
		$other = new WP_Object_Cache();
		$other->flush_groups( 'posts' );
		$this->assertSame( 'value', wp_cache_get( 'key', 'posts', true ), 'Without a runtime flush the old version is still in use.' );

		wp_cache_flush_runtime();

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * Without APCu everything lives in memory, so this empties the cache, versions included.
	 */
	public function test_without_apcu_everything_in_memory_is_cleared() {
		$this->new_request( false );
		wp_cache_set( 'key', 'value', 'posts' );
		wp_cache_flush_group( 'posts' );
		wp_cache_set( 'key', 'value', 'posts' );

		wp_cache_flush_runtime();

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertTrue( wp_cache_set( 'key', 'again', 'posts' ) );
		$this->assertSame( 'again', wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * Local cache on and off.
	 *
	 * @return array
	 */
	public function apcu_scenarios() {
		return array(
			'local cache on'  => array( true ),
			'local cache off' => array( false ),
		);
	}
}
