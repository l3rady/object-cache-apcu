<?php
/**
 * Tests for wp_cache_replace().
 *
 * @package object-cache-apcu
 */

/**
 * Replace must only write when the key already exists (issue #22).
 */
class Test_Replace extends APCu_Test_Case {

	/**
	 * Replacing a key that does not exist fails and does not create it.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu       Use APCu.
	 * @param bool   $local      Use the local cache.
	 * @param string $group      Cache group.
	 * @param bool   $persistent Whether the group is stored in APCu.
	 */
	public function test_replace_missing_key_fails_and_does_not_create_it( $apcu, $local, $group, $persistent ) {
		$this->new_request( $apcu, $local );

		$this->assertFalse( wp_cache_replace( 'key', 'value', $group ) );

		$this->assertFalse( wp_cache_get( 'key', $group, false, $found ) );
		$this->assertFalse( $found, 'The failed replace must not have created the key.' );
	}

	/**
	 * Replacing an existing key succeeds and stores the new value.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu       Use APCu.
	 * @param bool   $local      Use the local cache.
	 * @param string $group      Cache group.
	 * @param bool   $persistent Whether the group is stored in APCu.
	 */
	public function test_replace_existing_key_updates_it( $apcu, $local, $group, $persistent ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'first', $group );

		$result = wp_cache_replace( 'key', 'second', $group );

		$this->assertNotFalse( $result );
		if ( $apcu && $persistent ) {
			// Non persistent groups return the stored value instead of true: issue #26.
			$this->assertTrue( $result );
		}
		$this->assertSame( 'second', wp_cache_get( 'key', $group ) );
	}

	/**
	 * A falsy value is a real value, so replacing with one works.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu       Use APCu.
	 * @param bool   $local      Use the local cache.
	 * @param string $group      Cache group.
	 * @param bool   $persistent Whether the group is stored in APCu.
	 */
	public function test_replace_with_a_falsy_value( $apcu, $local, $group, $persistent ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'first', $group );

		wp_cache_replace( 'key', 0, $group );

		$this->assertSame( 0, wp_cache_get( 'key', $group, false, $found ) );
		$this->assertTrue( $found );
	}

	/**
	 * Replacing a key that was deleted fails.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu       Use APCu.
	 * @param bool   $local      Use the local cache.
	 * @param string $group      Cache group.
	 * @param bool   $persistent Whether the group is stored in APCu.
	 */
	public function test_replace_after_delete_fails( $apcu, $local, $group, $persistent ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'first', $group );
		wp_cache_delete( 'key', $group );

		$this->assertFalse( wp_cache_replace( 'key', 'second', $group ) );
	}

	/**
	 * A value written by replace is visible to the next request.
	 */
	public function test_replaced_value_is_persisted_in_apcu() {
		$this->new_request();
		wp_cache_set( 'key', 'first', 'posts' );
		wp_cache_replace( 'key', 'second', 'posts' );

		$this->new_request();

		$this->assertSame( 'second', wp_cache_get( 'key', 'posts' ) );
	}
}
