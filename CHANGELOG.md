# Changelog

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
