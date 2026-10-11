<?php
/**
 * Tests for the parts of core's WP_Object_Cache API that plugins call on the object.
 *
 * @package object-cache-apcu
 */

/**
 * Issue #31: flush_group(), reset() and the legacy properties.
 */
class Test_Class_API extends APCu_Test_Case {

	/**
	 * Core's method name works on the object.
	 */
	public function test_flush_group_method_flushes_the_group() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );
		wp_cache_set( 'other', 'kept', 'comments' );

		$this->assertTrue( $cache->flush_group( 'posts' ) );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'kept', wp_cache_get( 'other', 'comments' ) );
	}

	/**
	 * More than one group can be given, which core's own method does not allow.
	 */
	public function test_flush_group_accepts_an_array() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_set( 'b', 2, 'comments' );

		$this->assertTrue( $cache->flush_group( array( 'posts', 'comments' ) ) );

		$this->assertFalse( wp_cache_get( 'a', 'posts' ) );
		$this->assertFalse( wp_cache_get( 'b', 'comments' ) );
		$this->assertFalse( $cache->flush_group( array() ) );
	}

	/**
	 * The 1.x name still works.
	 */
	public function test_flush_groups_is_an_alias() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );

		$this->assertTrue( $cache->flush_groups( 'posts' ) );

		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * Calling reset() does not fatal and leaves the cache working.
	 */
	public function test_reset_is_harmless() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );

		$cache->reset();

		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ) );
	}

	/**
	 * What debug plugins read as `$wp_object_cache->cache`.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu       Use APCu.
	 * @param bool   $local      Use the local cache.
	 * @param string $group      A group persistent under that configuration.
	 * @param bool   $persistent Whether the group is stored in APCu.
	 */
	public function test_cache_property_lists_values_by_group_and_key( $apcu, $local, $group, $persistent ) {
		$cache = $this->new_request( $apcu, $local );
		wp_cache_set( 'a', 'one', $group );
		wp_cache_set( 5, 'five', $group );
		wp_cache_set( 'x:y', 'colon', $group );

		$held = $cache->cache;

		if ( ! $local && $persistent ) {
			$this->assertSame( array(), $held, 'With no local cache nothing is held in memory.' );
			return;
		}

		$this->assertSame(
			array(
				'a'   => 'one',
				'5'   => 'five',
				'x:y' => 'colon',
			),
			$held[ $group ]
		);
	}

	/**
	 * The legacy properties map onto the cache's own state.
	 */
	public function test_legacy_properties_are_readable() {
		$cache = $this->new_request();

		$this->assertSame( array( 'global' => true ), $cache->global_groups );
		$this->assertSame( 1, $cache->blog_prefix );
		$this->assertFalse( $cache->multisite );
		$this->assertTrue( isset( $cache->cache ) );
		$this->assertTrue( isset( $cache->blog_prefix ) );
	}

	/**
	 * Hits and misses stay public, for stats plugins.
	 */
	public function test_hit_and_miss_counters_are_public_properties() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_get( 'a', 'posts' );
		wp_cache_get( 'missing', 'posts' );

		$this->assertSame( 1, $cache->cache_hits );
		$this->assertSame( 1, $cache->cache_misses );
	}

	/**
	 * The legacy properties cannot be overwritten from outside.
	 */
	public function test_legacy_properties_are_read_only() {
		$cache = $this->new_request();

		$cache->blog_prefix = 99;

		$this->assertSame( 1, $cache->get_blog_prefix() );
		$this->assertCount( 1, $GLOBALS['apcu_test_doing_it_wrong'] );
	}

	/**
	 * Other names behave like properties on a plain object, as they do in core.
	 */
	public function test_other_properties_can_be_set_and_unset() {
		$cache = $this->new_request();

		$this->assertNull( $cache->something );
		$this->assertFalse( isset( $cache->something ) );

		$cache->something = 'value';
		$this->assertSame( 'value', $cache->something );
		$this->assertTrue( isset( $cache->something ) );

		unset( $cache->something );
		$this->assertFalse( isset( $cache->something ) );
	}
}
