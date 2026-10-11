<?php
/**
 * Rector configuration: upgrades syntax to PHP 7.4, the minimum supported version.
 *
 * Run `make rector` to see what it would change and `make rector-fix` to apply it.
 * Anything it changes must still pass `make lint` (WordPress Coding Standards).
 *
 * @package object-cache-apcu
 */

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
	->withPaths(
		array(
			__DIR__ . '/object-cache.php',
			__DIR__ . '/tests',
		)
	)
	->withSets( array( LevelSetList::UP_TO_PHP_74 ) )
	// WordPress Coding Standards require array(), not [].
	->withSkip( array( LongArrayToShortArrayRector::class ) );
