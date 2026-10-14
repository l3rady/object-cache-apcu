<?php
/**
 * Tests for cache key validation.
 *
 * @package object-cache-apcu
 */

/**
 * Keys must be integers or non-empty strings, as in core 6.1+ (issue #30).
 */
class Test_Keys extends APCu_Test_Case {

	/**
	 * Keys that are not allowed, with the type reported for them.
	 *
	 * @return array
	 */
	public function invalid_keys() {
		return array(
			'empty string' => array( '', 'must not be an empty string' ),
			'blank string' => array( "  \t\n", 'must not be an empty string' ),
			'null'         => array( null, 'NULL given' ),
			'array'        => array( array( 'a' ), 'array given' ),
			'object'       => array( new stdClass(), 'object given' ),
			'float'        => array( 1.5, 'double given' ),
			'true'         => array( true, 'boolean given' ),
		);
	}

	/**
	 * Keys that are fine.
	 *
	 * @return array
	 */
	public function valid_keys() {
		return array(
			'string'       => array( 'key' ),
			'string zero'  => array( '0' ),
			'integer'      => array( 5 ),
			'integer zero' => array( 0 ),
			'padded'       => array( ' key ' ),
			'unicode'      => array( 'clé ☃' ),
		);
	}

	/**
	 * Every single key function refuses an invalid key and says why.
	 *
	 * @dataProvider invalid_keys
	 *
	 * @param mixed  $key     Key to try.
	 * @param string $message Part of the notice expected.
	 */
	public function test_invalid_keys_are_rejected( $key, $message ) {
		$this->new_request();
		wp_cache_set( 'real', 1, 'posts' );

		$results = array(
			'set'     => wp_cache_set( $key, 'value', 'posts' ),
			'add'     => wp_cache_add( $key, 'value', 'posts' ),
			'replace' => wp_cache_replace( $key, 'value', 'posts' ),
			'get'     => wp_cache_get( $key, 'posts', false, $found ),
			'delete'  => wp_cache_delete( $key, 'posts' ),
			'incr'    => wp_cache_incr( $key, 1, 'posts' ),
			'decr'    => wp_cache_decr( $key, 1, 'posts' ),
		);

		foreach ( $results as $function => $result ) {
			$this->assertFalse( $result, $function );
		}
		$this->assertFalse( $found, 'get() reports a miss.' );
		$this->assertCount( 7, $GLOBALS['apcu_test_doing_it_wrong'], 'One notice per call.' );
		$this->assertStringContainsString( $message, $GLOBALS['apcu_test_doing_it_wrong'][0][1] );
		$this->assertSame( '6.1.0', $GLOBALS['apcu_test_doing_it_wrong'][0][2] );
	}

	/**
	 * An invalid key must not store anything or disturb real keys.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group Group to test.
	 */
	public function test_invalid_keys_store_nothing( $apcu, $local, $group ) {
		$cache = $this->new_request( $apcu, $local );
		wp_cache_set( 'real', 1, $group );

		wp_cache_set( '', 'value', $group );
		wp_cache_set( null, 'value', $group );
		wp_cache_set( array( 'a' ), 'value', $group );

		$this->assertSame( 1, wp_cache_get( 'real', $group ) );
		$this->assertSame( 0, $cache->get_cache_misses(), 'Rejected reads are not counted.' );
		$this->assertCount( 3, $GLOBALS['apcu_test_doing_it_wrong'] );
	}

	/**
	 * Good keys work and raise no notice.
	 *
	 * @dataProvider valid_keys
	 *
	 * @param mixed $key Key to try.
	 */
	public function test_valid_keys_work( $key ) {
		$this->new_request();

		$this->assertTrue( wp_cache_set( $key, 'value', 'posts' ) );
		$this->assertSame( 'value', wp_cache_get( $key, 'posts' ) );
		$this->assertTrue( wp_cache_delete( $key, 'posts' ) );
		$this->assertSame( array(), $GLOBALS['apcu_test_doing_it_wrong'] );
	}

	/**
	 * The multiple key functions report an invalid key and leave it out of the result.
	 */
	public function test_get_and_delete_multiple_skip_invalid_keys() {
		$this->new_request();
		wp_cache_set( 'a', 1, 'posts' );

		$this->assertSame(
			array(
				'a' => 1,
				'b' => false,
			),
			wp_cache_get_multiple( array( 'a', array( 'x' ), 'b', null ), 'posts' )
		);
		$this->assertCount( 2, $GLOBALS['apcu_test_doing_it_wrong'] );

		$this->assertSame( array( 'a' => true ), wp_cache_delete_multiple( array( 'a', new stdClass() ), 'posts' ) );
		$this->assertCount( 3, $GLOBALS['apcu_test_doing_it_wrong'] );
	}

	/**
	 * Data given to set_multiple and add_multiple has array keys, so only blank ones can be invalid.
	 */
	public function test_set_and_add_multiple_report_blank_keys() {
		$this->new_request();

		$this->assertSame(
			array(
				''  => false,
				'a' => true,
			),
			wp_cache_set_multiple(
				array(
					''  => 1,
					'a' => 2,
				),
				'posts'
			)
		);
		$this->assertSame(
			array(
				''  => false,
				'b' => true,
			),
			wp_cache_add_multiple(
				array(
					''  => 1,
					'b' => 2,
				),
				'posts'
			)
		);
		$this->assertCount( 2, $GLOBALS['apcu_test_doing_it_wrong'] );
	}

	/**
	 * The key written to APCu is salt:path:site:group:key:v{epoch}.{site version}.{group version}.
	 */
	public function test_the_apcu_key_has_a_documented_layout() {
		$cache = $this->new_request();
		$this->set_private( $cache, 'key_salt', 'salty' );
		$base = 'salty:' . md5( ABSPATH );

		$epoch = 'e1';
		$site  = 's1';
		$group = 'g1';
		apcu_store( $base . ':Epoch:0', $epoch );
		apcu_store( $base . ':SiteVersion:1', $site );
		apcu_store( $base . ':SiteVersion:0', 's0' );
		apcu_store( $base . ':GroupVersion:posts', $group );
		apcu_store( $base . ':GroupVersion:global', 'g0' );
		apcu_store( $base . ':GroupVersion:default', 'gd' );

		$this->assertSame( $base . ':1:posts:my-key:ve1.s1.g1', $this->call_private( $cache, 'build_key', array( 'my-key', 'posts' ) ) );
		$this->assertSame( $base . ':1:posts:7:ve1.s1.g1', $this->call_private( $cache, 'build_key', array( 7, 'posts' ) ) );
		$this->assertSame( $base . ':0:global:my-key:ve1.s0.g0', $this->call_private( $cache, 'build_key', array( 'my-key', 'global' ) ) );
		$this->assertSame( $base . ':1:default:my-key:ve1.s1.gd', $this->call_private( $cache, 'build_key', array( 'my-key', '' ) ) );

		$cache->set( 'my-key', 'value', 'posts' );
		$this->assertSame( 'value', apcu_fetch( $base . ':1:posts:my-key:ve1.s1.g1' ) );
	}
}
