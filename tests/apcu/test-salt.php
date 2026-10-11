<?php
/**
 * Tests for the key salt.
 *
 * @package object-cache-apcu
 */

/**
 * Issue #36: the salt comes from WP_APCU_KEY_SALT, then WP_CACHE_KEY_SALT, then the database.
 */
class Test_Salt extends APCu_Test_Case {

	/**
	 * Calls the resolver.
	 *
	 * @param string $apcu   WP_APCU_KEY_SALT.
	 * @param string $cache  WP_CACHE_KEY_SALT.
	 * @param string $db     DB_NAME.
	 * @param string $prefix Table prefix.
	 * @return string
	 */
	private function resolve( $apcu, $cache, $db, $prefix ) {
		$method = new ReflectionMethod( WP_Object_Cache::class, 'resolve_key_salt' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		return $method->invoke( null, $apcu, $cache, $db, $prefix );
	}

	/**
	 * Makes a cache the one the wp_cache_*() functions use.
	 *
	 * @param WP_Object_Cache $cache Cache to use.
	 */
	private function use_cache( $cache ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The functions under test use this global.
		$GLOBALS['wp_object_cache'] = $cache;
	}

	/**
	 * Each level wins over the ones below it.
	 */
	public function test_precedence() {
		$this->assertSame( 'mine', $this->resolve( 'mine', 'shared', 'db', 'wp_' ) );
		$this->assertSame( 'shared', $this->resolve( '', 'shared', 'db', 'wp_' ) );
		$this->assertSame( md5( 'db:wp_' ), $this->resolve( '', '', 'db', 'wp_' ) );
		$this->assertSame( 'wp', $this->resolve( '', '', '', 'wp_' ) );
	}

	/**
	 * Two installs on the same ABSPATH with different databases or prefixes do not collide.
	 */
	public function test_database_default_separates_installs() {
		$this->assertNotSame( $this->resolve( '', '', 'site_a', 'wp_' ), $this->resolve( '', '', 'site_b', 'wp_' ) );
		$this->assertNotSame( $this->resolve( '', '', 'site', 'a_' ), $this->resolve( '', '', 'site', 'b_' ) );
		$this->assertSame( $this->resolve( '', '', 'site', 'a_' ), $this->resolve( '', '', 'site', 'a_' ) );
	}

	/**
	 * The salt ends up in the keys, so a different one gives a different, empty cache.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_constants_and_globals_reach_the_keys() {
		define( 'DB_NAME', 'some_database' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulates wp-config.php.
		$GLOBALS['table_prefix'] = 'xyz_';

		$cache = $this->new_request();
		$this->assertSame( md5( 'some_database:xyz_' ), $this->get_private( $cache, 'key_salt' ) );
		$this->assertStringStartsWith( md5( 'some_database:xyz_' ) . ':', $this->call_private( $cache, 'build_key', array( 'k', 'posts' ) ) );
	}

	/**
	 * WP_CACHE_KEY_SALT is read.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_wp_cache_key_salt_constant_is_used() {
		define( 'WP_CACHE_KEY_SALT', 'from-host' );

		$this->assertSame( 'from-host', $this->get_private( $this->new_request(), 'key_salt' ) );
	}

	/**
	 * WP_APCU_KEY_SALT beats WP_CACHE_KEY_SALT.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_wp_apcu_key_salt_constant_wins() {
		define( 'WP_CACHE_KEY_SALT', 'from-host' );
		define( 'WP_APCU_KEY_SALT', 'ours' );

		$this->assertSame( 'ours', $this->get_private( $this->new_request(), 'key_salt' ) );
	}

	/**
	 * Installs with different salts share APCu without seeing each other's data.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_two_salts_do_not_see_each_other() {
		$first = new WP_Object_Cache();
		$this->set_private( $first, 'key_salt', 'one' );
		$second = new WP_Object_Cache();
		$this->set_private( $second, 'key_salt', 'two' );

		$this->use_cache( $first );
		wp_cache_set( 'k', 'first', 'posts' );
		$this->use_cache( $second );

		$this->assertFalse( wp_cache_get( 'k', 'posts' ) );
		$this->assertTrue( wp_cache_set( 'k', 'second', 'posts' ) );
		$this->use_cache( $first );
		$this->assertSame( 'first', wp_cache_get( 'k', 'posts', true ) );
	}
}
