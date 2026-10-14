# Changelog

## [2.0.1](https://github.com/l3rady/object-cache-apcu/compare/v2.0.0...v2.0.1) (2026-10-14)


### Bug Fixes

* **cache:** return a copy of objects read from non-persistent groups ([372b87d](https://github.com/l3rady/object-cache-apcu/commit/372b87d46b19e21b00f4a4a6e485c7a9050eb2a2)), closes [#101](https://github.com/l3rady/object-cache-apcu/issues/101) [#95](https://github.com/l3rady/object-cache-apcu/issues/95)

## [2.0.0](https://github.com/l3rady/object-cache-apcu/compare/v1.2...v2.0.0) (2026-10-11)


### ⚠ BREAKING CHANGES

* wp_cache_flush() no longer empties all of APCu, so code that relied on it to clear other applications' data must call apcu_clear_cache() itself. The key format gained an epoch, so the cache starts empty once after upgrading.
* installs that define neither WP_APCU_KEY_SALT nor WP_CACHE_KEY_SALT use a different key salt, so the cache starts empty once after upgrading. Define WP_APCU_KEY_SALT as 'wp' to keep the old keys.
* PHP 7.2 and 7.3 are no longer supported; PHP 7.4 or newer is required.
* **cache:** the public camelCase accessors on WP_Object_Cache were renamed to snake_case. Code calling the old names must switch to the new ones. The public cache_hits and cache_misses properties are unchanged.

### Features

* add the missing WP_Object_Cache class API ([4a124c8](https://github.com/l3rady/object-cache-apcu/commit/4a124c8e5be4067cc29a010e343ab783fb2a9e15)), closes [#31](https://github.com/l3rady/object-cache-apcu/issues/31)
* fall back to WP_CACHE_KEY_SALT, then the database, for the key salt ([9a4f54f](https://github.com/l3rady/object-cache-apcu/commit/9a4f54fa7d706afa148c36b819749fef513c0bab)), closes [#36](https://github.com/l3rady/object-cache-apcu/issues/36)
* make flush() empty only this install's cache ([0d86001](https://github.com/l3rady/object-cache-apcu/commit/0d86001d4b1f8bc6f9188a4d5e42ea4036e2e291)), closes [#34](https://github.com/l3rady/object-cache-apcu/issues/34)


### Bug Fixes

* **cache:** follow core semantics for incr and decr ([33aa098](https://github.com/l3rady/object-cache-apcu/commit/33aa0988349a097afd96123d6174929e55d11165)), closes [#27](https://github.com/l3rady/object-cache-apcu/issues/27)
* **cache:** list groups in stats and report APCu usage ([b566a02](https://github.com/l3rady/object-cache-apcu/commit/b566a02d37ff298488d92f188a038ba7679dcdb3)), closes [#32](https://github.com/l3rady/object-cache-apcu/issues/32)
* **cache:** make a forced read bypass the local cache ([8975a5e](https://github.com/l3rady/object-cache-apcu/commit/8975a5ec863f7c8e82f3760d9eda672c47c5d475)), closes [#29](https://github.com/l3rady/object-cache-apcu/issues/29)
* **cache:** make replace() only write when the key already exists ([c3d32e1](https://github.com/l3rady/object-cache-apcu/commit/c3d32e155d63c359c5d6841cfaaeeddc108daf58)), closes [#22](https://github.com/l3rady/object-cache-apcu/issues/22)
* **cache:** name the compare and swap retry limit and test losing the race ([770c306](https://github.com/l3rady/object-cache-apcu/commit/770c3063162784cdc353f72da7b1a15a28e2ad41))
* **cache:** never clear APCu from flush_runtime ([e8e0cd9](https://github.com/l3rady/object-cache-apcu/commit/e8e0cd93f8d7de212e9857c11e9dbe56bd04ff37)), closes [#25](https://github.com/l3rady/object-cache-apcu/issues/25)
* **cache:** reject invalid cache keys like core ([460f9f2](https://github.com/l3rady/object-cache-apcu/commit/460f9f2b11d69889f6c72c072855f1fd6e511007)), closes [#30](https://github.com/l3rady/object-cache-apcu/issues/30)
* **cache:** return true from non persistent writes ([7c262bb](https://github.com/l3rady/object-cache-apcu/commit/7c262bba2e4defcb5de98e2956308a67c94b3fd1)), closes [#26](https://github.com/l3rady/object-cache-apcu/issues/26)
* **cache:** use apcu_enabled() to decide whether APCu works ([5bb2359](https://github.com/l3rady/object-cache-apcu/commit/5bb2359d1e6aaf0beaccb1657bfce5eaf4d6e207)), closes [#28](https://github.com/l3rady/object-cache-apcu/issues/28)
* **cache:** use random version tokens so evicted versions stay lost ([4be3e60](https://github.com/l3rady/object-cache-apcu/commit/4be3e60b6676d46657adaf800fad6d7a48c4596f)), closes [#24](https://github.com/l3rady/object-cache-apcu/issues/24)
* **cache:** use the new version at once after flush_group and flush_site ([7ced093](https://github.com/l3rady/object-cache-apcu/commit/7ced093f23776c90a82e8c6925069c8a943e8be8)), closes [#23](https://github.com/l3rady/object-cache-apcu/issues/23)
* keep extra files out of the dist archive ([e8a8d66](https://github.com/l3rady/object-cache-apcu/commit/e8a8d662fb5398bfedcd94fec55b17ab00608065)), closes [#74](https://github.com/l3rady/object-cache-apcu/issues/74)


### Performance Improvements

* batch the *_multiple functions through APCu's array APIs ([4161dc6](https://github.com/l3rady/object-cache-apcu/commit/4161dc685e6ffe8f930429e6552858bbb0c52f8f)), closes [#35](https://github.com/l3rady/object-cache-apcu/issues/35)


### Code Refactoring

* **cache:** rename public accessors to snake_case ([09ba739](https://github.com/l3rady/object-cache-apcu/commit/09ba739b9099738173f41ea301402ded853beb56)), closes [#44](https://github.com/l3rady/object-cache-apcu/issues/44)
* require PHP 7.4 and use its syntax ([6d0a6a9](https://github.com/l3rady/object-cache-apcu/commit/6d0a6a9b3a0187204640ea746480768d7679a761)), closes [#21](https://github.com/l3rady/object-cache-apcu/issues/21)

## 1.2.0

* Add composer file and add dropin plugin to packagist.org for easy installation via composer.
* Add latest cache methods added to core recently.

## 1.1

* Add local array cache to reduce APCu calls for repeated requests to same keys during page load. Props to [rob006](https://github.com/rob006)
* Add `WP_APCU_LOCAL_CACHE` define to disable local cache for edge cases where local cache can cause memory exhaustion issues

## 1.0.1

* Make `$cache_hits` and `$cache_misses` public params for stats plugins to access

## 1.0

* Initial release, forked from [WordPress-APC-Object-Cache](https://github.com/l3rady/WordPress-APC-Object-Cache)
