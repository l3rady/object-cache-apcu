<?php
/**
 * Tests that object values are copied when they go into and come out of the cache.
 *
 * @package object-cache-apcu
 */

/**
 * Callers get and keep their own copy of an object, so changing it never changes
 * what another caller reads from the cache.
 */
class Test_Object_Values extends APCu_Test_Case {

	/**
	 * Makes an object to cache.
	 *
	 * @return stdClass
	 */
	private function make_object() {
		$value       = new stdClass();
		$value->name = 'original';

		return $value;
	}

	/**
	 * Changing an object after storing it does not change the cached copy.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_set_stores_a_copy( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		$value = $this->make_object();

		wp_cache_set( 'key', $value, $group );
		$value->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get( 'key', $group )->name );
	}

	/**
	 * Changing an object after adding it does not change the cached copy.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_add_stores_a_copy( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		$value = $this->make_object();

		wp_cache_add( 'key', $value, $group );
		$value->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get( 'key', $group )->name );
	}

	/**
	 * Changing an object after replacing with it does not change the cached copy.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_replace_stores_a_copy( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', 'first', $group );
		$value = $this->make_object();

		wp_cache_replace( 'key', $value, $group );
		$value->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get( 'key', $group )->name );
	}

	/**
	 * Changing an object that a read returned does not change the cached copy.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_get_returns_a_copy( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set( 'key', $this->make_object(), $group );

		$first       = wp_cache_get( 'key', $group );
		$first->name = 'changed by the caller';
		$second      = wp_cache_get( 'key', $group );

		$this->assertSame( 'original', $second->name );
		$this->assertNotSame( $first, $second, 'Every read is a separate object.' );
	}

	/**
	 * Reading from APCu in a new request gives a copy as well.
	 */
	public function test_get_returns_a_copy_when_read_from_apcu() {
		$this->new_request();
		wp_cache_set( 'key', $this->make_object(), 'posts' );

		$this->new_request();
		$first       = wp_cache_get( 'key', 'posts' );
		$first->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get( 'key', 'posts' )->name );
	}

	/**
	 * The multiple-key functions copy on the way in.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_set_multiple_and_add_multiple_store_copies( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		$set = $this->make_object();
		$add = $this->make_object();

		wp_cache_set_multiple( array( 'set' => $set ), $group );
		wp_cache_add_multiple( array( 'add' => $add ), $group );
		$set->name = 'changed by the caller';
		$add->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get( 'set', $group )->name );
		$this->assertSame( 'original', wp_cache_get( 'add', $group )->name );
	}

	/**
	 * Reading several keys with get_multiple() hands out copies.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Whether APCu is used.
	 * @param bool   $local Whether the local cache is used.
	 * @param string $group Group to use.
	 */
	public function test_get_multiple_returns_copies( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		wp_cache_set_multiple( array( 'key' => $this->make_object() ), $group );

		$first              = wp_cache_get_multiple( array( 'key' ), $group );
		$first['key']->name = 'changed by the caller';

		$this->assertSame( 'original', wp_cache_get_multiple( array( 'key' ), $group )['key']->name );
		$this->assertSame( 'original', wp_cache_get( 'key', $group )->name );
	}
}
