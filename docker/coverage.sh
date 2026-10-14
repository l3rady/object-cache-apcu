#!/bin/sh
# Runs the plain APCu tests with coverage and fails if line coverage of
# object-cache.php is below the minimum. Run inside the coverage container:
#   make coverage                    # minimum 95 (%)
#   make coverage COVERAGE_MIN=98
set -eu

min="${COVERAGE_MIN:-95}"
clover="$(mktemp)"
trap 'rm -f "$clover"' EXIT

vendor/bin/phpunit --coverage-text --coverage-clover "$clover" "$@"

php -r '
	$metrics = simplexml_load_file( $argv[1] )->project->metrics;
	$total   = (int) $metrics["statements"];
	$covered = (int) $metrics["coveredstatements"];
	$percent = $total ? 100 * $covered / $total : 0;
	$min     = (float) $argv[2];

	printf( "Line coverage: %.2f%% (%d of %d), minimum %s%%\n", $percent, $covered, $total, $argv[2] );
	if ( $percent < $min ) {
		fwrite( STDERR, "FAIL: coverage is below the minimum. Add tests for the code you changed.\n" );
		exit( 1 );
	}
' "$clover" "$min"
