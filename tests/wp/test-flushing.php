<?php
/**
 * Tests for flushing and for multisite behaviour, through WordPress's own functions.
 *
 * Run with `make test-wp` (single site) and `make test-wp-ms` (multisite). The
 * tests that need a network are skipped on a single site.
 *
 * @package object-cache-apcu
 */

/**
 * What each way of flushing removes, and what it must leave alone.
 */
class Test_Flushing extends WP_UnitTestCase {

	/**
	 * Skips a test that needs a multisite network.
	 */
	private function require_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only: run `make test-wp-ms`.' );
		}
	}

	/**
	 * Flushing a group removes that group and nothing else.
	 */
	public function test_flush_group_removes_only_that_group() {
		wp_cache_set( 'key', 'one', 'flush-group-a' );
		wp_cache_set( 'key', 'two', 'flush-group-b' );

		$this->assertTrue( wp_cache_flush_group( 'flush-group-a' ) );

		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );
		$this->assertSame( 'two', wp_cache_get( 'key', 'flush-group-b' ) );
	}

	/**
	 * The change is seen by a later request, not only by this one.
	 */
	public function test_a_flushed_group_stays_flushed_in_the_next_request() {
		wp_cache_set( 'key', 'one', 'flush-group-a' );
		wp_cache_flush_group( 'flush-group-a' );

		wp_cache_init();

		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );
	}

	/**
	 * Core's own flush function empties the object cache.
	 */
	public function test_wp_cache_flush_removes_the_cached_values() {
		wp_cache_set( 'key', 'one', 'flush-group-a' );

		$this->assertTrue( wp_cache_flush() );

		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );
	}

	/**
	 * Other applications that share this APCu keep their data when WordPress flushes.
	 */
	public function test_wp_cache_flush_leaves_other_apcu_data_alone() {
		apcu_store( 'another-application-key', 'still-here' );
		wp_cache_set( 'key', 'one', 'flush-group-a' );

		wp_cache_flush();

		$this->assertSame( 'still-here', apcu_fetch( 'another-application-key' ) );
		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );

		apcu_delete( 'another-application-key' );
	}

	/**
	 * Clearing only the in-request copy leaves APCu, and so the next read, intact.
	 */
	public function test_flush_runtime_does_not_flush_apcu() {
		apcu_store( 'another-application-key', 'still-here' );
		wp_cache_set( 'key', 'one', 'flush-group-a' );

		wp_cache_flush_runtime();

		$this->assertSame( 'still-here', apcu_fetch( 'another-application-key' ) );
		$this->assertSame( 'one', wp_cache_get( 'key', 'flush-group-a' ) );

		apcu_delete( 'another-application-key' );
	}

	/**
	 * Flushing one site leaves the others alone.
	 *
	 * @group ms-required
	 */
	public function test_flush_site_removes_only_that_site() {
		$this->require_multisite();

		$other_site = self::factory()->blog->create();

		wp_cache_set( 'key', 'site-one', 'flush-group-a' );
		switch_to_blog( $other_site );
		wp_cache_set( 'key', 'site-two', 'flush-group-a' );
		restore_current_blog();

		$this->assertTrue( wp_cache_flush_site( $other_site ) );

		$this->assertSame( 'site-one', wp_cache_get( 'key', 'flush-group-a' ) );
		switch_to_blog( $other_site );
		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );
		restore_current_blog();
	}

	/**
	 * Without an argument the current site is flushed.
	 *
	 * @group ms-required
	 */
	public function test_flush_site_without_an_argument_flushes_the_current_site() {
		$this->require_multisite();

		$other_site = self::factory()->blog->create();

		wp_cache_set( 'key', 'site-one', 'flush-group-a' );
		switch_to_blog( $other_site );
		wp_cache_set( 'key', 'site-two', 'flush-group-a' );

		$this->assertTrue( wp_cache_flush_site() );

		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ), 'The site switched to was flushed.' );
		restore_current_blog();
		$this->assertSame( 'site-one', wp_cache_get( 'key', 'flush-group-a' ) );
	}

	/**
	 * A flushed group is flushed on every site, as in core: the group is the unit, not the site.
	 *
	 * @group ms-required
	 */
	public function test_flush_group_flushes_the_group_on_every_site() {
		$this->require_multisite();

		$other_site = self::factory()->blog->create();

		wp_cache_set( 'key', 'site-one', 'flush-group-a' );
		wp_cache_set( 'key', 'kept', 'flush-group-b' );
		switch_to_blog( $other_site );
		wp_cache_set( 'key', 'site-two', 'flush-group-a' );

		wp_cache_flush_group( 'flush-group-a' );

		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ) );
		restore_current_blog();
		$this->assertFalse( wp_cache_get( 'key', 'flush-group-a' ), 'The group is flushed on the other site too.' );
		$this->assertSame( 'kept', wp_cache_get( 'key', 'flush-group-b' ), 'Other groups are untouched.' );
	}

	/**
	 * A non-persistent group is kept apart per site too.
	 *
	 * @group ms-required
	 */
	public function test_non_persistent_groups_are_separate_per_site() {
		$this->require_multisite();

		wp_cache_add_non_persistent_groups( 'memory-only' );
		$other_site = self::factory()->blog->create();

		wp_cache_set( 'key', 'site-one', 'memory-only' );
		switch_to_blog( $other_site );

		$this->assertFalse( wp_cache_get( 'key', 'memory-only' ) );

		wp_cache_set( 'key', 'site-two', 'memory-only' );
		restore_current_blog();

		$this->assertSame( 'site-one', wp_cache_get( 'key', 'memory-only' ) );
	}

	/**
	 * Switching site keeps working after a flush of everything.
	 *
	 * @group ms-required
	 */
	public function test_switching_site_still_separates_values_after_a_full_flush() {
		$this->require_multisite();

		$other_site = self::factory()->blog->create();
		wp_cache_flush();

		wp_cache_set( 'key', 'site-one', 'flush-group-a' );
		switch_to_blog( $other_site );
		wp_cache_set( 'key', 'site-two', 'flush-group-a' );
		restore_current_blog();

		$this->assertSame( 'site-one', wp_cache_get( 'key', 'flush-group-a' ) );
	}
}
