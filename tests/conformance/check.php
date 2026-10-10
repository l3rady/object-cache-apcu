<?php
/**
 * Compares a PHPUnit JUnit log from WordPress's own test suite with the list of
 * expected failures.
 *
 * Usage: php check.php <junit.xml> <known-failures.txt>
 *
 * Exits non-zero if a test failed that is not listed (a regression), or if a
 * listed test passed or did not run (a stale entry).
 *
 * @package object-cache-apcu
 */

if ( $argc < 3 ) {
	fwrite( STDERR, "Usage: php check.php <junit.xml> <known-failures.txt>\n" );
	exit( 2 );
}

list( , $junit_file, $known_file ) = $argv;

if ( ! is_readable( $junit_file ) ) {
	fwrite( STDERR, "No JUnit log at {$junit_file}: PHPUnit did not finish.\n" );
	exit( 2 );
}

// Expected failures: "Class::test | reason".
$known = array();
foreach ( file( $known_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
	$line = trim( $line );
	if ( '' === $line || '#' === $line[0] ) {
		continue;
	}
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) < 2 || '' === $parts[1] ) {
		fwrite( STDERR, "Known failure without a reason: {$line}\n" );
		exit( 2 );
	}
	$known[ $parts[0] ] = $parts[1];
}

$xml = simplexml_load_file( $junit_file );
if ( false === $xml ) {
	fwrite( STDERR, "Could not parse {$junit_file}.\n" );
	exit( 2 );
}

$ran     = array();
$failing = array();
foreach ( $xml->xpath( '//testcase' ) as $case ) {
	// Data provider cases are "test_name with data set ...": treat them as the test.
	$name    = preg_replace( '/ with data set.*$/s', '', (string) $case['name'] );
	$test_id = (string) $case['class'] . '::' . $name;

	$ran[ $test_id ] = true;
	if ( isset( $case->failure ) || isset( $case->error ) ) {
		$failing[ $test_id ] = true;
	}
}

if ( 0 === count( $ran ) ) {
	fwrite( STDERR, "No tests ran.\n" );
	exit( 2 );
}

$unexpected = array_diff_key( $failing, $known );
$stale      = array();
foreach ( array_keys( $known ) as $test_id ) {
	if ( ! isset( $ran[ $test_id ] ) ) {
		$stale[ $test_id ] = 'did not run';
	} elseif ( ! isset( $failing[ $test_id ] ) ) {
		$stale[ $test_id ] = 'now passes';
	}
}

printf(
	"\nConformance: %d distinct tests ran, %d failed, %d expected to fail.\n",
	count( $ran ),
	count( $failing ),
	count( $known )
);

foreach ( array_keys( $unexpected ) as $test_id ) {
	echo "  UNEXPECTED FAILURE: {$test_id}\n";
}
foreach ( $stale as $test_id => $why ) {
	echo "  STALE ENTRY ({$why}): {$test_id}: remove it from known-failures.txt\n";
}

if ( $unexpected || $stale ) {
	exit( 1 );
}

echo "  OK: every failure is a documented, expected one.\n";
