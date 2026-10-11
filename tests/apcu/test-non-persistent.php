<?php
/**
 * Tests for the in-memory storage used by non persistent groups and when APCu is unavailable.
 *
 * @package object-cache-apcu
 */

/**
 * Writes report true or false, never the stored value (issue #26).
 */
class Test_Non_Persistent extends APCu_Test_Case {

	/**
	 * Groups that use the in-memory storage: a non persistent group, or any group without APCu.
	 *
	 * @return array
	 */
	public function in_memory_scenarios() {
		return array(
			'non persistent group' => array( true, 'np' ),
			'no apcu'              => array( false, 'posts' ),
		);
	}

	/**
	 * Values that are falsy in PHP but perfectly good to cache.
	 *
	 * @return array
	 */
	public function falsy_values() {
		return array(
			'zero'         => array( 0 ),
			'float zero'   => array( 0.0 ),
			'empty string' => array( '' ),
			'string zero'  => array( '0' ),
			'empty array'  => array( array() ),
			'false'        => array( false ),
			'null'         => array( null ),
		);
	}

	/**
	 * Set reports success for any value.
	 *
	 * @dataProvider in_memory_scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param string $group Group to test.
	 */
	public function test_set_returns_true_for_falsy_values( $apcu, $group ) {
		$this->new_request( $apcu );

		foreach ( $this->falsy_values() as $name => $args ) {
			$this->assertTrue( wp_cache_set( 'key-' . $name, $args[0], $group ), $name );
			$this->assertSame( $args[0], wp_cache_get( 'key-' . $name, $group, false, $found ), $name );
			$this->assertTrue( $found, $name );
		}
	}

	/**
	 * Add reports success for any value, and false when the key already exists.
	 *
	 * @dataProvider in_memory_scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param string $group Group to test.
	 */
	public function test_add_returns_true_for_falsy_values( $apcu, $group ) {
		$this->new_request( $apcu );

		foreach ( $this->falsy_values() as $name => $args ) {
			$this->assertTrue( wp_cache_add( 'key-' . $name, $args[0], $group ), $name );
			$this->assertFalse( wp_cache_add( 'key-' . $name, 'other', $group ), $name . ' again' );
		}
	}

	/**
	 * Replace reports success for any value, and false when the key is missing.
	 *
	 * @dataProvider in_memory_scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param string $group Group to test.
	 */
	public function test_replace_returns_true_for_falsy_values( $apcu, $group ) {
		$this->new_request( $apcu );
		wp_cache_set( 'key', 'something', $group );

		foreach ( $this->falsy_values() as $name => $args ) {
			$this->assertTrue( wp_cache_replace( 'key', $args[0], $group ), $name );
		}

		$this->assertFalse( wp_cache_replace( 'missing', 0, $group ) );
	}

	/**
	 * The multiple key functions report true per key.
	 *
	 * @dataProvider in_memory_scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param string $group Group to test.
	 */
	public function test_multiple_functions_return_true_for_falsy_values( $apcu, $group ) {
		$this->new_request( $apcu );
		$data = array(
			'zero'  => 0,
			'empty' => '',
			'none'  => array(),
		);

		$expected = array(
			'zero'  => true,
			'empty' => true,
			'none'  => true,
		);

		$this->assertSame( $expected, wp_cache_add_multiple( $data, $group ) );
		$this->assertSame( $expected, wp_cache_set_multiple( $data, $group ) );
	}

	/**
	 * A counter at zero is a number, not a failure.
	 *
	 * @dataProvider in_memory_scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param string $group Group to test.
	 */
	public function test_zero_is_distinguishable_from_failure( $apcu, $group ) {
		$this->new_request( $apcu );
		wp_cache_set( 'n', 1, $group );

		$this->assertSame( 0, wp_cache_decr( 'n', 1, $group ) );
		$this->assertFalse( wp_cache_decr( 'missing', 1, $group ) );
	}
}
