<?php
/**
 * Tests that document bugs which are still open.
 *
 * They are tagged `known-bug`, so they are skipped by `make test-apcu` and fail
 * on purpose under `make test-known-bugs`. The pull request that fixes a bug
 * removes its tag, which moves the test into the normal run.
 *
 * @package object-cache-apcu
 */

/**
 * Open bugs.
 *
 * @group known-bug
 */
class Test_Known_Bugs extends APCu_Test_Case {

	/**
	 * Set and add must return a bool for non persistent groups too.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/26
	 */
	public function test_non_persistent_set_and_add_return_booleans() {
		$this->new_request();

		$this->assertTrue( wp_cache_set( 'zero', 0, 'np' ), 'set() of 0' );
		$this->assertTrue( wp_cache_set( 'empty', array(), 'np' ), 'set() of an empty array' );
		$this->assertTrue( wp_cache_add( 'text', '', 'np' ), 'add() of an empty string' );
	}

	/**
	 * Decrementing must never go below zero, like core.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/27
	 */
	public function test_decr_stops_at_zero() {
		$this->new_request();
		wp_cache_set( 'counter', 0, 'posts' );
		wp_cache_set( 'counter', 0, 'np' );

		$this->assertSame( 0, wp_cache_decr( 'counter', 1, 'posts' ), 'APCu group' );
		$this->assertSame( 0, wp_cache_decr( 'counter', 1, 'np' ), 'non persistent group' );
	}

	/**
	 * A negative offset must move the value the other way, like core.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/27
	 */
	public function test_incr_accepts_a_negative_offset() {
		$this->new_request();
		wp_cache_set( 'counter', 5, 'posts' );

		$this->assertSame( 3, wp_cache_incr( 'counter', -2, 'posts' ) );
	}

	/**
	 * Data flushed with flush_group must not come back if APCu loses the version key.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/24
	 */
	public function test_flushed_data_does_not_return_when_the_version_key_is_evicted() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'written before the flush', 'posts' );
		wp_cache_flush_group( 'posts' );

		// APCu evicts the group's version key under memory pressure.
		apcu_delete( $this->call_private( $cache, 'get_cache_version_key', array( 'GroupVersion', 'posts' ) ) );
		$this->new_request();

		$this->assertFalse( wp_cache_get( 'key', 'posts' ), 'Flushed data must stay flushed.' );
	}

	/**
	 * Clearing the runtime cache must never touch APCu, even with the local cache off.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/25
	 */
	public function test_flush_runtime_keeps_apcu_data_when_the_local_cache_is_off() {
		$this->new_request( true, false );
		wp_cache_set( 'key', 'value', 'posts' );

		wp_cache_flush_runtime();

		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * A forced read must see what another process wrote to APCu.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/29
	 */
	public function test_force_bypasses_the_local_cache() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'ours', 'posts' );

		// Another process changes the value behind this request's back.
		apcu_store( $this->call_private( $cache, 'build_key', array( 'key', 'posts' ) ), 'theirs' );

		$this->assertSame( 'ours', wp_cache_get( 'key', 'posts' ), 'An unforced read may use the local copy.' );
		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts', true ), 'A forced read must go to APCu.' );
	}

	/**
	 * Empty and non-scalar keys are rejected, like core 6.1+.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/30
	 */
	public function test_invalid_keys_are_rejected() {
		$this->new_request();

		$this->assertFalse( wp_cache_set( '', 'value', 'posts' ), 'empty string' );
		$this->assertFalse( wp_cache_set( '   ', 'value', 'posts' ), 'blank string' );
		$this->assertFalse( wp_cache_set( null, 'value', 'posts' ), 'null' );
		$this->assertFalse( wp_cache_set( array( 'a' ), 'value', 'posts' ), 'array' );
	}

	/**
	 * The stats output lists groups, not full cache keys.
	 *
	 * @see https://github.com/l3rady/object-cache-apcu/issues/32
	 */
	public function test_stats_lists_groups() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );

		ob_start();
		$cache->stats();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<strong>Group:</strong> posts ', $output );
	}
}
