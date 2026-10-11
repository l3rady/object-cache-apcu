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
}
