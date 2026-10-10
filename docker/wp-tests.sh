#!/bin/sh
# Run this repository's own WordPress-based tests (tests/wp) with core's test
# bootstrap and a real database. Runs inside the wp-tests container; set
# WP_MULTISITE=1 for the multisite run (see `make test-wp` / `make test-wp-ms`).
set -eu

cd "${WP_TESTS_DIR:-/opt/wordpress-develop}"

# Core's default config always excludes the ms-required group; its multisite
# config is the one that runs those tests.
if [ "${WP_MULTISITE:-0}" = "1" ]; then
    CONFIG=tests/phpunit/multisite.xml
else
    CONFIG=phpunit.xml.dist
fi

exec php ./vendor/bin/phpunit -c "$CONFIG" --test-suffix .php "$@" /opt/object-cache-apcu/tests/wp
