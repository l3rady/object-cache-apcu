<?php
/**
 * Tests for values WordPress and plugins pass in as the wrong type.
 *
 * @package object-cache-apcu
 */

/**
 * Callers are not strict about types, so strings and floats arrive where numbers
 * are expected; the typed internals must cope as the untyped ones did (issue #21).
 */
class Test_Input_Types extends APCu_Test_Case {

	/**
	 * Expiry times that are not integers.
	 *
	 * @return array
	 */
	public function expiry_values() {
		return array(
			'numeric string' => array( '60' ),
			'float'          => array( 60.5 ),
			'empty string'   => array( '' ),
			'null'           => array( null ),
			'negative'       => array( -5 ),
		);
	}

	/**
	 * Every write function accepts them.
	 *
	 * @dataProvider expiry_values
	 *
	 * @param mixed $expire Expiry to try.
	 */
	public function test_writes_accept_loose_expiry_values( $expire ) {
		$this->new_request();

		$this->assertTrue( wp_cache_set( 'a', 1, 'posts', $expire ) );
		$this->assertTrue( wp_cache_add( 'b', 1, 'posts', $expire ) );
		$this->assertTrue( wp_cache_replace( 'a', 2, 'posts', $expire ) );
		$this->assertSame( 2, wp_cache_get( 'a', 'posts' ) );
	}

	/**
	 * Offsets that are not integers are cast, as before.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_offsets_are_cast_to_integers( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', 10, $group );

		$this->assertSame( 12, wp_cache_incr( 'n', '2', $group ) );
		$this->assertSame( 13, wp_cache_incr( 'n', 1.9, $group ) );
		$this->assertSame( 13, wp_cache_incr( 'n', 'abc', $group ), 'Not a number counts as 0.' );
		$this->assertSame( 12, wp_cache_decr( 'n', '1', $group ) );
		$this->assertSame( 12, wp_cache_decr( 'n', null, $group ) );
	}

	/**
	 * Group names and blog ids that are not what core documents.
	 */
	public function test_odd_groups_and_blog_ids() {
		$GLOBALS['apcu_test_multisite'] = true;
		$cache                          = $this->new_request();

		$cache->switch_to_blog( '3' );
		$this->assertTrue( wp_cache_set( 'k', 'v', '' ), 'An empty group is the default group.' );
		$this->assertSame( 'v', wp_cache_get( 'k', 'default' ) );
		$this->assertSame( 3, $this->get_private( $cache, 'blog_prefix' ), 'A string blog id becomes an integer.' );
	}
}
