<?php
/**
 * Micro-benchmark: *_multiple against a loop of single-key calls.
 *
 * Run with `make bench`. It only documents the gain; nothing asserts on it.
 *
 * @package object-cache-apcu
 */

define( 'BENCH_KEYS', 500 );
define( 'BENCH_ROUNDS', 200 );

require_once dirname( __DIR__ ) . '/apcu/bootstrap.php';

/**
 * Times a callable over the benchmark rounds, each starting from a new request.
 *
 * @param callable $work   Does the work being timed.
 * @param bool     $local  Whether the local cache is used.
 * @param array    $primed Values already in APCu, but not in this request's memory.
 * @return float Milliseconds per round.
 */
function bench_run( callable $work, bool $local, array $primed = array() ): float {
	$total = 0.0;

	for ( $round = 0; $round < BENCH_ROUNDS; $round++ ) {
		apcu_clear_cache();
		wp_cache_init();

		$property = new ReflectionProperty( $GLOBALS['wp_object_cache'], 'local_cache_enabled' );
		$property->setAccessible( true );
		$property->setValue( $GLOBALS['wp_object_cache'], $local );

		if ( $primed ) {
			wp_cache_set_multiple( $primed, 'posts' );
			wp_cache_flush_runtime();
		}

		$start = hrtime( true );
		$work();
		$total += hrtime( true ) - $start;
	}

	return $total / BENCH_ROUNDS / 1e6;
}

/**
 * Prints one comparison row.
 *
 * @param string $label Operation measured.
 * @param float  $loop  Milliseconds for the loop of single calls.
 * @param float  $batch Milliseconds for the multiple function.
 */
function bench_row( string $label, float $loop, float $batch ): void {
	printf( "%-8s loop %8.3f ms   multiple %8.3f ms   x%.1f\n", $label, $loop, $batch, $loop / $batch );
}

/**
 * Calls a single-key write function once per entry.
 *
 * @param callable $callback wp_cache_set or wp_cache_add.
 * @param array    $data     Keys and values.
 */
function bench_write( callable $callback, array $data ): void {
	foreach ( $data as $key => $value ) {
		$callback( $key, $value, 'posts' );
	}
}

/**
 * Calls a single-key read or delete function once per key.
 *
 * @param callable $callback wp_cache_get or wp_cache_delete.
 * @param array    $keys     Keys.
 */
function bench_read( callable $callback, array $keys ): void {
	foreach ( $keys as $key ) {
		$callback( $key, 'posts' );
	}
}

$data = array();
for ( $i = 0; $i < BENCH_KEYS; $i++ ) {
	$data[ 'key-' . $i ] = array(
		'id'    => $i,
		'title' => str_repeat( 'x', 200 ),
	);
}
$keys = array_keys( $data );

printf( "PHP %s, APCu %s, %d keys per call, %d rounds\n", PHP_VERSION, phpversion( 'apcu' ), BENCH_KEYS, BENCH_ROUNDS );

foreach ( array( true, false ) as $local ) {
	echo $local ? "\nWith the local cache\n" : "\nWithout the local cache\n";

	bench_row( 'set', bench_run( static fn() => bench_write( 'wp_cache_set', $data ), $local ), bench_run( static fn() => wp_cache_set_multiple( $data, 'posts' ), $local ) );
	bench_row( 'add', bench_run( static fn() => bench_write( 'wp_cache_add', $data ), $local ), bench_run( static fn() => wp_cache_add_multiple( $data, 'posts' ), $local ) );
	bench_row( 'get', bench_run( static fn() => bench_read( 'wp_cache_get', $keys ), $local, $data ), bench_run( static fn() => wp_cache_get_multiple( $keys, 'posts' ), $local, $data ) );
	bench_row( 'delete', bench_run( static fn() => bench_read( 'wp_cache_delete', $keys ), $local, $data ), bench_run( static fn() => wp_cache_delete_multiple( $keys, 'posts' ), $local, $data ) );
}
