<?php
/**
 * Tests for the wp_cache_*() functions that hand over to the cache object.
 *
 * @package object-cache-apcu
 */

/**
 * Each function must reach the cache object that WordPress holds in $wp_object_cache.
 */
class Test_Functions extends APCu_Test_Case {

	/**
	 * The blog id is switched on the global cache, and only on multisite.
	 */
	public function test_wp_cache_switch_to_blog_changes_the_blog_prefix_on_multisite() {
		$GLOBALS['apcu_test_multisite'] = true;
		$cache                          = $this->new_request();

		wp_cache_switch_to_blog( 7 );

		$this->assertSame( 7, $cache->get_blog_prefix() );
	}

	/**
	 * Without multisite there is only one site, whatever id is passed.
	 */
	public function test_wp_cache_switch_to_blog_keeps_prefix_one_on_a_single_site() {
		$cache = $this->new_request();

		wp_cache_switch_to_blog( 7 );

		$this->assertSame( 1, $cache->get_blog_prefix() );
	}

	/**
	 * A group added through the function is shared by every site.
	 */
	public function test_wp_cache_add_global_groups_makes_a_group_global() {
		$GLOBALS['apcu_test_multisite'] = true;
		$cache                          = $this->new_request();

		wp_cache_add_global_groups( array( 'shared-a', 'shared-b' ) );
		wp_cache_switch_to_blog( 1 );
		wp_cache_set( 'key', 'one', 'shared-a' );
		wp_cache_set( 'key', 'plain', 'plain-group' );
		wp_cache_switch_to_blog( 2 );

		$this->assertSame( 'one', wp_cache_get( 'key', 'shared-a' ), 'A global group is the same on every site.' );
		$this->assertFalse( wp_cache_get( 'key', 'plain-group' ), 'Any other group is per site.' );
		$this->assertArrayHasKey( 'shared-b', $cache->get_global_groups(), 'Every group in the list is added.' );
	}

	/**
	 * A group added through the function is never written to APCu.
	 */
	public function test_wp_cache_add_non_persistent_groups_keeps_a_group_out_of_apcu() {
		$this->new_request();

		wp_cache_add_non_persistent_groups( 'memory-only' );
		wp_cache_set( 'key', 'value', 'memory-only' );

		$this->assertSame( 'value', wp_cache_get( 'key', 'memory-only' ), 'It is still cached for this request.' );

		$this->new_request();

		$this->assertFalse( wp_cache_get( 'key', 'memory-only' ), 'A new request must not see it.' );
	}

	/**
	 * The function that was deprecated in WordPress 3.5 reports itself and does nothing.
	 */
	public function test_wp_cache_reset_is_deprecated_and_returns_false() {
		$this->new_request();
		wp_cache_set( 'key', 'value', 'posts' );

		$this->assertFalse( wp_cache_reset() ); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_cache_resetFound -- The deprecated function is what is tested.
		$this->assertSame( array( array( 'wp_cache_reset', '3.5', 'wp_cache_switch_to_blog()' ) ), $GLOBALS['apcu_test_deprecated'] );
		$this->assertSame( 'value', wp_cache_get( 'key', 'posts' ), 'It must not clear the cache.' );
	}

	/**
	 * The method that backs it is deprecated too.
	 */
	public function test_the_reset_method_is_deprecated() {
		$cache = $this->new_request();

		$cache->reset();

		$this->assertSame( array( array( 'reset', '3.5.0', 'switch_to_blog()' ) ), $GLOBALS['apcu_test_deprecated'] );
	}

	/**
	 * Every feature core can ask about.
	 *
	 * @return array
	 */
	public function supported_features() {
		return array(
			'add_multiple'    => array( 'add_multiple' ),
			'set_multiple'    => array( 'set_multiple' ),
			'get_multiple'    => array( 'get_multiple' ),
			'delete_multiple' => array( 'delete_multiple' ),
			'flush_runtime'   => array( 'flush_runtime' ),
			'flush_group'     => array( 'flush_group' ),
		);
	}

	/**
	 * Supported features report true.
	 *
	 * @dataProvider supported_features
	 *
	 * @param string $feature Feature name.
	 */
	public function test_wp_cache_supports_every_core_feature( $feature ) {
		$this->assertTrue( wp_cache_supports( $feature ) );
	}

	/**
	 * Anything else is reported as unsupported, so core falls back to its own way.
	 */
	public function test_wp_cache_supports_nothing_else() {
		$this->assertFalse( wp_cache_supports( 'flush_everything' ) );
		$this->assertFalse( wp_cache_supports( '' ) );
		$this->assertFalse( wp_cache_supports( 'ADD_MULTIPLE' ), 'Feature names are case sensitive.' );
	}
}
