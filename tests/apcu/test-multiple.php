<?php
/**
 * Tests for the *_multiple functions, which write and read through APCu's array APIs.
 *
 * @package object-cache-apcu
 */

/**
 * Issue #35. Each function must give exactly the answers and leave exactly the
 * state that calling the single-key function in a loop would.
 */
class Test_Multiple extends APCu_Test_Case {

	/**
	 * Runs one multiple-key call and its single-key equivalent in separate, empty
	 * caches and returns what each answered and left behind.
	 *
	 * @param callable $setup    Prepares the cache; receives nothing.
	 * @param callable $batched  Does the work with the multiple function; returns its result.
	 * @param callable $looped   Does the same with single-key functions; returns the result.
	 * @param bool     $apcu     Use APCu.
	 * @param bool     $local    Use the local cache.
	 * @param string[] $inspect  Keys to read back afterwards.
	 * @param string   $group    Group of the keys to read back.
	 * @return array Entries 'batched' and 'looped', each with the result, the values, the hits and the misses.
	 */
	private function compare( callable $setup, callable $batched, callable $looped, $apcu, $local, array $inspect, $group ) {
		$outcomes = array();

		foreach ( array( $batched, $looped ) as $work ) {
			apcu_clear_cache();
			$cache = $this->new_request( $apcu, $local );
			$setup();
			$cache->cache_hits   = 0;
			$cache->cache_misses = 0;

			$result = $work();
			$hits   = $cache->cache_hits;
			$misses = $cache->cache_misses;

			$values = array();
			foreach ( $inspect as $key ) {
				$values[ $key ] = wp_cache_get( $key, $group, true );
			}

			// Look at what a later request would see.
			$this->new_request( $apcu, $local );
			$later = array();
			foreach ( $inspect as $key ) {
				$later[ $key ] = wp_cache_get( $key, $group );
			}

			$outcomes[] = array(
				'result' => $result,
				'values' => $values,
				'later'  => $later,
				'hits'   => $hits,
				'misses' => $misses,
			);
		}

		return array(
			'batched' => $outcomes[0],
			'looped'  => $outcomes[1],
		);
	}

