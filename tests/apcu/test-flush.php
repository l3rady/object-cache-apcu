<?php
/**
 * Tests for flushing groups and sites.
 *
 * @package object-cache-apcu
 */

/**
 * Flushing bumps a version counter that is part of every key, so data written
 * before the flush becomes unreachable (issue #23).
 */
class Test_Flush extends APCu_Test_Case {

	/**
	 * Group scenarios that are stored in APCu.
	 *
	 * @return array
	 */
	public function apcu_scenarios() {
		return array(
			'apcu, local cache'    => array( true, true ),
			'apcu, no local cache' => array( true, false ),
		);
	}

	/**
	 * The in-memory fallback has no versions, so flushing the group clears it directly.
	 */
	public function test_flush_group_without_apcu() {
		$this->new_request( false );
		wp_cache_set( 'key', 'old', 'posts' );

		$this->assertTrue( wp_cache_flush_group( 'posts' ) );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * Flushed data is gone and the group is immediately usable again in the same request.
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $apcu  Use APCu.
	 * @param bool $local Use the local cache.
	 */
	public function test_flush_group_then_write_in_the_same_request( $apcu, $local ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'old', 'posts' );
		wp_cache_set( 'other', 'kept', 'comments' );

		$this->assertTrue( wp_cache_flush_group( 'posts' ) );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ), 'Flushed data must be gone.' );
		$this->assertTrue( wp_cache_set( 'key', 'new', 'posts' ) );
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ), 'Data written after the flush must be readable.' );
		$this->assertSame( 'kept', wp_cache_get( 'other', 'comments' ), 'Other groups are untouched.' );

		// A later request must see the new value, not the flushed one.
		$this->new_request( $apcu, $local );
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ), 'Data written after the flush must be stored under the new version.' );
	}

	/**
	 * Data written after a flush must be flushed by the next flush, not leak back.
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $apcu  Use APCu.
	 * @param bool $local Use the local cache.
	 */
	public function test_flushing_twice_in_one_request( $apcu, $local ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'one', 'posts' );

		wp_cache_flush_group( 'posts' );
		wp_cache_set( 'key', 'two', 'posts' );
		wp_cache_flush_group( 'posts' );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );

		$this->new_request( $apcu, $local );
		$this->assertFalse( wp_cache_get( 'key', 'posts' ), 'Neither earlier value may come back.' );
	}

	/**
	 * Several groups can be flushed in one call.
	 */
	public function test_flush_several_groups() {
		$this->new_request();
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_set( 'b', 2, 'comments' );
		wp_cache_set( 'c', 3, 'options' );

		$this->assertTrue( wp_cache_flush_group( array( 'posts', 'comments' ) ) );
		wp_cache_set( 'a', 'new', 'posts' );

		$this->assertSame( 'new', wp_cache_get( 'a', 'posts' ) );
		$this->assertFalse( wp_cache_get( 'b', 'comments' ) );
		$this->assertSame( 3, wp_cache_get( 'c', 'options' ) );
	}

	/**
	 * Flushing an empty list of groups does nothing and reports it.
	 */
	public function test_flush_no_groups_returns_false() {
		$cache = $this->new_request();

		$this->assertFalse( $cache->flush_groups( array() ) );
	}

	/**
	 * Flushing the site flushes its groups and the global groups, and is usable straight away.
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $apcu  Use APCu.
	 * @param bool $local Use the local cache.
	 */
	public function test_flush_site_then_write_in_the_same_request( $apcu, $local ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'old', 'posts' );
		wp_cache_set( 'shared', 'old', 'global' );

		$this->assertTrue( wp_cache_flush_site() );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertFalse( wp_cache_get( 'shared', 'global' ) );

		wp_cache_set( 'key', 'new', 'posts' );
		wp_cache_set( 'shared', 'new', 'global' );
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'new', wp_cache_get( 'shared', 'global' ) );

		$this->new_request( $apcu, $local );
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'new', wp_cache_get( 'shared', 'global' ) );
	}

	/**
	 * Flushing one site leaves the others alone.
	 */
	public function test_flush_site_leaves_other_sites_alone() {
		$GLOBALS['apcu_test_multisite'] = true;
		$cache                          = $this->new_request();
		$cache->switch_to_blog( 1 );
		wp_cache_set( 'key', 'site one', 'posts' );
		$cache->switch_to_blog( 2 );
		wp_cache_set( 'key', 'site two', 'posts' );

		wp_cache_flush_site( 2 );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ), 'Site two is flushed.' );
		$cache->switch_to_blog( 1 );
		$this->assertSame( 'site one', wp_cache_get( 'key', 'posts' ), 'Site one is not.' );
	}

	/**
	 * Data flushed with flush_group must not come back if APCu loses the version key.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/24
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $apcu  Use APCu.
	 * @param bool $local Use the local cache.
	 */
	public function test_flushed_data_does_not_return_when_the_version_key_is_evicted( $apcu, $local ) {
		$cache = $this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'written before the flush', 'posts' );
		wp_cache_flush_group( 'posts' );

		// APCu evicts the group's version key under memory pressure.
		apcu_delete( $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) ) );
		$this->new_request( $apcu, $local );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ), 'Flushed data must stay flushed.' );
	}

	/**
	 * Data written before the version key was evicted is not served under the new version.
	 */
	public function test_data_is_not_served_under_a_new_version_after_eviction() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'old', 'posts' );

		apcu_delete( $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) ) );
		$this->new_request();

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertTrue( wp_cache_set( 'key', 'new', 'posts' ) );
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ) );

		$this->new_request();
		$this->assertSame( 'new', wp_cache_get( 'key', 'posts' ), 'The new version is kept for later requests.' );
	}

	/**
	 * Flushed site data must not come back if APCu loses the site version key.
	 *
	 * @dataProvider apcu_scenarios
	 *
	 * @param bool $apcu  Use APCu.
	 * @param bool $local Use the local cache.
	 */
	public function test_flushed_site_data_does_not_return_when_the_version_key_is_evicted( $apcu, $local ) {
		$cache = $this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'old', 'posts' );
		wp_cache_set( 'shared', 'old', 'global' );
		wp_cache_flush_site();

		// Every site version this request remembers is evicted.
		foreach ( array_keys( $cache->get_site_versions() ) as $site ) {
			apcu_delete( $this->call_private( $cache, 'get_cache_version_key', array( 'SiteVersion', $site ) ) );
		}
		$this->new_request( $apcu, $local );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertFalse( wp_cache_get( 'shared', 'global' ) );
	}

	/**
	 * On multisite an evicted site version does not bring back the flushed site's data.
	 */
	public function test_flushed_multisite_data_does_not_return_when_the_site_version_is_evicted() {
		$GLOBALS['apcu_test_multisite'] = true;
		$cache                          = $this->new_request();
		$cache->switch_to_blog( 2 );
		wp_cache_set( 'key', 'site two', 'posts' );
		wp_cache_flush_site( 2 );

		foreach ( array_keys( $cache->get_site_versions() ) as $site ) {
			apcu_delete( $this->call_private( $cache, 'get_cache_version_key', array( 'SiteVersion', $site ) ) );
		}

		$cache = $this->new_request();
		$cache->switch_to_blog( 2 );
		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * A flush must invalidate data even if the flushing request remembers an older version than the
	 * one in APCu, because another request flushed in between.
	 */
	public function test_flush_from_a_stale_version_still_invalidates() {
		$first = $this->new_request();
		$first->set( 'key', 'first', 'posts' );

		// Another request flushes and writes under the new version.
		$second = $this->new_request();
		$second->flush_groups( 'posts' );
		$second->set( 'key', 'second', 'posts' );

		// The first request still remembers the old version. Its flush must not be a no-op.
		$first->flush_groups( 'posts' );

		$third = $this->new_request();
		$this->assertFalse( $third->get( 'key', 'posts' ), 'The stale flush must invalidate the second request\'s data.' );
	}

	/**
	 * The same for sites.
	 */
	public function test_site_flush_from_a_stale_version_still_invalidates() {
		$first = $this->new_request();
		$first->set( 'key', 'first', 'posts' );

		$second = $this->new_request();
		$second->flush_sites( array() );
		$second->set( 'key', 'second', 'posts' );

		$first->flush_sites( array() );

		$third = $this->new_request();
		$this->assertFalse( $third->get( 'key', 'posts' ) );
	}

	/**
	 * Two requests that both find the version missing must agree on one version.
	 */
	public function test_requests_creating_a_missing_version_agree() {
		$first  = $this->new_request();
		$second = $this->new_request();

		$first->set( 'key', 'shared', 'posts' );

		$this->assertSame( 'shared', $second->get( 'key', 'posts' ), 'The version created first is used by the other request.' );
		$this->assertSame( $first->get_group_versions(), $second->get_group_versions() );
	}

	/**
	 * A version created by another request between our read and our add wins.
	 */
	public function test_version_created_concurrently_is_adopted() {
		$cache = $this->new_request();
		$key   = $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) );

		// Another request creates the version first.
		apcu_add( $key, 'created-elsewhere' );

		$this->assertSame( 'created-elsewhere', $this->call_private( $cache, 'get_group_cache_version', array( 'posts' ) ) );
	}

	/**
	 * Versions are tokens: a later flush never repeats an earlier version.
	 */
	public function test_flush_mints_a_new_version_each_time() {
		$cache    = $this->new_request();
		$versions = array();

		for ( $i = 0; $i < 5; $i++ ) {
			$cache->flush_groups( 'posts' );
			$versions[] = $cache->get_group_versions()['posts'];
		}

		$this->assertCount( 5, array_unique( $versions ) );
	}

	/**
	 * A version number written by an earlier release is still accepted.
	 */
	public function test_an_integer_version_from_an_earlier_release_still_works() {
		$cache = $this->new_request();
		apcu_store( $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) ), 3 );

		$cache = $this->new_request();
		$this->assertTrue( wp_cache_set( 'key', 'value', 'posts' ) );
		$this->assertSame( '3', $cache->get_group_versions()['posts'] );

		$this->new_request();
		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ) );
	}
}
