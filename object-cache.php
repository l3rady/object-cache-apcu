<?php
/**
 * Plugin Name: WordPress APCu Object Cache Backend
 * Plugin URI: https://github.com/l3rady/object-cache-apcu
 * Description: APCu backend for WordPress' Object Cache
 * Requires PHP: 7.4
 * x-release-please-start-version
 * Version: 1.2.0
 * x-release-please-end
 * Author: Scott Cariss
 * Author URI: https://cariss.dev
 *
 * @package object-cache-apcu
 */

/*
	Copyright 2024  Scott Cariss  (email : scott@cariss.dev)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Stop direct access.
defined( 'ABSPATH' ) || exit;


/**
 * Adds data to the cache, if the cache key does not already exist.
 *
 * @param int|string $key The cache key to use for retrieval later.
 * @param mixed      $data The data to add to the cache store.
 * @param string     $group The group to add the cache to.
 * @param int        $expire When the cache data should be expired.
 *
 * @return bool False if cache key and group already exist, true on success
 */
function wp_cache_add( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->add( $key, $data, $group, $expire );
}


/**
 * Adds multiple values to the cache in one call.
 *
 * @param array<string|int, mixed> $data   Array of keys and values to be set.
 * @param string                   $group  Optional. Where the cache contents are grouped. Default default.
 * @param int                      $expire Optional. When to expire the cache contents, in seconds.
 *                                         Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if cache key and group already exist.
 */
function wp_cache_add_multiple( array $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->add_multiple( $data, $group, $expire );
}


/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return bool Always returns True
 */
function wp_cache_close() {
	return true;
}


/**
 * Decrement numeric cache item's value
 *
 * @param int|string $key The cache key to increment.
 * @param int        $offset The amount by which to decrement the item's value. Default is 1.
 * @param string     $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_decr( $key, $offset = 1, $group = 'default' ) {
	return WP_Object_Cache::instance()->decr( $key, $offset, $group );
}


/**
 * Removes the cache contents matching key and group.
 *
 * @param int|string $key What the contents in the cache are called.
 * @param string     $group Where the cache contents are grouped.
 *
 * @return bool True on successful removal, false on failure
 */
function wp_cache_delete( $key, $group = 'default' ) {
	return WP_Object_Cache::instance()->delete( $key, $group );
}


/**
 * Deletes multiple values from the cache in one call.
 *
 * @param array<int, string|int> $keys  Array of keys under which the cache to deleted.
 * @param string                 $group Optional. Where the cache contents are grouped. Default default.
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if the contents were not deleted.
 */
function wp_cache_delete_multiple( array $keys, $group = 'default' ) {
	return WP_Object_Cache::instance()->delete_multiple( $keys, $group );
}


/**
 * Removes all cache items.
 *
 * @return bool False on failure, true on success
 */
function wp_cache_flush() {
	return WP_Object_Cache::instance()->flush();
}


/**
 * Removes all cache items from the in-memory runtime cache.
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush_runtime() {
	return WP_Object_Cache::instance()->flush_runtime();
}


/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @param int|string $key What the contents in the cache are called.
 * @param string     $group Where the cache contents are grouped.
 * @param bool       $force Whether to read APCu even if this request has a local copy.
 * @param bool       &$found Whether key was found in the cache. Disambiguates a return of false, a storable value.
 *
 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
 */
function wp_cache_get( $key, $group = 'default', $force = false, &$found = null ) {
	return WP_Object_Cache::instance()->get( $key, $group, $force, $found );
}


/**
 * Retrieves multiple values from the cache in one call.
 *
 * @param array<int, string|int> $keys  Array of keys under which the cache contents are stored.
 * @param string                 $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool                   $force Optional. Whether to force an update of the local cache
 *                                      from the persistent cache. Default false.
 * @return array<string|int, mixed> Array of return values, grouped by key. Each value is either
 *               the cache contents on success, or false on failure.
 */
function wp_cache_get_multiple( $keys, $group = 'default', $force = false ) {
	return WP_Object_Cache::instance()->get_multiple( $keys, $group, $force );
}


/**
 * Increment numeric cache item's value
 *
 * @param int|string $key The cache key to increment.
 * @param int        $offset The amount by which to increment the item's value. Default is 1.
 * @param string     $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_incr( $key, $offset = 1, $group = 'default' ) {
	return WP_Object_Cache::instance()->incr( $key, $offset, $group );
}


/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache WordPress Object Cache
 *
 * @return void
 */
function wp_cache_init() {
	// Setting this global is the job of wp_cache_init(), exactly as in core.
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}


/**
 * Replaces the contents of the cache with new data.
 *
 * @param int|string $key What to call the contents in the cache.
 * @param mixed      $data The contents to store in the cache.
 * @param string     $group Where to group the cache contents.
 * @param int        $expire When to expire the cache contents.
 *
 * @return bool False if not exists, true if contents were replaced
 */
function wp_cache_replace( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->replace( $key, $data, $group, $expire );
}


/**
 * Saves the data to the cache.
 *
 * @param int|string $key What to call the contents in the cache.
 * @param mixed      $data The contents to store in the cache.
 * @param string     $group Where to group the cache contents.
 * @param int        $expire When to expire the cache contents.
 *
 * @return bool False on failure, true on success
 */
function wp_cache_set( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->set( $key, $data, $group, $expire );
}


/**
 * Sets multiple values to the cache in one call.
 *
 * @param array<string|int, mixed> $data   Array of keys and values to be set.
 * @param string                   $group  Optional. Where the cache contents are grouped. Default default.
 * @param int                      $expire Optional. When to expire the cache contents, in seconds.
 *                                         Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false on failure.
 */
function wp_cache_set_multiple( array $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->set_multiple( $data, $group, $expire );
}


/**
 * Switch the internal blog id.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @param int $blog_id Blog ID.
 *
 * @return void
 */
function wp_cache_switch_to_blog( $blog_id ) {
	WP_Object_Cache::instance()->switch_to_blog( $blog_id );
}


/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @param string|string[] $groups A group or an array of groups to add.
 *
 * @return void
 */
function wp_cache_add_global_groups( $groups ) {
	WP_Object_Cache::instance()->add_global_groups( $groups );
}


/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @param string|string[] $groups A group or an array of groups to add.
 *
 * @return void
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	WP_Object_Cache::instance()->add_non_persistent_groups( $groups );
}


/**
 * Function was depreciated and now does nothing
 *
 * @return bool Always returns false
 */