	/**
	 * Same answers and state as a loop of set().
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_set_multiple_matches_a_loop_of_set( $apcu, $local, $group ) {
		$data = array(
			'new'   => 'value',
			'old'   => 'after',
			7       => 'changed',
			'false' => false,
			'zero'  => 0,
			'null'  => null,
			'array' => array( 'a' => array( 1, 2 ) ),
		);

		$outcome = $this->compare(
			static function () use ( $group ) {
				wp_cache_set( 'old', 'before', $group );
				wp_cache_set( 7, 'seven', $group );
			},
			static fn() => wp_cache_set_multiple( $data, $group ),
			static function () use ( $data, $group ) {
				$result = array();
				foreach ( $data as $key => $value ) {
					$result[ $key ] = wp_cache_set( $key, $value, $group );
				}
				return $result;
			},
			$apcu,
			$local,
			array_map( 'strval', array_keys( $data ) ),
			$group
		);

		$batched = $outcome['batched'];
		$this->assertSame( $outcome['looped'], $batched );
		$this->assertSame( array_keys( $data ), array_keys( $batched['result'] ), 'The order of the keys must be kept.' );
		$this->assertNotContains( false, $batched['result'] );
	}

	/**
	 * Same answers and state as a loop of add(): existing keys are left alone.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_add_multiple_matches_a_loop_of_add( $apcu, $local, $group ) {
		$data = array(
			'new'  => 'value',
			'old'  => 'after',
			7      => 'changed',
			'kept' => 'changed',
			'zero' => 0,
		);

		$outcome = $this->compare(
			static function () use ( $group ) {
				wp_cache_set( 'kept', 'before', $group );
				wp_cache_set( 'old', 'before', $group );
				wp_cache_set( 7, 'seven', $group );
			},
			static fn() => wp_cache_add_multiple( $data, $group ),
			static function () use ( $data, $group ) {
				$result = array();
				foreach ( $data as $key => $value ) {
					$result[ $key ] = wp_cache_add( $key, $value, $group );
				}
				return $result;
			},
			$apcu,
			$local,
			array_map( 'strval', array_keys( $data ) ),
			$group
		);

		$batched = $outcome['batched'];
		$this->assertSame( $outcome['looped'], $batched );
		$this->assertSame(
			array(
				'new'  => true,
				'old'  => false,
				7      => false,
				'kept' => false,
				'zero' => true,
			),
			$batched['result']
		);
		$this->assertSame( 'before', $batched['values']['old'], 'An existing value must not be overwritten.' );
	}

	/**
	 * Same answers, state and hit/miss counts as a loop of get().
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_get_multiple_matches_a_loop_of_get( $apcu, $local, $group ) {
		$keys = array( 'missing', 'old', 7, 'false', 'old', 'also-missing' );

		$outcome = $this->compare(
			function () use ( $group ) {
				wp_cache_set( 'old', 'value', $group );
				wp_cache_set( 7, 'seven', $group );
				wp_cache_set( 'false', false, $group );
			},
			static fn() => wp_cache_get_multiple( $keys, $group ),
			static function () use ( $keys, $group ) {
				$result = array();
				foreach ( $keys as $key ) {
					$result[ $key ] = wp_cache_get( $key, $group );
				}
				return $result;
			},
			$apcu,
			$local,
			array( 'old' ),
			$group
		);

		$batched = $outcome['batched'];
		$this->assertSame( $outcome['looped'], $batched );
		$this->assertSame( array( 'missing', 'old', 7, 'false', 'also-missing' ), array_keys( $batched['result'] ) );
		$this->assertSame( 4, $batched['hits'] );
		$this->assertSame( 2, $batched['misses'] );
	}

	/**
	 * Same answers and state as a loop of delete().
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_delete_multiple_matches_a_loop_of_delete( $apcu, $local, $group ) {
		$keys = array( 'old', 'missing', 7, 'kept-not' );

		$outcome = $this->compare(
			function () use ( $group ) {
				wp_cache_set( 'old', 'value', $group );
				wp_cache_set( 7, 'seven', $group );
				wp_cache_set( 'other', 'kept', $group );
			},
			static fn() => wp_cache_delete_multiple( $keys, $group ),
			static function () use ( $keys, $group ) {
				$result = array();
				foreach ( $keys as $key ) {
					$result[ $key ] = wp_cache_delete( $key, $group );
				}
				return $result;
			},
			$apcu,
			$local,
			array( 'old', '7', 'other' ),
			$group
		);

		$batched = $outcome['batched'];
		$this->assertSame( $outcome['looped'], $batched );
		$this->assertSame(
			array(
				'old'      => true,
				'missing'  => false,
				7          => true,
				'kept-not' => false,
			),
			$batched['result']
		);
		$this->assertSame( 'kept', $batched['values']['other'] );
	}

	/**
	 * With the local cache on, a value another request changed is only seen when forced.
	 */
	public function test_get_multiple_uses_the_local_copy_unless_forced() {
		$cache = $this->new_request();
		wp_cache_set_multiple(
			array(
				'a' => 'mine',
				'b' => 'mine',
			),
			'posts'
		);
		apcu_store( $this->call_private( $cache, 'build_key', array( 'a', 'posts' ) ), 'theirs' );

		$this->assertSame(
			array(
				'a' => 'mine',
				'b' => 'mine',
			),
			wp_cache_get_multiple( array( 'a', 'b' ), 'posts' )
		);
		$this->assertSame(
			array(
				'a' => 'theirs',
				'b' => 'mine',
			),
			wp_cache_get_multiple( array( 'a', 'b' ), 'posts', true )
		);
		$this->assertSame( 'theirs', wp_cache_get( 'a', 'posts' ), 'A forced read refreshes the local copy.' );
	}

