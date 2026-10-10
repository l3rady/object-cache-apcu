# WordPress APCu Object Cache Backend #
Contributors: l3rady  
Tags: APCu, object cache, backend, cache, performance, speed  
Requires at least: 5.6  
Tested up to: 6.5.3  
Stable tag: 1.2.0 <!-- x-release-please-version -->

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

## Development ##
Everything runs in Docker, so the only host requirements are `docker` and `docker compose`. No local PHP is needed.

```
make smoke                        # build the image and sanity-check PHP, APCu, mysqli and the drop-in
make smoke PHP_VERSION=7.4        # any PHP from 7.4 to 8.5 (default 8.3)
make matrix                       # smoke test every supported PHP version
make test-core                    # WordPress core's own cache tests against the drop-in (WP_VERSION, default 7.1.3)
make shell                        # shell in the PHP container
make composer ARGS="install"      # run composer in the container
make lint                         # check WordPress Coding Standards (phpcs); must pass with no errors or warnings
make fix                          # auto-fix what phpcbf can
```

Code must follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) (tabs, `array()`, Yoda conditions, snake_case) and stay compatible with PHP 7.4+. The rules live in `phpcs.xml.dist`; any exclusion needs a reason there. `make lint` also runs in CI.

The images are defined in `docker/Dockerfile` (`dev`, `coverage` and `wp-tests` targets, with an `fpm` flavour via `PHP_VARIANT`) and wired together in `docker-compose.yml`.

### Commits and releases ###
Commits follow [Conventional Commits](https://www.conventionalcommits.org/) and drive the version number, but **merging to `main` never changes the version**. [release-please](https://github.com/googleapis/release-please) keeps a "Release PR" open that collects the next version, the `CHANGELOG.md` entry and the version bumps in `object-cache.php` and this README. **Merging that PR cuts the release** (a `vX.Y.Z` tag and GitHub release, which Packagist picks up).

| Commit | Release |
|---|---|
| `fix:` | patch |
| `feat:` | minor |
| `feat!:` / `refactor!:` or a `BREAKING CHANGE:` footer | major |
| `docs:`, `chore:`, `build:`, `ci:`, `test:`, `style:` | none on their own |

To force a specific version, add a `Release-As: X.Y.Z` footer to a commit on `main`.

## Changelog ##
See [CHANGELOG.md](CHANGELOG.md).
