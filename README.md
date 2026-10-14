# WordPress APCu Object Cache Backend #
Contributors: l3rady  
Tags: APCu, object cache, backend, cache, performance, speed  
Requires at least: 7.1  
Requires PHP: 7.4  
Tested up to: 7.1.3  
Stable tag: 2.0.1 <!-- x-release-please-version -->

[![lint](https://github.com/l3rady/object-cache-apcu/actions/workflows/lint.yml/badge.svg)](https://github.com/l3rady/object-cache-apcu/actions/workflows/lint.yml) [![ci](https://github.com/l3rady/object-cache-apcu/actions/workflows/ci.yml/badge.svg)](https://github.com/l3rady/object-cache-apcu/actions/workflows/ci.yml) [![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

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

## Installation ##
Verify that you have PHP 7.4+ and APCu 5.1 or newer installed. The command line (WP-CLI, cron) has its own separate cache and does not share it with the web server; read [Running the APCu object cache](docs/operations.md) before relying on `wp cache flush` or on changes made from the CLI. This drop-in is for single-server sites.

### With Composer ###
A drop-in is one file that has to end up at `wp-content/object-cache.php`, but Composer installs whole packages into their own directory. Marking a package as `wordpress-dropin` is therefore not enough: `composer/installers` on its own puts this package in `wp-content/object-cache-apcu/`, where WordPress never loads it. Use [`koodimonni/composer-dropin-installer`](https://github.com/Koodimonni/Composer-Dropin-Installer), which copies named files out of a package:

```
composer require koodimonni/composer-dropin-installer l3rady/object-cache-apcu
```

Allow the plugin when Composer asks (or run `composer config allow-plugins.koodimonni/composer-dropin-installer true`), then tell it which file to copy and where, in your project's `composer.json`:

```json
"extra": {
    "dropin-paths": {
        "wp-content/": ["package:l3rady/object-cache-apcu:object-cache.php"]
    }
}
```

Run `composer install` and `object-cache.php` is in place.

- **Name the file** as above (`package:vendor/name:file`). A rule that matches by type (`type:wordpress-dropin`) copies every file in the package archive that the installer does not skip, so name the file.
- **The path is your real content directory**, relative to the project root: `web/app/` on a Bedrock-style layout, not `wp-content/`.
- **Check for an existing drop-in first.** The installer overwrites `object-cache.php` without a warning, including another object cache's or one you edited by hand.
- **Updates** (`composer update`) replace the file.
- **Removing** the package (`composer remove`) does not delete the file: delete `object-cache.php` yourself, or WordPress keeps loading a drop-in that is no longer managed.
- The installer's last release is from 2022. It works with Composer 2, and this project's CI installs the archive with it every night, so a break would be noticed.

### Manually ###
1. Download `object-cache.php` from the [latest release](https://github.com/l3rady/object-cache-apcu/releases/latest) (and `object-cache.php.sha256` if you want to check it: `sha256sum -c object-cache.php.sha256` in the folder you downloaded both to).
2. Copy it to your WordPress content directory (`wp-content/` by default).
3. Done!

## Operations ##
[Running the APCu object cache](docs/operations.md) covers compatibility, the recommended `php.ini` settings, why the CLI and the web server do not share a cache, the local cache, flushing, multisite and expiry.

## Frequently Asked Questions ##
### I share `wp-config.php` among multiple WordPress installs. How can I guarantee key uniqueness? ###

Every key starts with a salt and a hash of `ABSPATH`. Installs that have the same `ABSPATH` and share APCu (cloned containers, blue/green deploys, several sites in one PHP-FPM pool) must have different salts. The salt is the first of these that is set:

1. `WP_APCU_KEY_SALT`
2. `WP_CACHE_KEY_SALT`, the constant many hosts and other object caches use
3. a hash of `DB_NAME` and the table prefix, which is already different for most installs
4. `wp`, only if WordPress provides none of the above

If you share one `wp-config.php` and the same database between installs, define `WP_APCU_KEY_SALT` yourself. Empty values are ignored.

Changing the salt (including upgrading from a version before 2.0, which used `wp` when nothing was defined) starts a new, empty cache: old entries are never read again and are removed by APCu when they expire or space is needed.

## Development ##
Everything runs in Docker, so the only host requirements are `docker` and `docker compose`. No local PHP is needed.

```
make test                         # every test layer below
make commits                      # commit messages follow Conventional Commits (commits on this branch)
make stan                         # static analysis (PHPStan level 8, WordPress stubs, parsed as PHP 7.4)
make rector                       # show the PHP 7.4 syntax upgrades Rector would make (changes nothing)
make test-apcu                    # plain APCu tests (PHPUnit, no WordPress), PHP_VERSION=7.4 to 8.5
make test-wp                      # our WordPress-based tests, single site (real WordPress + database)
make test-wp-ms                   # the same, as multisite
make test-core                    # WordPress core's own tests (cache, option, meta, taxonomy, user, comment, post)
make test-composer                # install the dist archive with Composer and check only object-cache.php lands in the content directory
make test-known-bugs              # tests that document open bugs; they are expected to fail
make coverage                     # coverage of the plain APCu tests (pcov); fails below COVERAGE_MIN (98%)
make mutation                     # mutation testing (Infection): changes the code and checks a test fails; fails below 90% (slow, ~30s)
make matrix                       # smoke test + plain APCu tests on every supported PHP version
make smoke                        # build the image and sanity-check PHP, APCu, mysqli and the drop-in
make lint                         # check WordPress Coding Standards (phpcs); must pass with no errors or warnings
make fix                          # auto-fix what phpcbf can
make shell                        # shell in the PHP container
make composer ARGS="install"      # run composer in the container
```

Code must follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) (tabs, `array()`, Yoda conditions, snake_case) and stay compatible with PHP 7.4+. The rules live in `phpcs.xml.dist`; any exclusion needs a reason there. `make lint` also runs in CI.

The images are defined in `docker/Dockerfile` (`dev`, `coverage` and `wp-tests` targets, with an `fpm` flavour via `PHP_VARIANT`) and wired together in `docker-compose.yml`.

### Tests ###
There are three layers, all in Docker:

1. **Plain APCu tests** (`tests/apcu`, `make test-apcu`): PHPUnit against the drop-in with a few WordPress functions stubbed. Fast; this is where each bug fix gets its regression test. Tests tagged `@group known-bug` document a bug that is still open: they are skipped normally and fail on purpose under `make test-known-bugs`. The pull request that fixes the bug removes the tag.
2. **WordPress tests** (`tests/wp`, `make test-wp` and `make test-wp-ms`): the drop-in installed as `wp-content/object-cache.php` in a real WordPress with a database, run with core's own test bootstrap (`WP_UnitTestCase`). Includes a multisite test and a check that the drop-in really is the cache in use.
3. **Core conformance** (`make test-core`): WordPress core's own test groups that depend on the cache, run against the drop-in. Failures that are expected are listed with a reason in `tests/conformance/known-failures.txt`. The run fails on any failure that is not listed, and also on a listed test that now passes, so the list cannot go stale. `WP_VERSION` (default 7.1.3) and `CORE_GROUPS` choose the WordPress version and groups.

### Continuous integration ###
Every pull request runs, in the same Docker setup (see `.github/workflows`):

- `lint`: the WordPress Coding Standards.
- `ci` / `phpstan` and `ci` / `rector`: static analysis (`make stan`) and a check that no PHP 7.4 syntax upgrade is left undone (`make rector`).
- `ci` / `phpunit`: the plain APCu tests on every PHP version from 7.4 to 8.5.
- `ci` / `wordpress`: the WordPress tests (single site and multisite) and core conformance on PHP 7.4 and 8.5, against WordPress 7.1.3.
- `ci` / `coverage`: line coverage of `object-cache.php` by the plain APCu tests must stay at or above 98% (`make coverage`, `COVERAGE_MIN` in the Makefile).
- `ci` / `composer`: installs the dist archive with Composer's drop-in installer and checks only `object-cache.php` lands in the content directory (`make test-composer`). Also run nightly.
- `ci` / `mutation`: mutation testing with Infection, nightly and by hand (not on pull requests). It fails if fewer than 90% of the mutants in covered code are caught (`infection.json5`).
- A nightly run of the same WordPress jobs against WordPress `trunk`, to catch core changes early. Run it by hand from the Actions tab and give it a `wp_version` to test any tag or branch.

Dependabot proposes updates to GitHub Actions, the Docker base image and Composer dependencies.

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
