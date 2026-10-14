<?php
/**
 * Tests for optional arguments, constructor defaults and small internal contracts.
 *
 * @package object-cache-apcu
 */

/**
 * What happens when the optional arguments are left out, and how a new cache starts out.
 */
class Test_Defaults extends APCu_Test_Case {

	/**
	 * Reads the expiry APCu holds for a key of this cache.
	 *
	 * @param WP_Object_Cache $cache Cache that wrote the key.
	 * @param string          $key   Key.
	 * @param string          $group Group.
	 * @return int|null
	 */
	private function ttl_of( $cache, $key, $group = 'default' ) {
		$full_key = $this->call_private( $cache, 'build_key', array( $key, $group ) );
		foreach ( apcu_cache_info( false )['cache_list'] as $entry ) {
			if ( $entry['info'] === $full_key ) {
				return $entry['ttl'];
			}
		}

		return null;
	}

	/**
	 * A new cache uses the local cache and APCu unless told otherwise.
	 */
	public function test_a_new_cache_uses_the_local_cache_and_apcu() {
		$cache = new WP_Object_Cache();

		$this->assertTrue( $this->get_private( $cache, 'local_cache_enabled' ) );
		$this->assertTrue( $this->get_private( $cache, 'apcu_available' ) );
		$this->assertFalse( $this->get_private( $cache, 'flush_hard' ) );
	}

	/**
	 * Without an expiry, entries never expire.
	 */
	public function test_no_expiry_by_default() {
		$cache = $this->new_request();

		$cache->set( 'set', 1 );
		$cache->add( 'add', 1 );
		$cache->replace( 'set', 2 );
		$cache->set( 'replaced', 1 );
		$cache->replace( 'replaced', 2 );

		$this->assertSame( 0, $this->ttl_of( $cache, 'set' ) );
		$this->assertSame( 0, $this->ttl_of( $cache, 'add' ) );
		$this->assertSame( 0, $this->ttl_of( $cache, 'replaced' ) );
	}

	/**
	 * A negative expiry means no expiry rather than an error.
	 */
	public function test_a_negative_expiry_is_no_expiry() {
		$cache = $this->new_request();

		$cache->set( 'set', 1, 'default', -5 );
		$cache->add( 'add', 1, 'default', -5 );
		$cache->set_multiple( array( 'many' => 1 ), 'default', -5 );
		$cache->add_multiple( array( 'added' => 1 ), 'default', -5 );

		foreach ( array( 'set', 'add', 'many', 'added' ) as $key ) {
			$this->assertSame( 0, $this->ttl_of( $cache, $key ), $key );
		}
	}

	/**
	 * Without an offset, incr() and decr() change the value by one.
	 */
	public function test_incr_and_decr_default_to_one() {
		foreach ( array( true, false ) as $apcu ) {
			$cache = $this->new_request( $apcu );
			$cache->set( 'n', 10, 'posts' );

			$this->assertSame( 11, $cache->incr( 'n', 1, 'posts' ) );
			$this->assertSame( 12, $cache->incr( 'n', 1, 'posts' ) );
			$this->assertSame( 11, $cache->decr( 'n', 1, 'posts' ) );

			$cache->set( 'n', 10 );
			$this->assertSame( 11, $cache->incr( 'n' ) );
			$this->assertSame( 10, $cache->decr( 'n' ) );
		}
	}

	/**
	 * Reads are answered from this request's copy unless a refresh is asked for.
	 */
	public function test_get_uses_the_local_copy_by_default() {
		$cache = $this->new_request();
		$cache->set( 'a', 'mine' );
		apcu_store( $this->call_private( $cache, 'build_key', array( 'a', 'default' ) ), 'changed elsewhere' );

		$this->assertSame( 'mine', $cache->get( 'a' ) );
		$this->assertSame( 'mine', $this->call_private( $cache, 'get_apcu', array( $this->call_private( $cache, 'build_key', array( 'a', 'default' ) ) ) ) );
		$this->assertSame( 'changed elsewhere', $cache->get( 'a', 'default', true ) );
	}

	/**
	 * A site version found once is kept for the request, not read again for every key.
	 */
	public function test_the_site_version_is_remembered() {
		$cache = $this->new_request();
		$cache->set( 'a', 'value', 'posts' );

		$version_key = $this->call_private( $cache, 'get_cache_version_key', array( 'SiteVersion', 1 ) );
		apcu_store( $version_key, 'changed-by-another-request' );

		$this->assertSame( 'value', $cache->get( 'a', 'posts', true ) );
	}

	/**
	 * Flushing a site named by a string still flushes the global groups exactly once.
	 */
	public function test_flush_sites_with_string_ids() {
		$cache = new Counting_Object_Cache();

		$cache->flush_sites( array( '0', '3' ) );
		$this->assertSame( 2, $cache->minted, 'Site 0 is already in the list.' );

		$cache->flush_sites( array( '3' ) );
		$this->assertSame( 4, $cache->minted, 'Site 3 and the global groups.' );

		$cache->flush_sites( array() );
		$this->assertSame( 6, $cache->minted, 'The current site and the global groups.' );
	}

	/**
	 * Storing a version reports whether APCu took it, and without APCu keeps it in memory.
	 */
	public function test_set_cache_version_result() {
		$cache = $this->new_request();
		$this->assertTrue( $this->call_private( $cache, 'set_cache_version', array( 'a-key', 'v1' ) ) );
		$this->assertSame( 'v1', apcu_fetch( 'a-key' ) );

		$cache = $this->new_request( false );
		$this->assertSame( 'v2', $this->call_private( $cache, 'set_cache_version', array( 'b-key', 'v2' ) ) );
		$this->assertFalse( apcu_exists( 'b-key' ) );
		$this->assertSame( 'v2', $this->call_private( $cache, 'get_cache_version', array( 'b-key' ) ) );
	}

	/**
	 * Writing a read only property is refused and leaves nothing behind.
	 */
	public function test_a_refused_property_is_not_kept() {
		$cache = $this->new_request();

		$cache->blog_prefix = 99;

		$this->assertSame( array(), $this->get_private( $cache, 'dynamic_properties' ) );
	}
}