	/**
	 * A key found in neither place must not leave a stale local copy behind.
	 */
	public function test_get_multiple_forgets_local_copies_that_apcu_no_longer_has() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'mine', 'posts' );
		apcu_delete( $this->call_private( $cache, 'build_key', array( 'a', 'posts' ) ) );

		$this->assertSame( array( 'a' => false ), wp_cache_get_multiple( array( 'a' ), 'posts', true ) );
		$this->assertFalse( wp_cache_get( 'a', 'posts' ), 'The local copy must be dropped.' );
	}

	/**
	 * Local copies of deleted keys are dropped, so the next read goes to APCu.
	 */
	public function test_delete_multiple_drops_local_copies() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', 'posts' );

		wp_cache_delete_multiple( array( 'a' ), 'posts' );

		$this->assertCount( 0, $this->get_private( $cache, 'local_cache' ) );
	}

	/**
	 * Add is not allowed while additions are suspended, as for add().
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_add_multiple_honours_suspended_additions( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );
		$GLOBALS['apcu_test_suspend_additions'] = true;

		$this->assertSame(
			array(
				'a' => false,
				'b' => false,
			),
			wp_cache_add_multiple(
				array(
					'a' => 1,
					'b' => 2,
				),
				$group
			)
		);

		unset( $GLOBALS['apcu_test_suspend_additions'] );
		$this->assertFalse( wp_cache_get( 'a', $group ) );
	}

	/**
	 * Objects are copied on the way in and out, as single reads and writes do.
	 */
	public function test_objects_are_copied() {
		$this->new_request();
		$object      = new stdClass();
		$object->tag = 'original';

		wp_cache_set_multiple( array( 'o' => $object ), 'posts' );
		$object->tag = 'changed after storing';

		$first = wp_cache_get_multiple( array( 'o' ), 'posts' );
		$this->assertSame( 'original', $first['o']->tag );

		$first['o']->tag = 'changed after reading';
		$second          = wp_cache_get_multiple( array( 'o' ), 'posts' );
		$this->assertSame( 'original', $second['o']->tag );

		$this->new_request();
		$third = wp_cache_get_multiple( array( 'o' ), 'posts' );
		$this->assertSame( 'original', $third['o']->tag );
	}

	/**
	 * The expiry reaches APCu for every key.
	 */
	public function test_expiry_is_applied_to_every_key() {
		$this->new_request();

		wp_cache_set_multiple(
			array(
				'a' => 1,
				'b' => 2,
			),
			'posts',
			300
		);
		wp_cache_add_multiple( array( 'c' => 3 ), 'posts', 600 );
		wp_cache_set_multiple( array( 'd' => 4 ), 'posts' );

		$ttls = array();
		foreach ( apcu_cache_info( false )['cache_list'] as $entry ) {
			$ttls[ $entry['info'] ] = $entry['ttl'];
		}

		$this->assertSame( 300, $ttls[ $this->call_private( $GLOBALS['wp_object_cache'], 'build_key', array( 'a', 'posts' ) ) ] );
		$this->assertSame( 300, $ttls[ $this->call_private( $GLOBALS['wp_object_cache'], 'build_key', array( 'b', 'posts' ) ) ] );
		$this->assertSame( 600, $ttls[ $this->call_private( $GLOBALS['wp_object_cache'], 'build_key', array( 'c', 'posts' ) ) ] );
		$this->assertSame( 0, $ttls[ $this->call_private( $GLOBALS['wp_object_cache'], 'build_key', array( 'd', 'posts' ) ) ] );
	}

	/**
	 * Empty input is fine.
	 *
	 * @dataProvider scenarios
	 *
	 * @param bool   $apcu  Use APCu.
	 * @param bool   $local Use the local cache.
	 * @param string $group A group persistent under that configuration.
	 */
	public function test_empty_input( $apcu, $local, $group ) {
		$this->new_request( $apcu, $local );

		$this->assertSame( array(), wp_cache_set_multiple( array(), $group ) );
		$this->assertSame( array(), wp_cache_add_multiple( array(), $group ) );
		$this->assertSame( array(), wp_cache_get_multiple( array(), $group ) );
		$this->assertSame( array(), wp_cache_delete_multiple( array(), $group ) );
	}
}
