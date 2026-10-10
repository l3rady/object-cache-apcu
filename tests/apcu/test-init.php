<?php
/**
 * Tests for creating the cache object and for its public accessors.
 *
 * @package object-cache-apcu
 */

/**
 * The cache is a normal object held in $wp_object_cache, as in core (issue #33).
 */
class Test_Init extends APCu_Test_Case {

	/**
	 * The constructor is public, as core's own tests rely on.
	 */
	public function test_cache_can_be_constructed_directly() {
		$this->assertInstanceOf( WP_Object_Cache::class, new WP_Object_Cache() );
	}

	/**
	 * Every wp_cache_init() creates a new object, as core does.
	 */
	public function test_wp_cache_init_creates_a_new_object_each_time() {
		wp_cache_init();
		$first = $GLOBALS['wp_object_cache'];

		wp_cache_init();

		$this->assertNotSame( $first, $GLOBALS['wp_object_cache'] );
	}

	/**
	 * Re-initialising clears the in-request state but not APCu.
	 */
	public function test_wp_cache_init_resets_the_runtime_cache_but_keeps_apcu() {
		$first = $this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );
		$this->assertCount( 1, $this->get_private( $first, 'local_cache' ) );

		$second = $this->new_request();

		$this->assertCount( 0, $this->get_private( $second, 'local_cache' ), 'In-request state must be reset.' );
		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ), 'APCu data must survive.' );
	}

	/**
	 * The wp_cache_*() functions follow the global, so replacing it replaces the cache they use.
	 */
	public function test_instance_returns_the_global_cache() {
		$cache = $this->new_request();

		$this->assertSame( $cache, WP_Object_Cache::instance() );
	}

	/**
	 * If wp_cache_init() has not run yet, instance() creates the global.
	 */
	public function test_instance_creates_the_global_when_missing() {
		unset( $GLOBALS['wp_object_cache'] );

		$cache = WP_Object_Cache::instance();

		$this->assertInstanceOf( WP_Object_Cache::class, $cache );
		$this->assertSame( $cache, $GLOBALS['wp_object_cache'] );
	}

	/**
	 * The configuration constants are read, not defined, by the drop-in.
	 */
	public function test_constructing_the_cache_does_not_define_constants() {
		$this->new_request();

		$this->assertFalse( defined( 'WP_APCU_KEY_SALT' ) );
		$this->assertFalse( defined( 'WP_APCU_LOCAL_CACHE' ) );
	}

	/**
	 * The public accessors use snake_case names.
	 */
	public function test_accessors_are_snake_case() {
		$cache = $this->new_request();

		wp_cache_set( 'a', 1, 'posts' );
		wp_cache_get( 'a', 'posts' );
		wp_cache_get( 'missing', 'posts' );

		$this->assertSame( 32, strlen( $cache->get_abs_path() ) );
		$this->assertTrue( $cache->get_apcu_available() );
		$this->assertSame( 1, $cache->get_blog_prefix() );
		$this->assertSame( 1, $cache->get_cache_hits() );
		$this->assertSame( 1, $cache->get_cache_misses() );
		$this->assertSame( array( 'global' => true ), $cache->get_global_groups() );
		$this->assertIsArray( $cache->get_group_versions() );
		$this->assertFalse( $cache->get_multisite() );
		$this->assertSame( array(), $cache->get_non_persistent_cache() );
		$this->assertSame( array( 'np' => true ), $cache->get_non_persistent_groups() );
		$this->assertIsArray( $cache->get_site_versions() );
	}

	/**
	 * The camelCase accessors were removed in 2.0.
	 */
	public function test_camel_case_accessors_no_longer_exist() {
		$cache = $this->new_request();

		foreach ( array( 'getAbsPath', 'getApcuAvailable', 'getBlogPrefix', 'getCacheHits', 'getCacheMisses', 'getGlobalGroups', 'getGroupVersions', 'getMultiSite', 'getNonPersistentCache', 'getNonPersistentGroups', 'getSiteVersions' ) as $old ) {
			$this->assertFalse( method_exists( $cache, $old ), $old . ' should have been renamed.' );
		}
	}
}
