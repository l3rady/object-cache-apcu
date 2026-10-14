<?php
/**
 * Tests for wp_cache_flush() and purge().
 *
 * @package object-cache-apcu
 */

/**
 * Issue #34: flushing empties this install's cache, not the whole of APCu.
 */
class Test_Flush_All extends APCu_Test_Case {

	/**
	 * Everything this install stored is gone afterwards, in every configuration.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_flush_removes_all_of_this_installs_data( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'a', 1, $group );
		wp_cache_set( 'b', 2, 'comments' );
		wp_cache_set( 'u', 3, 'global' );
		wp_cache_set( 'n', 4, 'np' );

		$this->assertTrue( wp_cache_flush() );

		$this->assertFalse( wp_cache_get( 'a', $group ) );
		$this->assertFalse( wp_cache_get( 'b', 'comments' ) );
		$this->assertFalse( wp_cache_get( 'u', 'global' ) );
		$this->assertFalse( wp_cache_get( 'n', 'np' ) );

		// A later request must not find the old data either.
		$this->new_request( $apcu, $local );
		$this->assertFalse( wp_cache_get( 'a', $group ) );
		$this->assertFalse( wp_cache_get( 'u', 'global' ) );
	}

	/**
	 * The cache is usable straight after a flush and the new data is what later requests see.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_writes_after_a_flush_are_kept( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'a', 'old', $group );

		wp_cache_flush();
		wp_cache_set( 'a', 'new', $group );

		$this->assertSame( 'new', wp_cache_get( 'a', $group ) );
		$this->new_request( $apcu, $local );
		$this->assertSame( $apcu && 'np' !== $group ? 'new' : false, wp_cache_get( 'a', $group ) );
	}

	/**
	 * Other applications in the same APCu keep their data.
	 */
	public function test_flush_leaves_other_applications_alone() {
		$this->new_request();
		apcu_store( 'someone-elses-key', 'theirs' );
		wp_cache_set( 'a', 1, 'posts' );

		wp_cache_flush();

		$this->assertSame( 'theirs', apcu_fetch( 'someone-elses-key' ) );
	}

	/**
	 * Another install with a different salt keeps its data, and its group versions too.
	 */
	public function test_flush_leaves_another_install_alone() {
		$ours = $this->new_request();
		$this->set_private( $ours, 'key_salt', 'install-one' );
		wp_cache_set( 'a', 'ours', 'posts' );

		$theirs = new WP_Object_Cache();
		$this->set_private( $theirs, 'key_salt', 'install-two' );
		$theirs->set( 'a', 'theirs', 'posts' );

		wp_cache_flush();

		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );

