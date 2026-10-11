<?php
/**
 * Tests for incrementing and decrementing.
 *
 * @package object-cache-apcu
 */

/**
 * Same rules as core: a non numeric value counts as 0, a negative offset goes
 * the other way, the result never goes below 0 and a missing key is false
 * (issue #27).
 */
class Test_Incr_Decr extends APCu_Test_Case {

	/**
	 * Increments by the offset and by one by default.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_incr( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', 5, $group );

		$this->assertSame( 6, wp_cache_incr( 'n', 1, $group ) );
		$this->assertSame( 9, wp_cache_incr( 'n', 3, $group ) );
		$this->assertSame( 9, wp_cache_get( 'n', $group ) );
	}

	/**
	 * Decrements by the offset.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_decr( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', 5, $group );

		$this->assertSame( 4, wp_cache_decr( 'n', 1, $group ) );
		$this->assertSame( 1, wp_cache_decr( 'n', 3, $group ) );
		$this->assertSame( 1, wp_cache_get( 'n', $group ) );
	}

	/**
	 * Decrementing stops at zero.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_decr_stops_at_zero( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', 2, $group );

		$this->assertSame( 0, wp_cache_decr( 'n', 5, $group ) );
		$this->assertSame( 0, wp_cache_decr( 'n', 1, $group ) );
		$this->assertSame( 0, wp_cache_get( 'n', $group ) );
	}

	/**
	 * A negative offset moves the value the other way.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_negative_offsets( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', 5, $group );

		$this->assertSame( 3, wp_cache_incr( 'n', -2, $group ) );
		$this->assertSame( 6, wp_cache_decr( 'n', -3, $group ) );
		$this->assertSame( 0, wp_cache_incr( 'n', -100, $group ), 'incr with a negative offset also stops at zero' );
	}

	/**
	 * A missing key is not created.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_missing_key_returns_false( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );

		$this->assertFalse( wp_cache_incr( 'missing', 1, $group ) );
		$this->assertFalse( wp_cache_decr( 'missing', 1, $group ) );
		$this->assertFalse( wp_cache_get( 'missing', $group ) );
	}

	/**
	 * A value that is not a number counts as zero.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_non_numeric_value_counts_as_zero( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );

		wp_cache_set( 'text', 'abc', $group );
		$this->assertSame( 3, wp_cache_incr( 'text', 3, $group ) );

		wp_cache_set( 'list', array( 1 ), $group );
		$this->assertSame( 0, wp_cache_decr( 'list', 3, $group ) );
	}

	/**
	 * A number stored as a string is still a number.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_numeric_string_value( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'n', '7', $group );

		$this->assertSame( 9, wp_cache_incr( 'n', 2, $group ) );
	}

	/**
	 * Another request sees the new value, and an increment starts from what
	 * another request stored, not from this request's copy.
	 */
	public function test_value_is_shared_between_requests() {
		$cache = $this->new_request();
		wp_cache_set( 'n', 1, 'posts' );
		wp_cache_incr( 'n', 1, 'posts' );

		// Another process increments behind this request's back.
		apcu_inc( $this->call_private( $cache, 'build_key', array( 'n', 'posts' ) ), 10 );

		$this->assertSame( 13, wp_cache_incr( 'n', 1, 'posts' ) );
		$this->new_request();
		$this->assertSame( 13, wp_cache_get( 'n', 'posts' ) );
	}
}
