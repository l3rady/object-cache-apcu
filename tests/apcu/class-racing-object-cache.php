<?php
/**
 * A cache whose reads can be made stale, for the compare and swap tests.
 *
 * @package object-cache-apcu
 */

/**
 * A cache whose reads can be told to go stale, as if another request had
 * changed the value between the read and the write.
 */
class Racing_Object_Cache extends WP_Object_Cache {

	/**
	 * How many reads are made stale: the value is changed behind our back after each.
	 *
	 * @var int
	 */
	public $races = 0;

	/**
	 * How many times the value was read.
	 *
	 * @var int
	 */
	public $reads = 0;

	/**
	 * Reads the value, then lets "another request" change it.
	 *
	 * @param string $key     The cache key.
	 * @param bool   $success Whether the item was found.
	 * @return mixed
	 */
	protected function fetch_for_change( $key, &$success ) {
		$value = parent::fetch_for_change( $key, $success );
		++$this->reads;

		if ( $success && $this->races > 0 ) {
			--$this->races;
			apcu_inc( $key, 100 );
		}

		return $value;
	}
}
