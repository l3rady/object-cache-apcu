<?php
/**
 * Tests for reading, in particular the $force argument.
 *
 * @package object-cache-apcu
 */

/**
 * A forced read goes to APCu instead of the copy this request keeps (issue #29).
 */
class Test_Get extends APCu_Test_Case {

	/**
	 * Simulates another process changing a value in APCu behind this request's back.
	 *
	 * @param WP_Object_Cache $cache Cache in use.
	 * @param string          $key   Cache key.
	 * @param string          $group Cache group.
	 * @param mixed           $value New value; null deletes the item.
	 */
	private function change_elsewhere( $cache, $key, $group, $value ) {
		$apcu_key = $this->call_private( $cache, 'build_key', array( $key, $group ) );

		if ( null === $value ) {
			apcu_delete( $apcu_key );
		} else {
			apcu_store( $apcu_key, $value );
		}
	}

	/**
	 * A forced read sees the other process's value, an unforced one keeps the local copy.
	 */
	public function test_force_sees_changes_made_elsewhere() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'ours', 'posts' );
		$this->change_elsewhere( $cache, 'key', 'posts', 'theirs' );

		$this->assertSame( 'ours', wp_cache_get( 'key', 'posts' ), 'An unforced read may use the local copy.' );
		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts', true ), 'A forced read must go to APCu.' );
	}

	/**
	 * A forced read refreshes the local copy for the reads after it.
	 */
	public function test_force_refreshes_the_local_copy() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'ours', 'posts' );
		$this->change_elsewhere( $cache, 'key', 'posts', 'theirs' );

		wp_cache_get( 'key', 'posts', true );

		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * An item deleted elsewhere is a miss when forced, and stays a miss afterwards.
	 */
	public function test_force_sees_deletions_made_elsewhere() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'ours', 'posts' );
		$this->change_elsewhere( $cache, 'key', 'posts', null );

		$this->assertFalse( wp_cache_get( 'key', 'posts', true, $found ) );
		$this->assertFalse( $found );
		$this->assertFalse( wp_cache_get( 'key', 'posts', false, $found ), 'The stale local copy must be dropped.' );
		$this->assertFalse( $found );
	}

	/**
	 * Forced reads of several keys.
	 */
	public function test_get_multiple_honours_force() {
		$cache = $this->new_request();
		wp_cache_set_multiple(
			array(
				'a' => 1,
				'b' => 2,
			),
			'posts'
		);
		$this->change_elsewhere( $cache, 'a', 'posts', 10 );

		$this->assertSame(
			array(
				'a' => 1,
				'b' => 2,
			),
			wp_cache_get_multiple( array( 'a', 'b' ), 'posts' )
		);
		$this->assertSame(
			array(
				'a' => 10,
				'b' => 2,
			),
			wp_cache_get_multiple( array( 'a', 'b' ), 'posts', true )
		);
	}

	/**
	 * A forced read still works when there is no local cache.
	 */
	public function test_force_without_a_local_cache() {
		$cache = $this->new_request( true, false );
		wp_cache_set( 'key', 'ours', 'posts' );
		$this->change_elsewhere( $cache, 'key', 'posts', 'theirs' );

		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts', true ) );
		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * Memory-only groups have nothing else to read, so force changes nothing.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_force_returns_the_same_value_everywhere( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'value', $group );

		$this->assertSame( 'value', wp_cache_get( 'key', $group, true, $found ) );
		$this->assertTrue( $found );
	}
}