		$fresh = new WP_Object_Cache();
		$this->set_private( $fresh, 'key_salt', 'install-two' );
		$this->assertSame( 'theirs', $fresh->get( 'a', 'posts' ) );
	}

	/**
	 * Flushing does not delete anything: old entries wait for APCu to remove them.
	 */
	public function test_flush_does_not_delete_entries() {
		$this->new_request();
		wp_cache_set( 'a', 1, 'posts' );
		$before = apcu_cache_info( true )['num_entries'];

		wp_cache_flush();

		$this->assertGreaterThanOrEqual( $before, apcu_cache_info( true )['num_entries'] );
	}

	/**
	 * Flushing a group or site still works after a flush, as they sit under the epoch.
	 */
	public function test_group_flush_still_works_after_a_full_flush() {
		$this->new_request();
		wp_cache_flush();
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_set( 'b', 2, 'comments' );

		wp_cache_flush_group( 'posts' );

		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );
		$this->assertSame( 2, wp_cache_get( 'b', 'comments' ) );
	}

	/**
	 * Clearing the runtime cache must not look like a flush.
	 */
	public function test_flush_runtime_keeps_the_data() {
		$this->new_request();
		wp_cache_set( 'a', 1, 'posts' );

		wp_cache_flush_runtime();

		$this->assertSame( 1, wp_cache_get( 'a', 'posts' ) );
	}

	/**
	 * A request that clears its memory notices a flush another request made meanwhile.
	 */
	public function test_flush_runtime_notices_a_flush_by_another_request() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 1, 'posts' );

		$other = new WP_Object_Cache();
		$other->flush();

		$this->assertSame( 1, wp_cache_get( 'a', 'posts' ), 'Within a request the cached epoch is used.' );
		wp_cache_flush_runtime();
		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );
		$this->assertSame( $cache, $GLOBALS['wp_object_cache'] );
	}

	/**
	 * Purging deletes this install's entries, versions included, and nothing else.
	 */
	public function test_purge_deletes_only_this_installs_entries() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_set( 'b', 2, 'comments' );
		apcu_store( 'someone-elses-key', 'theirs' );

		$other = new WP_Object_Cache();
		$this->set_private( $other, 'key_salt', 'another' );
		$other->set( 'a', 'theirs', 'posts' );

		$deleted = $cache->purge();

		$this->assertGreaterThanOrEqual( 2, $deleted );
		foreach ( new APCUIterator( '/^wp:/', APC_ITER_KEY ) as $key => $unused ) {
			$this->fail( 'Entry left behind: ' . $key );
		}
		$this->assertSame( 'theirs', apcu_fetch( 'someone-elses-key' ) );
		$this->assertSame( 'theirs', $other->get( 'a', 'posts', true ) );
		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );
	}

	/**
	 * Without APCu there is nothing to purge.
	 */
	public function test_purge_without_apcu() {
		$cache = $this->new_request( false );

		$this->assertSame( 0, $cache->purge() );
	}

	/**
	 * With the hard setting, flush() also deletes the old entries.
	 */
	public function test_hard_flush_deletes_the_old_entries() {
		$cache = $this->new_request();
		$this->set_private( $cache, 'flush_hard', true );
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_set( 'b', 2, 'comments' );
		apcu_store( 'someone-elses-key', 'theirs' );

		$cache->flush();

		$remaining = array();
		foreach ( new APCUIterator( '/^wp:/', APC_ITER_KEY ) as $key => $unused ) {
			$remaining[] = $key;
		}
		$this->assertCount( 1, $remaining, 'Only the new epoch should be left.' );
		$this->assertStringContainsString( ':Epoch:', $remaining[0] );
		$this->assertSame( 'theirs', apcu_fetch( 'someone-elses-key' ) );
		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );
	}

	/**
	 * WP_APCU_FLUSH_HARD turns the hard flush on.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_flush_hard_constant() {
		define( 'WP_APCU_FLUSH_HARD', true );

		$this->assertTrue( $this->get_private( $this->new_request(), 'flush_hard' ) );
	}

	/**
	 * It is off unless asked for.
	 */
	public function test_flush_hard_is_off_by_default() {
		$this->assertFalse( $this->get_private( $this->new_request(), 'flush_hard' ) );
	}

	/**
	 * Without APCu nothing is purged, even when APCu holds entries with this install's key.
	 */
	public function test_purge_without_apcu_leaves_apcu_alone() {
		$cache = $this->new_request();
		$cache->set( 'a', 1, 'posts' );
		$this->set_private( $cache, 'apcu_available', false );

		$this->assertSame( 0, $cache->purge() );

		$this->set_private( $cache, 'apcu_available', true );
		$this->assertSame( 1, $cache->get( 'a', 'posts', true ) );
	}

	/**
	 * Purging an install that has nothing stored reports that nothing was deleted.
	 */
	public function test_purge_with_nothing_to_delete_returns_zero() {
		$cache = $this->new_request();

		$this->assertSame( 0, $cache->purge() );
	}

	/**
	 * A key salt made of regular expression characters is matched as text, and only
	 * the install with exactly that salt and path is purged.
	 */
	public function test_purge_matches_the_salt_literally() {
		$cache = $this->new_request();
		$this->set_private( $cache, 'key_salt', 'a.b+c(d)' );
		$cache->set( 'k', 'mine', 'posts' );

		$base = $this->get_private( $cache, 'abs_path' );

		// Installs whose keys a pattern built without quoting, or without the final colon, would also match.
		$lookalike = new WP_Object_Cache();
		$this->set_private( $lookalike, 'key_salt', 'abbcd' );
		$lookalike->set( 'k', 'lookalike', 'posts' );
		$longer = new WP_Object_Cache();
		$this->set_private( $longer, 'key_salt', 'a.b+c(d):' . $base . 'more' );
		$longer->set( 'k', 'longer', 'posts' );

		$this->assertGreaterThanOrEqual( 2, $cache->purge() );

		$this->assertFalse( $cache->get( 'k', 'posts', true ) );
		$this->assertSame( 'lookalike', $lookalike->get( 'k', 'posts', true ) );
		$this->assertSame( 'longer', $longer->get( 'k', 'posts', true ) );
	}
}
