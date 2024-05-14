# WordPress APCu Object Cache Backend #
Contributors: l3rady  
Tags: APCu, object cache, backend, cache, performance, speed  
Requires at least: 5.6  
Tested up to: 6.5.3  
Stable tag: 1.2

WordPress APCu Object Cache Backend provides a persistent memory-based backend for the WordPress object cache.

## Description ##
WordPress APCu Object Cache Backend provides a persistent memory-based backend for the WordPress object cache.

An object cache is a place for WordPress and WordPress extensions to store the results of complex operations. On subsequent loads,
this data can be fetched from the cache, which will be must faster than dynamically generating it on every page load.

Be sure to read the installation instructions, as this is **not** a traditional plugin, and needs to be installed in a specific location.

This object cache is a fork of [Mark Jaquith's APC Object Cache Backend](https://wordpress.org/plugins/apc/). There are a number of bugs in that version that have been
ignored so I decided to write my own version. The object cache has been pretty much been re-written but some of the best bits from Marks
version has been cherry picked over to this version.

## Composer Installation ##
This plugin is marked as a wordpress-dropin type and can be installed automatically via composer using [composer-dropin-installer](https://packagist.org/packages/koodimonni/composer-dropin-installer).

Once the composer-dropin-installer is installed and configured in your project you can run `composer require l3rady/object-cache-apcu`. If set up correctly this should place the object-cache.php file into the your WordPress content dir, usually `wp-content`.

## Manual Installation ##
1. Verify that you have PHP 7.2+ and a compatible APCu version installed.
2. Copy `object-cache.php` to your WordPress content directory (`wp-content/` by default).
3. Done!

## Frequently Asked Questions ##
### I share `wp-config.php` among multiple WordPress installs. How can I guarantee key uniqueness? ###

Define `WP_APCU_KEY_SALT` to something that is unique for each install (like an md5 of the MySQL host, database, and table prefix).

## Changelog ##
### 1.2 ###
+ Add composer file and add dropin plugin to packagist.org for easy installation via composer.
+ Add latest cache methods added to core recentlys
### 1.1 ###
+ Add local array cache to reduce APCu calls for repeated requests to same keys during page load. Props to [rob006](https://github.com/rob006)
+ Add `WP_APCU_LOCAL_CACHE` define to disable local cache for edge cases where local cache can cause memory exhaustion issues
### 1.0.1 ###
+ Make `$cache_hits` and `$cache_misses` public params for stats plugins to access
### 1.0 ###
+ Initial release, forked from [WordPress-APC-Object-Cache](https://github.com/l3rady/WordPress-APC-Object-Cache)