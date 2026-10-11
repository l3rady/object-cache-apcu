# Running the APCu object cache

Things that surprise people, and the settings that matter. Where a behaviour is a
known limitation that is going to change, the issue is linked.

## Compatibility

| | Tested |
|---|---|
| PHP | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5 (every pull request) |
| WordPress | 7.1.3 (every pull request) and `trunk` (every night) |
| APCu | 5.1.28 in CI. 5.1 or newer is required; older versions without `apcu_enabled()` fall back to the `apc.enabled` setting. |

The minimum is WordPress 7.1, the release line the WordPress project itself
supports: its [Supported Versions](https://wordpress.org/documentation/article/supported-versions/)
page says only the latest major release is actively supported (security fixes
are backported to older branches as a courtesy, with no guarantee). This
project tests that line and `trunk`, and raises the minimum when a new
WordPress release makes the previous one unsupported.

The drop-in uses the `WP_Object_Cache` API that WordPress has had since 6.1 and
may well work on older versions, but they are not tested and not supported.

## One server only

APCu memory lives inside one PHP process pool on one machine. With several web
servers, each has its own cache, and invalidation on one server (a post update,
`wp_cache_flush()`) does **not** reach the others, so they serve stale data. For
more than one server use a shared cache such as Redis or Memcached. This
drop-in is for single-host sites.

## The command line and the web server do not share a cache

PHP-FPM (or Apache) and the command line (WP-CLI, cron, queue workers) each have
their own APCu memory. Even with `apc.enable_cli=1`, a CLI process has a private
cache that disappears when it exits. In practice:

- **`wp cache flush` does not flush the web cache.** It only clears the
  command line's memory.
- **Changes made from the command line are not seen by the web cache.** If
  `wp option update` or an import changes the database, the web server may keep
  serving the old cached value until it expires or is flushed there.
- Without `apc.enable_cli=1`, APCu is switched off in the CLI. The drop-in
  notices and keeps data in memory for that run only.

To empty the web cache after a CLI change, trigger a flush *from the web*
(a request that calls `wp_cache_flush()`, for example from a small mu-plugin
behind a secret URL or an admin action) or restart PHP-FPM, which empties APCu.

## Recommended php.ini

| Setting | Suggestion | Why |
|---|---|---|
| `apc.shm_size` | 64M to 256M | The default (32M) is small for a busy site. When the cache is full APCu has to remove entries, and the site spends its time refilling the cache. Check **APCu Expunges** in the cache stats; it should stay at or near 0. |
| `apc.ttl` | `3600` or more | How long an entry may sit unused before it can be removed to make room. `0` (the default) means APCu does not remove idle entries to make room; the APCu documentation warns the cache "could potentially fill up with stale entries while newer entries won't be cached". |
| `apc.gc_ttl` | leave at the default | Time deleted entries stay around for readers still using them. |
| `apc.entries_hint` | roughly the number of entries you expect | Sizes the lookup table; too small slows lookups. |
| `apc.enable_cli` | `1` only if you want a per-run cache in CLI | See above. It never shares data with the web server. |

A cache that seems slower than none usually has a `shm_size` that is too small
(constant expunges), or an `apc.ttl` of `0` with a full cache, so new writes are
refused and the site pays for the attempts without getting a cache. The stats
output (`$wp_object_cache->stats()`) lists APCu entries, memory used, hit rate
and expunges, which is the first thing to look at.

## The local cache (`WP_APCU_LOCAL_CACHE`)

During one request the drop-in keeps a copy of every value it reads or writes in
a PHP array, so repeat reads do not go back to APCu. This is on by default.
Define `WP_APCU_LOCAL_CACHE` as `false` to turn it off.

- Local copies do not expire; they only live as long as the request.
- Another process changing a value is not seen through the local copy. Pass
  `$force = true` to `wp_cache_get()` to read APCu instead.
- In very long runs (imports, WP-CLI loops) the local copies and the
  non-persistent groups grow without limit. Call `wp_cache_flush_runtime()`
  now and then: it empties this request's memory only and never touches APCu.

## Flushing

| Call | Effect |
|---|---|
| `wp_cache_flush()` | Makes everything this install cached unreachable. Other applications and other installs that use the same APCu are not touched. Nothing is deleted: the old entries stay until they expire or APCu needs the space (see `apc.ttl`). Define `WP_APCU_FLUSH_HARD` as `true` to delete them at once as well. |
| `wp_cache_flush_group( $group )` | Makes the group's current entries unreachable. They stay in memory until APCu removes them. |
| `wp_cache_flush_site( $site )` | The same for every group of the site, and the global groups. |
| `wp_cache_flush_runtime()` | Empties this request's memory only. |
| `$wp_object_cache->purge()` | Deletes every entry this install has in APCu right now, and returns how many. Needs `APCUIterator`, which APCu provides. |

Flushing works by changing a version token that is part of every key: one for the whole install, one per site and one per group. Memory used by the old entries is only given back when APCu removes them, so a site that flushes often should keep `apc.ttl` above `0`, or turn on `WP_APCU_FLUSH_HARD`.

If APCu itself removes one of these tokens (memory pressure), what it covered is
treated as flushed: you lose its cached data rather than risk serving data that
was meant to be gone.

## Multisite and groups

- Keys include the blog id, so each site has its own entries. `switch_to_blog()`
  changes the prefix used.
- **Global groups** (`users`, `site-options`, `blog-details`, and others that
  WordPress registers) are shared by every site.
- **Non-persistent groups** (`counts`, `plugins`, `theme_json`, and any a plugin
  registers) are kept in the request's memory only and never written to APCu.
- Sites that share one APCu pool and one `ABSPATH` need different keys; define
  `WP_APCU_KEY_SALT` or `WP_CACHE_KEY_SALT` (see the README FAQ).

## Expiry (TTL)

`$expire` is passed to APCu as the entry's lifetime in seconds. `0` means no
expiry; the entry stays until APCu needs the space (see `apc.ttl`) or it is
deleted or flushed. A negative or non-numeric value counts as `0`. Local copies
ignore TTL (see above).
