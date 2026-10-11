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
}
