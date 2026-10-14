<?php
/**
 * A cache that counts the versions it mints, for the flush tests.
 *
 * @package object-cache-apcu
 */

/**
 * A cache that remembers how many version tokens it has created.
 */
class Counting_Object_Cache extends WP_Object_Cache {

	/**
	 * How many version tokens have been minted.
	 *
	 * @var int
	 */
	public $minted = 0;

	/**
	 * Mints a version and counts it.
	 *
	 * @return string
	 */
	protected function new_cache_version(): string {
		++$this->minted;

		return parent::new_cache_version();
	}
}