function wp_cache_reset() {
	_deprecated_function( __FUNCTION__, '3.5', 'wp_cache_switch_to_blog()' );
	return false;
}


/**
 * Invalidate a site's object cache
 *
 * @param mixed $sites Sites ID's that want flushing.
 *                     Don't pass a site to flush current site.
 *
 * @return bool
 */
function wp_cache_flush_site( $sites = null ) {
	return WP_Object_Cache::instance()->flush_sites( $sites );
}


/**
 * Invalidate a groups object cache
 *
 * @param mixed $groups A group or an array of groups to invalidate.
 *
 * @return bool
 */
function wp_cache_flush_group( $groups = 'default' ) {
	return WP_Object_Cache::instance()->flush_group( $groups );
}


/**
 * Determines whether the object cache implementation supports a particular feature.
 *
 * @since 6.1.0
 *
 * @param string $feature Name of the feature to check for. Possible values include:
 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
 *                        'flush_runtime', 'flush_group'.
 * @return bool True if the feature is supported, false otherwise.
 */
function wp_cache_supports( $feature ) {
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
		case 'flush_group':
			return true;

		default:
			return false;
	}
}


/**
 * WordPress APCu Object Cache Backend
 *
 * The WordPress Object Cache is used to save on trips to the database. The
 * APCu Object Cache stores all of the cache data to APCu and makes the cache
 * contents available by using a key, which is used to name and later retrieve
 * the cache contents.
 */
class WP_Object_Cache {

	/**
	 * How many times incr() and decr() retry when another request changes the
	 * value while they are working on it, before giving up.
	 *
	 * @var int
	 */
	private const MAX_CAS_ATTEMPTS = 10;

	/**
	 * Properties that were public on core's cache, answered by __get().
	 *
	 * @var string[]
	 */
	private const LEGACY_PROPERTIES = array( 'cache', 'global_groups', 'blog_prefix', 'multisite' );

	/**
	 * MD5 hash of the current installation ABSPATH.
	 *
	 * @var string
	 */
	private string $abs_path;

	/**
	 * Stores if APCu is available.
	 *
	 * @var bool
	 */
	private bool $apcu_available;

	/**
	 * The sites current blog ID. This only differs if running a multi-site installations.
	 *
	 * @var int
	 */
	private int $blog_prefix;

	/**
	 * Keeps count of how many times the cache was successfully received from APCu.
	 *
	 * @var int
	 */
	public $cache_hits = 0;

	/**
	 * Keeps count of how many times the cache was not successfully received from APCu.
	 *
	 * @var int
	 */
	public $cache_misses = 0;

	/**
	 * Holds a list of cache groups that are shared across all sites in a multi-site installation.
	 *
	 * @var array<string, bool>
	 */
	private array $global_groups = array();

	/**
	 * Holds an array of versions of the retrieved groups.
	 *
	 * @var array<string, string|int>
	 */
	private array $group_versions = array();

	/**
	 * True if the current installation is a multi-site.
	 *
	 * @var bool
	 */
	private bool $multisite;

	/**
	 * Holds cache that is to be non persistent.
	 *
	 * @var array<string, mixed>
	 */
	private array $non_persistent_cache = array();

	/**
	 * Holds a list of cache groups that are not to be saved to APCu.
	 *
	 * @var array<string, bool>
	 */
	private array $non_persistent_groups = array();

	/**
	 * Holds values read from or written to APCu during this request.
	 *
	 * @var array<string, mixed>
	 */
	private array $local_cache = array();

	/**
	 * Holds an array of versions of the retrieved sites.
	 *
	 * @var array<string|int, string|int>
	 */
	private array $site_versions = array();

	/**
	 * Remembered version of this install's whole cache, changed by flush().
	 *
	 * @var string|null
	 */
	private ?string $epoch = null;

	/**
	 * Whether flush() also deletes the old entries (WP_APCU_FLUSH_HARD).
	 *
	 * @var bool
	 */
	private bool $flush_hard;

	/**
	 * Properties set from outside under names this class does not use, as core allows.
	 *
	 * @var array<string, mixed>
	 */
	private array $dynamic_properties = array();

	/**
	 * Salt prepended to every key (see resolve_key_salt()).
	 *
	 * @var string
	 */
	private string $key_salt;

	/**
	 * Whether the in-request array cache is used (WP_APCU_LOCAL_CACHE).
	 *
	 * @var bool
	 */
	private bool $local_cache_enabled;

	/**
	 * Return the global object cache, creating it if wp_cache_init() has not run yet.
	 *
	 * The cache lives in the $wp_object_cache global, exactly as with core's
	 * WP_Object_Cache, so replacing the global (or calling wp_cache_init() again)
	 * replaces the cache used by the wp_cache_*() functions.
	 *
	 * @return WP_Object_Cache
	 */
	public static function instance() {
		global $wp_object_cache;

		if ( ! $wp_object_cache instanceof self ) {
			// A drop-in owns this global, exactly as core's wp_cache_init() does.
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_object_cache = new self();
		}

		return $wp_object_cache;
	}

	/**
	 * Works out the salt that keeps this install's keys apart from other installs in the same APCu.
	 *
	 * In order: WP_APCU_KEY_SALT, then WP_CACHE_KEY_SALT (the constant most hosts and
	 * other object caches use), then a hash of the database name and table prefix, and
	 * only when WordPress does not give those either, 'wp'. An empty value counts as not set.
	 *
	 * @param string $apcu_salt  Value of WP_APCU_KEY_SALT, or empty.
	 * @param string $cache_salt Value of WP_CACHE_KEY_SALT, or empty.
	 * @param string $db_name    Value of DB_NAME, or empty.
	 * @param string $prefix     The table prefix.
	 * @return string
	 */
	private static function resolve_key_salt( string $apcu_salt, string $cache_salt, string $db_name, string $prefix ): string {
		if ( '' !== $apcu_salt ) {
			return $apcu_salt;
		}

		if ( '' !== $cache_salt ) {
			return $cache_salt;
		}

		if ( '' !== $db_name ) {
			return md5( $db_name . ':' . $prefix );
		}

		return 'wp';
	}

