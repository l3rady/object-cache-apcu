# WordPress APCu Object Cache Backend #

Contributors: l3rady<br/>
Donate link: [http://l3rady.com/donate][2]<br/>
Tags: APCu, object cache, backend, cache, performance, speed<br/>
Requires at least: 3.3<br/>
Tested up to: 4.5.3<br/>
Stable tag: 1.0.1

WordPress APCu Object Cache Backend provides a persistent memory-based backend for the WordPress object cache.

## Description ##

WordPress APCu Object Cache Backend provides a persistent memory-based backend for the WordPress object cache.

An object cache is a place for WordPress and WordPress extensions to store the results of complex operations. On subsequent loads,
this data can be fetched from the cache, which will be must faster than dynamically generating it on every page load.

Be sure to read the installation instructions, as this is **not** a traditional plugin, and needs to be installed in a specific location.

This object cache is a fork of [Mark Jaquith's APC Object Cache Backend][1]. There are a number of bugs in that version that have been
ignored so I decided to write my own version. The object cache has been pretty much been re-written but some of the best bits from Marks
version has been cherry picked over to this version.

## Installation ##

1. Verify that you have PHP 5.4+ and a compatible APCu version installed.
2. Copy `object-cache.php` to your WordPress content directory (`wp-content/` by default).
3. Done!

## Frequently Asked Questions ##

### I share `wp-config.php` among multiple WordPress installs. How can I guarantee key uniqueness? ###

Define `WP_APCU_KEY_SALT` to something that is unique for each install (like an md5 of the MySQL host, database, and table prefix).

## Changelog ##

### 1.0.1 ###
+ Make `$cache_hits` and `$cache_misses` public params for stats plugins to access

### 1.0 ###
+ Initial release, forked from [WordPress-APC-Object-Cache][3]

[1]: https://wordpress.org/plugins/apc/
[2]: http://l3rady.com/donate
[3]: https://github.com/l3rady/WordPress-APC-Object-Cache
