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
}