	/**
	 * Sets up a new object cache. Configuration is read from constants but,
	 * unlike earlier versions, the constants are no longer defined as a side effect.
	 */
	public function __construct() {
		global $blog_id;

		// Installs that share APCu and an ABSPATH need different salts: see resolve_key_salt().
		$this->key_salt = self::resolve_key_salt(
			defined( 'WP_APCU_KEY_SALT' ) ? (string) WP_APCU_KEY_SALT : '',
			defined( 'WP_CACHE_KEY_SALT' ) ? (string) WP_CACHE_KEY_SALT : '',
			defined( 'DB_NAME' ) ? (string) DB_NAME : '',
			isset( $GLOBALS['table_prefix'] ) ? (string) $GLOBALS['table_prefix'] : ''
		);

		// Define WP_APCU_LOCAL_CACHE as false to disable the local array cache
		// and force all cache to be returned from APCu.
		$this->local_cache_enabled = defined( 'WP_APCU_LOCAL_CACHE' ) ? (bool) WP_APCU_LOCAL_CACHE : true;

		// Define WP_APCU_FLUSH_HARD as true to have flush() delete this install's old
		// entries at once instead of leaving them for APCu to remove.
		$this->flush_hard = defined( 'WP_APCU_FLUSH_HARD' ) && (bool) WP_APCU_FLUSH_HARD;

		$this->abs_path = md5( ABSPATH );

		// apc.enabled can be on while APCu has switched itself off, for example in the
		// CLI without apc.enable_cli, so ask APCu itself when it can tell us.
		$this->apcu_available = extension_loaded( 'apcu' ) && ( function_exists( 'apcu_enabled' ) ? apcu_enabled() : (bool) ini_get( 'apc.enabled' ) );
		$this->multisite      = is_multisite();
		$this->blog_prefix    = $this->multisite ? (int) $blog_id : 1;
	}

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits and cache misses, the size of each group held in this
	 * request's memory (the local cache and the non persistent groups, like core
	 * does) and, when APCu is in use, how full APCu is.
	 *
	 * @return void
	 */
	public function stats() {
		echo '<p>';
		echo '<strong>Cache Hits:</strong> ' . (int) $this->cache_hits . '<br />';
		echo '<strong>Cache Misses:</strong> ' . (int) $this->cache_misses . '<br />';
		echo '</p>';
		echo '<ul>';

		foreach ( $this->get_group_sizes() as $group => $size ) {
			echo '<li><strong>Group:</strong> ' . esc_html( $group ) . ' - ( ' . number_format( $size / KB_IN_BYTES, 2 ) . 'k )</li>';
		}
		echo '</ul>';

		$this->apcu_stats();
	}

	/**
	 * Sizes the groups held in this request's memory.
	 *
	 * @return int[] Serialized size in bytes, by group name, sorted by name.
	 */
	private function get_group_sizes(): array {
		$sizes = array();
		foreach ( $this->get_grouped_cache() as $group => $values ) {
			// Mirrors WP_Object_Cache::stats(), which sizes each group by serializing it.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$sizes[ $group ] = strlen( serialize( $values ) );
		}
		ksort( $sizes );

		return $sizes;
	}

	/**
	 * Arranges what this request holds in memory (the local cache and the non
	 * persistent groups) by group and key, the shape of core's `$cache` property.
	 *
	 * Entries are stored under their full key, so the group is found by matching
	 * the key against the groups used so far in this request. If one group name
	 * is the start of another's, the longest match wins.
	 *
	 * @return array<string, array<int|string, mixed>> Values by key, by group name.
	 */
	private function get_grouped_cache(): array {
		$groups = array_keys( $this->group_versions );
		usort(
			$groups,
			static fn( $a, $b ) => strlen( $b ) - strlen( $a )
		);

		$base    = preg_quote( $this->key_salt . ':' . $this->abs_path, '/' );
		$entries = array();
		foreach ( array( $this->local_cache, $this->non_persistent_cache ) as $cache ) {
			foreach ( $cache as $key => $value ) {
				foreach ( $groups as $group ) {
					if ( 1 === preg_match( '/^' . $base . ':\d+:' . preg_quote( $group, '/' ) . ':(.*):v[^:]*$/s', (string) $key, $match ) ) {
						$entries[ $group ][ $match[1] ] = $value;
						continue 2;
					}
				}
			}
		}

		return $entries;
	}

	/**
	 * Echoes how APCu is doing: useful when the cache seems slower than none.
	 *
	 * @return void
	 */
	private function apcu_stats(): void {
		if ( ! $this->apcu_available || ! function_exists( 'apcu_cache_info' ) || ! function_exists( 'apcu_sma_info' ) ) {
			return;
		}

		$info = apcu_cache_info( true );
		$sma  = apcu_sma_info( true );
		if ( ! is_array( $info ) || ! is_array( $sma ) ) {
			return;
		}

		$total  = $sma['seg_size'] * $sma['num_seg'];
		$used   = $total - $sma['avail_mem'];
		$hits   = (int) $info['num_hits'];
		$misses = (int) $info['num_misses'];
		$rate   = ( $hits + $misses ) > 0 ? $hits / ( $hits + $misses ) * 100 : 0;

		echo '<p>';
		echo '<strong>APCu Entries:</strong> ' . (int) $info['num_entries'] . '<br />';
		echo '<strong>APCu Memory:</strong> ' . number_format( $used / MB_IN_BYTES, 1 ) . ' MB used of ' . number_format( $total / MB_IN_BYTES, 1 ) . ' MB<br />';
		echo '<strong>APCu Hit Rate:</strong> ' . esc_html( number_format( $rate, 1 ) ) . '%<br />';
		echo '<strong>APCu Expunges:</strong> ' . (int) $info['expunges'] . '<br />';
		echo '</p>';
	}

	/**
	 * Adds data to the cache, if the cache key does not already exist.
	 *
	 * @param int|string $key The cache key to use for retrieval later.
	 * @param mixed      $value The data to add to the cache store.
	 * @param string     $group The group to add the cache to.
	 * @param int        $ttl When the cache data should be expired.
	 *
	 * @return bool False if cache key and group already exist, true on success
	 */
	public function add( $key, $value, $group = 'default', $ttl = 0 ) {
		if ( function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition() ) {
			return false;
		}

		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->add_np( $key, $value );
		}

		return $this->add_apcu( $key, $value, $ttl );
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @param array<string|int, mixed> $data   Array of keys and values to be added.
	 * @param string                   $group  Optional. Where the cache contents are grouped. Default default.
	 * @param int                      $ttl    Optional. When to expire the cache contents, in seconds.
	 *                                         Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if cache key and group already exist.
	 */
	public function add_multiple( array $data, $group = 'default', $ttl = 0 ) {
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			$values = array();

			foreach ( $data as $key => $value ) {
				$values[ $key ] = $this->add( $key, $value, $group, $ttl );
			}

			return $values;
		}

		return $this->store_multiple( $data, $group, $ttl, true );
	}

	/**
	 * Adds data to APCu cache, if the cache key does not already exist.
	 *
	 * @param string $key The cache key to use for retrieval later.
	 * @param mixed  $value The data to add to the cache store.
	 * @param int    $ttl When the cache data should be expired.
	 *
	 * @return bool False if cache key and group already exist, true on success
	 */
	private function add_apcu( $key, $value, $ttl ): bool {
		if ( apcu_add( $key, $value, max( (int) $ttl, 0 ) ) ) {
			if ( $this->local_cache_enabled ) {
				$this->local_cache[ $key ] = is_object( $value ) ? clone $value : $value;
			}
			return true;
		}
		return false;
	}

	/**
	 * Adds data to non persistent cache, if the cache key does not already exist.
	 *
	 * @param string $key The cache key to use for retrieval later.
	 * @param mixed  $value The data to add to the cache store.
	 *
	 * @return bool False if cache key and group already exist, true on success
	 */
	private function add_np( $key, $value ): bool {
		if ( $this->exists_np( $key ) ) {
			return false;
		}

		return $this->set_np( $key, $value );
	}

	/**
	 * Sets the list of global groups.
	 *
	 * @param string|string[] $groups List of groups that are global.
	 *
	 * @return void
	 */
	public function add_global_groups( $groups ) {
		foreach ( (array) $groups as $group ) {
			$this->global_groups[ $group ] = true;
		}
	}

	/**
	 * Sets the list of non persistent groups.
	 *
	 * @param string|string[] $groups List of groups that are non persistent.
	 *
	 * @return void
	 */
	public function add_non_persistent_groups( $groups ) {
		foreach ( (array) $groups as $group ) {
			$this->non_persistent_groups[ $group ] = true;
		}
	}

	/**
	 * Decrement numeric cache item's value
	 *
	 * @param int|string $key The cache key to increment.
	 * @param int        $offset The amount by which to decrement the item's value. Default is 1.
	 * @param string     $group The group the key is in.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->decr_np( $key, $offset );
		}

		return $this->decr_apcu( $key, $offset );
	}

	/**
	 * Decrement numeric APCu cache item's value
	 *
	 * @param string $key The cache key to increment.
	 * @param int    $offset The amount by which to decrement the item's value. Default is 1.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	private function decr_apcu( $key, $offset ) {
		return $this->change_apcu( $key, -1 * (int) $offset );
	}

	/**
	 * Decrement numeric non persistent cache item's value
	 *
	 * @param string $key The cache key to increment.
	 * @param int    $offset The amount by which to decrement the item's value. Default is 1.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	private function decr_np( $key, $offset ) {
		return $this->change_np( $key, -1 * (int) $offset );
	}

	/**
	 * Remove the contents of the cache key in the group
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param string     $group Where the cache contents are grouped.
	 * @param bool       $deprecated Deprecated.
	 *
	 * @return bool False if the contents weren't deleted and true on success
	 */
	public function delete( $key, $group = 'default', $deprecated = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature mirrors WP_Object_Cache::delete().
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->delete_np( $key );
		}

		return $this->delete_apcu( $key );
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @param array<int, string|int> $keys  Array of keys to be deleted.
	 * @param string                 $group Optional. Where the cache contents are grouped. Default default.
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if the contents were not deleted.
	 */
	public function delete_multiple( array $keys, $group = 'default' ) {
		$values = array();

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			foreach ( $keys as $key ) {
				$deleted = $this->delete( $key, $group );

				// An invalid key was reported by delete() and cannot be an array key.
				if ( is_int( $key ) || is_string( $key ) ) {
					$values[ $key ] = $deleted;
				}
			}

			return $values;
		}

		$owners = array();
		foreach ( $keys as $key ) {
			// An invalid key was reported and left out of the result.
			if ( ! $this->is_valid_key( $key ) ) {
				continue;
			}

			$full_key            = $this->build_key( $key, $group );
			$owners[ $full_key ] = $key;
			$values[ $key ]      = true;
			unset( $this->local_cache[ $full_key ] );
		}

		if ( $owners ) {
			// Returns a list of the keys that could not be deleted, as they did not exist.
			foreach ( (array) apcu_delete( array_keys( $owners ) ) as $full_key ) {
				$values[ $owners[ $full_key ] ] = false;
			}
		}

		return $values;
	}

	/**
	 * Remove the contents of the APCu cache key in the group
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @param string $key What the contents in the cache are called.
	 *
	 * @return bool False if the contents weren't deleted and true on success
	 */
	private function delete_apcu( $key ): bool {
		unset( $this->local_cache[ $key ] );
		return apcu_delete( $key );
	}

	/**
	 * Remove the contents of the non persistent cache key in the group
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @param string $key What the contents in the cache are called.
	 *
	 * @return bool False if the contents weren't deleted and true on success
	 */
	private function delete_np( $key ): bool {
		if ( array_key_exists( $key, $this->non_persistent_cache ) ) {
			unset( $this->non_persistent_cache[ $key ] );

			return true;
		}

		return false;
	}

	/**
	 * Checks if the cached non persistent key exists
	 *
	 * @param string $key What the contents in the cache are called.
	 *
	 * @return bool True if cache key exists else false
	 */
	private function exists_np( $key ): bool {
		return array_key_exists( $key, $this->non_persistent_cache );
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * Only this install's data: other applications, and other installs that use the same
	 * APCu, keep theirs. Nothing is deleted. Changing the epoch that is part of every key
	 * makes the old entries unreachable, and APCu removes them as they expire or space is
	 * needed. Define WP_APCU_FLUSH_HARD as true to also delete them straight away.
	 *
	 * @return bool Always returns true
	 */
	public function flush() {
		$this->flush_runtime();

		if ( $this->apcu_available ) {
			if ( $this->flush_hard ) {
				$this->purge();
			}

			$this->epoch = $this->new_cache_version();
			$this->set_cache_version( $this->get_cache_version_key( 'Epoch', 0 ), $this->epoch );
		}

		return true;
	}

	/**
	 * Deletes every entry this install has in APCu, now.
	 *
	 * Entries of other applications and of installs with a different key salt or
	 * ABSPATH are left alone. This is what WP_APCU_FLUSH_HARD adds to flush().
	 *
	 * @return int How many entries were deleted.
	 */
	public function purge(): int {
		if ( ! $this->apcu_available || ! class_exists( 'APCUIterator' ) ) {
			return 0;
		}

		$this->flush_runtime();

		$deleted  = 0;
		$iterator = new APCUIterator( '/^' . preg_quote( $this->key_salt . ':' . $this->abs_path . ':', '/' ) . '/', APC_ITER_KEY );
		foreach ( $iterator as $key => $unused ) {
			if ( apcu_delete( $key ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Clears this request's in-memory state: the local cache, non persistent groups and
	 * remembered versions. Never touches APCu.
	 *
	 * @return bool Always returns true
	 */
	public function flush_runtime() {
		// Only this request's memory is cleared. APCu is persistent storage and is never touched.
		$this->non_persistent_cache = array();
		$this->local_cache          = array();
		$this->group_versions       = array();
		$this->site_versions        = array();
		$this->epoch                = null;

		return true;
	}

	/**
	 * Invalidate a group's object cache.
	 *
	 * Unlike core, which takes one group, this also accepts an array of groups.
	 *
	 * @param string|string[] $groups A group or an array of groups to invalidate.
	 *
	 * @return bool
	 */
	public function flush_group( $groups ) {
		$groups = (array) $groups;

		if ( empty( $groups ) ) {
			return false;
		}

		foreach ( $groups as $group ) {
			$this->set_group_cache_version( $group, $this->new_cache_version() );
		}

		return true;
	}

	/**
	 * Invalidate a group's object cache.
	 *
	 * The name this method had before 2.0, kept for code written against 1.x.
	 * Use flush_group(), the name core uses.
	 *
	 * @param string|string[] $groups A group or an array of groups to invalidate.
	 *
	 * @return bool
	 */
	public function flush_groups( $groups ) {
		return $this->flush_group( $groups );
	}

	/**
	 * Invalidate a site's object cache
	 *
	 * @param mixed $sites Sites ID's that want flushing.
	 *                     Don't pass a site to flush current site.
	 *
	 * @return bool
	 */
	public function flush_sites( $sites ) {
		$sites = (array) $sites;

		if ( empty( $sites ) ) {
			$sites = array( $this->blog_prefix );
		}

		// Add global groups (site 0) to be flushed.
		if ( ! in_array( 0, array_map( 'intval', $sites ), true ) ) {
			$sites[] = 0;
		}

		foreach ( $sites as $site ) {
			$this->set_site_cache_version( $site, $this->new_cache_version() );
		}

		return true;
	}

	/**
	 * Retrieves the cache contents, if it exists
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache key. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param string     $group Where the cache contents are grouped.
	 * @param bool       $force Whether to read from APCu even if this request has a local copy, so a change made by another request is seen.
	 * @param bool       &$success Whether the key was found in the cache.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 */
	public function get( $key, $group = 'default', $force = false, &$success = null ) {
		$success = false;

		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			$value = $this->get_np( $key, $success );
		} else {
			$value = $this->get_apcu( $key, $success, $force );
		}

		if ( $success ) {
			++$this->cache_hits;
		} else {
			++$this->cache_misses;
		}

		return $value;
	}

	/**
	 * Retrieves the APCu cache contents, if it exists
	 *
	 * @param string $key What the contents in the cache are called.
	 * @param bool   &$success Whether the key was found in the cache.
	 * @param bool   $force Read APCu even if there is a local copy, and refresh the copy.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 */
	private function get_apcu( $key, &$success = null, $force = false ) {
		if ( ! $force && $this->local_cache_enabled && array_key_exists( $key, $this->local_cache )
		) {
			$success = true;
			$value   = $this->local_cache[ $key ];
		} else {
			$value = apcu_fetch( $key, $success );
			if ( $this->local_cache_enabled ) {
				if ( $success ) {
					$this->local_cache[ $key ] = $value;
				} else {
					unset( $this->local_cache[ $key ] );
				}
			}
		}

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		return $value;
	}

	/**
	 * Retrieves the non persistent cache contents, if it exists
	 *
	 * @param string $key What the contents in the cache are called.
	 * @param bool   &$success Whether the key was found in the cache.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 */
	private function get_np( $key, &$success = false ) {
		if ( array_key_exists( $key, $this->non_persistent_cache ) ) {
			$success = true;
			return $this->non_persistent_cache[ $key ];
		}

		$success = false;
		return false;
	}

	/**
	 * Mints a version token that has never been used before.
	 *
	 * Versions are random tokens, not counters. A counter restarts at 0 when APCu evicts its key,
	 * which makes keys written under an old version reachable again, and two processes that flush
	 * from the same remembered value write the same next number, so one flush is lost. A token
	 * cannot collide with an earlier one, so a lost version key only costs a cache refill.
	 *
	 * @return string The new version token.
	 */
	private function new_cache_version(): string {
		return bin2hex( random_bytes( 6 ) );
	}

	/**
	 * Get the cache version of a given key
	 *
	 * If the version is missing, because it was never created or APCu evicted it, a new token is
	 * created. Everything stored under the lost version stays unreachable.
	 *
	 * @param string $key The cache key.
	 *
	 * @return string cache version
	 */
	private function get_cache_version( $key ): string {
		if ( $this->apcu_available ) {
			$version = apcu_fetch( $key );

			if ( false === $version ) {
				$version = $this->new_cache_version();

				// Add, so a version created by a concurrent request wins and both agree on it.
				if ( ! apcu_add( $key, $version ) ) {
					$existing = apcu_fetch( $key );
					$version  = false === $existing ? $version : $existing;
				}
			}
		} elseif ( array_key_exists( $key, $this->non_persistent_cache ) ) {
			$version = $this->non_persistent_cache[ $key ];
		} else {
			$version = $this->new_cache_version();

			$this->non_persistent_cache[ $key ] = $version;
		}

		return (string) $version;
	}

	/**
	 * Build cache version key
	 *
	 * @param string $type Type of key: epoch, site or group.
	 * @param mixed  $value the group or site id.
	 *
	 * @return string The key
	 */
	private function get_cache_version_key( string $type, $value ): string {
		return $this->key_salt . ':' . $this->abs_path . ':' . $type . ':' . $value;
	}

	/**
	 * Gets the version of this install's whole cache.
	 *
	 * @return string
	 */
	private function get_epoch(): string {
		$this->epoch ??= $this->get_cache_version( $this->get_cache_version_key( 'Epoch', 0 ) );

		return $this->epoch;
	}

	/**
	 * Get the groups cache version
	 *
	 * @param string $group The group to get version for.
	 *
	 * @return int|string The group cache version
	 */
	private function get_group_cache_version( $group ) {
		$this->group_versions[ $group ] ??= $this->get_cache_version(
			$this->get_cache_version_key(
				'GroupVersion',
				$group
			)
		);

		return $this->group_versions[ $group ];
	}


	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @param array<int, string|int> $keys  Array of keys under which the cache contents are stored.
	 * @param string                 $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool                   $force Optional. Whether to force an update of the local cache
	 *                                      from the persistent cache. Default false.
	 * @return array<string|int, mixed> Array of return values, grouped by key. Each value is either
	 *               the cache contents on success, or false on failure.
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$values = array();

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			foreach ( $keys as $key ) {
				$value = $this->get( $key, $group, $force );

				// An invalid key was reported by get() and cannot be an array key.
				if ( is_int( $key ) || is_string( $key ) ) {
					$values[ $key ] = $value;
				}
			}

			return $values;
		}

		// Keys this request already holds are answered from memory, the rest are
		// fetched from APCu together.
		$ordered   = array();
		$full_keys = array();
		$hits      = array();
		$fetch     = array();
		foreach ( $keys as $key ) {
			// An invalid key was reported and left out of the result.
			if ( ! $this->is_valid_key( $key ) ) {
				continue;
			}

			$full_key    = $this->build_key( $key, $group );
			$ordered[]   = $key;
			$full_keys[] = $full_key;

			if ( ! $force && $this->local_cache_enabled && array_key_exists( $full_key, $this->local_cache ) ) {
				$hits[ $full_key ] = $this->local_cache[ $full_key ];
			} else {
				$fetch[ $full_key ] = true;
			}
		}

		if ( $fetch ) {
			$found = apcu_fetch( array_keys( $fetch ) );
			$found = is_array( $found ) ? $found : array();

			foreach ( array_keys( $fetch ) as $full_key ) {
				if ( array_key_exists( $full_key, $found ) ) {
					$hits[ $full_key ] = $found[ $full_key ];
					if ( $this->local_cache_enabled ) {
						$this->local_cache[ $full_key ] = $found[ $full_key ];
					}
				} elseif ( $this->local_cache_enabled ) {
					unset( $this->local_cache[ $full_key ] );
				}
			}
		}

		foreach ( $ordered as $position => $key ) {
			$full_key = $full_keys[ $position ];

			if ( array_key_exists( $full_key, $hits ) ) {
				++$this->cache_hits;
				$value = $hits[ $full_key ];
			} else {
				++$this->cache_misses;
				$value = false;
			}

			$values[ $key ] = is_object( $value ) ? clone $value : $value;
		}

		return $values;
	}

	/**
	 * Get the sites cache version
	 *
	 * @param int $site The site to get version for.
	 *
	 * @return int|string The site cache version
	 */
	private function get_site_cache_version( $site ) {
		$this->site_versions[ $site ] ??= $this->get_cache_version(
			$this->get_cache_version_key(
				'SiteVersion',
				$site
			)
		);

		return $this->site_versions[ $site ];
	}

	/**
	 * Increment numeric cache item's value
	 *
	 * @param int|string $key The cache key to increment.
	 * @param int        $offset The amount by which to increment the item's value. Default is 1.
	 * @param string     $group The group the key is in.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->incr_np( $key, $offset );
		}

		return $this->incr_apcu( $key, $offset );
	}

	/**
	 * Increment numeric APCu cache item's value
	 *
	 * @param string $key The cache key to increment.
	 * @param int    $offset The amount by which to increment the item's value. Default is 1.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	private function incr_apcu( $key, $offset ) {
		return $this->change_apcu( $key, (int) $offset );
	}

	/**
	 * Increment numeric non persistent cache item's value
	 *
	 * @param string $key The cache key to increment.
	 * @param int    $offset The amount by which to increment the item's value. Default is 1.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	private function incr_np( $key, $offset ) {
		return $this->change_np( $key, (int) $offset );
	}

	/**
	 * Adds a signed amount to a numeric APCu cache item, like core: a value that
	 * is not numeric counts as 0 and the result never goes below 0.
	 *
	 * The change is applied with a compare and swap so that concurrent requests
	 * do not lose updates: if another request changed the value since it was
	 * read, it is read again and the change retried, up to MAX_CAS_ATTEMPTS
	 * times. The item keeps the lifetime it was stored with.
	 *
	 * @param string $key   The cache key.
	 * @param int    $delta Amount to add; negative to subtract.
	 *
	 * @return false|int False if the item does not exist or the change kept
	 *                    losing the race, otherwise its new value.
	 */
	private function change_apcu( $key, int $delta ) {
		for ( $attempt = 0; $attempt < self::MAX_CAS_ATTEMPTS; $attempt++ ) {
			$current = $this->fetch_for_change( $key, $success );
			if ( ! $success ) {
				return false;
			}

			$value = max( 0, (int) ( ( is_numeric( $current ) ? $current : 0 ) + $delta ) );

			if ( is_int( $current ) ? apcu_cas( $key, $current, $value ) : apcu_store( $key, $value ) ) {
				if ( $this->local_cache_enabled ) {
					$this->local_cache[ $key ] = $value;
				}

				return $value;
			}
		}

		return false;
	}

	/**
	 * Reads the current value of an APCu item that is about to be changed.
	 *
	 * Always reads APCu, never the local cache. A separate method so tests can
	 * simulate another request changing the value between the read and the write.
	 *
	 * @param string $key     The cache key.
	 * @param bool   $success Set to whether the item was found.
	 *
	 * @return mixed The stored value.
	 */
	protected function fetch_for_change( $key, &$success ) {
		return apcu_fetch( $key, $success );
	}

	/**
	 * Adds a signed amount to a numeric non persistent cache item, like core.
	 *
	 * @param string $key   The cache key.
	 * @param int    $delta Amount to add; negative to subtract.
	 *
	 * @return false|int False if the item does not exist, otherwise its new value.
	 */
	private function change_np( $key, int $delta ) {
		if ( ! $this->exists_np( $key ) ) {
			return false;
		}

		$value = $this->get_np( $key );
		$value = max( 0, (int) ( ( is_numeric( $value ) ? $value : 0 ) + $delta ) );

		$this->set_np( $key, $value );

		return $value;
	}

	/**
	 * Checks that a cache key is an integer or a non-empty string, like core does
	 * since WordPress 6.1. Reports anything else with _doing_it_wrong().
	 *
	 * @param mixed $key Cache key to check.
	 *
	 * @return bool True if the key can be used.
	 */
	private function is_valid_key( $key ): bool {
		if ( ! is_int( $key ) && ! is_string( $key ) ) {
			$this->invalid_key_notice(
				sprintf(
					/* translators: %s: The type of the given cache key. */
					function_exists( '__' ) ? __( 'Cache key must be an integer or a non-empty string, %s given.' ) : 'Cache key must be an integer or a non-empty string, %s given.',
					gettype( $key )
				)
			);

			return false;
		}

		if ( is_string( $key ) && '' === trim( $key ) ) {
			$this->invalid_key_notice( function_exists( '__' ) ? __( 'Cache key must not be an empty string.' ) : 'Cache key must not be an empty string.' );

			return false;
		}

		return true;
	}

	/**
	 * Reports a misused cache key.
	 *
	 * @param string $message What is wrong with the key.
	 *
	 * @return void
	 */
	private function invalid_key_notice( string $message ): void {
		if ( function_exists( '_doing_it_wrong' ) ) {
			_doing_it_wrong( 'WP_Object_Cache', $message, '6.1.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed messages plus a type name; core does not escape them either, and esc_html() may not be loaded this early.
		}
	}

	/**
	 * Checks if the given group is a non persistent group
	 *
	 * @param string $group The group to be checked.
	 *
	 * @return bool True if the group is a non persistent group else false
	 */
	private function is_non_persistent_group( $group ): bool {
		return isset( $this->non_persistent_groups[ $group ] );
	}

	/**
	 * Works out a cache key based on a given key and group
	 *
	 * @param int|string $key The key.
	 * @param string     $group The group.
	 *
	 * @return string Returns the calculated cache key
	 */
	private function build_key( $key, $group ): string {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = 0;

		if ( ! isset( $this->global_groups[ $group ] ) ) {
			$prefix = $this->blog_prefix;
		}

		$group_version = $this->get_group_cache_version( $group );
		$site_version  = $this->get_site_cache_version( $prefix );

		return $this->key_salt . ':' . $this->abs_path . ':' . $prefix . ':' . $group . ':' . $key . ':v' . $this->get_epoch() . '.' . $site_version . '.' . $group_version;
	}

	/**
	 * Replace the contents in the cache, if contents already exist
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed      $value The contents to store in the cache.
	 * @param string     $group Where to group the cache contents.
	 * @param int        $ttl When to expire the cache contents.
	 *
	 * @return bool False if not exists, true if contents were replaced
	 */
	public function replace( $key, $value, $group = 'default', $ttl = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->replace_np( $key, $value );
		}

		return $this->replace_apcu( $key, $value, $ttl );
	}

	/**
	 * Replace the contents in the APCu cache, if contents already exist
	 *
	 * @param string $key What to call the contents in the cache.
	 * @param mixed  $value The contents to store in the cache.
	 * @param int    $ttl When to expire the cache contents.
	 *
	 * @return bool False if not exists, true if contents were replaced
	 */
	private function replace_apcu( $key, $value, $ttl ): bool {
		$this->get_apcu( $key, $success );
		if ( ! $success ) {
			return false;
		}

		return $this->set_apcu( $key, $value, $ttl );
	}

	/**
	 * Replace the contents in the non persistent cache, if contents already exist
	 *
	 * @param string $key What to call the contents in the cache.
	 * @param mixed  $value The contents to store in the cache.
	 *
	 * @return bool False if not exists, true if contents were replaced
	 */
	private function replace_np( $key, $value ): bool {
		if ( ! $this->exists_np( $key ) ) {
			return false;
		}

		return $this->set_np( $key, $value );
	}

	/**
	 * Sets the data contents into the cache
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed      $value The contents to store in the cache.
	 * @param string     $group Where to group the cache contents.
	 * @param int        $ttl When the cache data should be expired.
	 *
	 * @return bool True if cache set successfully else false
	 */
	public function set( $key, $value, $group = 'default', $ttl = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		$key = $this->build_key( $key, $group );

		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->set_np( $key, $value );
		}

		return $this->set_apcu( $key, $value, $ttl );
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @param array<string|int, mixed> $data   Array of key and value to be set.
	 * @param string                   $group  Optional. Where the cache contents are grouped. Default default.
	 * @param int                      $ttl    Optional. When to expire the cache contents, in seconds.
	 *                                         Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is always true.
	 */
	public function set_multiple( array $data, $group = 'default', $ttl = 0 ) {
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			$values = array();

			foreach ( $data as $key => $value ) {
				$values[ $key ] = $this->set( $key, $value, $group, $ttl );
			}

			return $values;
		}

		return $this->store_multiple( $data, $group, $ttl, false );
	}

	/**
	 * Writes many values to APCu with one call, for add_multiple() and set_multiple().
	 *
	 * @param array<string|int, mixed> $data     Array of keys and values.
	 * @param string                   $group    Where the cache contents are grouped.
	 * @param int                      $ttl      When to expire the cache contents, in seconds.
	 * @param bool                     $only_new True to leave keys that already exist alone (add), false to overwrite (set).
	 * @return bool[] Whether each key was written, in the order given.
	 */
	private function store_multiple( array $data, $group, $ttl, bool $only_new ): array {
		$values = array_fill_keys( array_keys( $data ), false );

		if ( $only_new && function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition() ) {
			return $values;
		}

		$batch  = array();
		$owners = array();
		foreach ( $data as $key => $value ) {
			// An invalid key was reported and stays false.
			if ( ! $this->is_valid_key( $key ) ) {
				continue;
			}

			$full_key            = $this->build_key( $key, $group );
			$batch[ $full_key ]  = is_object( $value ) ? clone $value : $value;
			$owners[ $full_key ] = $key;
		}

		if ( ! $batch ) {
			return $values;
		}

		// Both return only the keys that were not written, with an error code.
		$ttl    = max( (int) $ttl, 0 );
		$failed = $only_new ? apcu_add( $batch, null, $ttl ) : apcu_store( $batch, null, $ttl );

		foreach ( $batch as $full_key => $value ) {
			if ( isset( $failed[ $full_key ] ) ) {
				if ( ! $only_new ) {
					// As set_apcu(): do not keep serving the old value from the local copy.
					unset( $this->local_cache[ $full_key ] );
				}
				continue;
			}

			$values[ $owners[ $full_key ] ] = true;
			if ( $this->local_cache_enabled ) {
				$this->local_cache[ $full_key ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Sets the data contents into the APCu cache
	 *
	 * @param string $key What to call the contents in the cache.
	 * @param mixed  $value The contents to store in the cache.
	 * @param int    $ttl When the cache data should be expired.
	 *
	 * @return bool True if cache set successfully else false
	 */
	private function set_apcu( $key, $value, $ttl ): bool {
		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		if ( apcu_store( $key, $value, max( (int) $ttl, 0 ) ) ) {
			if ( $this->local_cache_enabled ) {
				$this->local_cache[ $key ] = $value;
			}
			return true;
		}

		// APCu is full or refused the write. Do not keep serving the old value from
		// the local copy: the next read must ask APCu, which has the real state.
		unset( $this->local_cache[ $key ] );

		return false;
	}

	/**
	 * Sets the data contents into the non persistent cache
	 *
	 * @param string $key What to call the contents in the cache.
	 * @param mixed  $value The contents to store in the cache.
	 *
	 * @return bool True if cache set successfully else false
	 */
	private function set_np( $key, $value ): bool {
		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$this->non_persistent_cache[ $key ] = $value;

		return true;
	}

	/**
	 * Set the cache version for a given key
	 *
	 * @param string $key The cache key.
	 * @param string $version The version token.
	 *
	 * @return mixed
	 */
	private function set_cache_version( $key, $version ) {
		if ( $this->apcu_available ) {
			return apcu_store( $key, $version );
		}

		$this->non_persistent_cache[ $key ] = $version;

		return $version;
	}

	/**
	 * Set the version for a groups cache
	 *
	 * @param string $group The cache group.
	 * @param string $version The version token.
	 *
	 * @return void
	 */
	private function set_group_cache_version( $group, $version ): void {
		$this->set_cache_version( $this->get_cache_version_key( 'GroupVersion', $group ), $version );

		// Keys built for the rest of this request must use the new version too.
		$this->group_versions[ $group ] = $version;
	}

	/**
	 * Set the version for a sites cache
	 *
	 * @param int    $site    The site ID.
	 * @param string $version The version token.
	 *
	 * @return void
	 */
	private function set_site_cache_version( $site, $version ): void {
		$this->set_cache_version( $this->get_cache_version_key( 'SiteVersion', $site ), $version );

		// Keys built for the rest of this request must use the new version too.
		$this->site_versions[ $site ] = $version;
	}

	/**
	 * Switch the internal blog id.
	 *
	 * This changes the blog id used to create keys in blog specific groups.
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return void
	 */
	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $this->multisite ? (int) $blog_id : 1;
	}

	/**
	 * Does nothing. Core clears the non-global groups here after a blog switch, but
	 * this cache puts the blog ID in every key, so there is nothing to clear.
	 *
	 * @deprecated 3.5.0 Use switch_to_blog().
	 *
	 * @return void
	 */
	public function reset() {
		_deprecated_function( __FUNCTION__, '3.5.0', 'switch_to_blog()' );
	}

	/**
	 * Reads a property, including the ones core's cache once had as public properties.
	 *
	 * `cache` is what this request holds in memory, by group and key (read only).
	 * `global_groups`, `blog_prefix` and `multisite` map to this cache's own state.
	 *
	 * @param string $name Property name.
	 * @return mixed Null for a name that was never set.
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'cache':
				return $this->get_grouped_cache();
			case 'global_groups':
				return $this->global_groups;
			case 'blog_prefix':
				return $this->blog_prefix;
			case 'multisite':
				return $this->multisite;
			default:
				return $this->dynamic_properties[ $name ] ?? null;
		}
	}

	/**
	 * Writes a property. The legacy properties are read only: writing one is
	 * refused with a notice. Any other name is stored, as on a plain object.
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, self::LEGACY_PROPERTIES, true ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'The "%s" property of the object cache is read only.', $name ) ), '2.0.0' );
			return;
		}

		$this->dynamic_properties[ $name ] = $value;
	}

	/**
	 * Whether a property is set.
	 *
	 * @param string $name Property name.
	 * @return bool
	 */
	public function __isset( $name ) {
		if ( in_array( $name, self::LEGACY_PROPERTIES, true ) ) {
			return null !== $this->__get( $name );
		}

		return isset( $this->dynamic_properties[ $name ] );
	}

	/**
	 * Unsets a property. Only properties that were stored by __set() can be removed.
	 *
	 * @param string $name Property name.
	 * @return void
	 */
	public function __unset( $name ) {
		unset( $this->dynamic_properties[ $name ] );
	}

	/**
	 * Gets the MD5 hash of the installation ABSPATH.
	 *
	 * @return string
	 */
	public function get_abs_path() {
		return $this->abs_path;
	}

	/**
	 * Gets whether APCu is available.
	 *
	 * @return bool
	 */
	public function get_apcu_available() {
		return $this->apcu_available;
	}

	/**
	 * Gets the blog ID used to prefix keys.
	 *
	 * @return int
	 */
	public function get_blog_prefix() {
		return $this->blog_prefix;
	}

	/**
	 * Gets the number of cache hits.
	 *
	 * @return int
	 */
	public function get_cache_hits() {
		return $this->cache_hits;
	}

	/**
	 * Gets the number of cache misses.
	 *
	 * @return int
	 */
	public function get_cache_misses() {
		return $this->cache_misses;
	}

	/**
	 * Gets the list of global groups.
	 *
	 * @return array<string, bool>
	 */
	public function get_global_groups() {
		return $this->global_groups;
	}

	/**
	 * Gets the group versions retrieved so far.
	 *
	 * @return array<string, string|int>
	 */
	public function get_group_versions() {
		return $this->group_versions;
	}

	/**
	 * Gets whether this is a multisite installation.
	 *
	 * @return bool
	 */
	public function get_multisite() {
		return $this->multisite;
	}

	/**
	 * Gets the non persistent cache contents.
	 *
	 * @return array<string, mixed>
	 */
	public function get_non_persistent_cache() {
		return $this->non_persistent_cache;
	}

	/**
	 * Gets the list of non persistent groups.
	 *
	 * @return array<string, bool>
	 */
	public function get_non_persistent_groups() {
		return $this->non_persistent_groups;
	}

	/**
	 * Gets the site versions retrieved so far.
	 *
	 * @return array<string|int, string|int>
	 */
	public function get_site_versions() {
		return $this->site_versions;
	}
}
