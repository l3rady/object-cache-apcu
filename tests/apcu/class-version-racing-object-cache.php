<?php
/**
 * A cache that loses the race to create a version, for the version token tests.
 *
 * @package object-cache-apcu
 */

/**
 * A cache where another request creates a version key just after this one finds
 * it missing and before it writes its own.
 */
class Version_Racing_Object_Cache extends WP_Object_Cache {

	/**
	 * The version key the other request creates, or null for no race.
	 *
	 * @var string|null
	 */
	public $other_request_key = null;

	/**
	 * The version the other request stores.
	 *
	 * @var string
	 */
	public $other_request_version = 'from-another-request';

	/**
	 * Mints a version, and lets "another request" store its own first.
	 *
	 * @return string
	 */
	protected function new_cache_version(): string {
		if ( null !== $this->other_request_key ) {
			apcu_store( $this->other_request_key, $this->other_request_version );
		}

		return parent::new_cache_version();
	}
}
