#!/bin/sh
# Smoke test for the Docker environment. Run inside the php container:
#   make smoke PHP_VERSION=7.4
set -eu

fail() { echo "FAIL: $*" >&2; exit 1; }

echo "PHP:   $(php -r 'echo PHP_VERSION;') ($(php -r 'echo PHP_SAPI;'))"

php -r 'extension_loaded("apcu") || exit(1);' || fail "apcu extension not loaded"
echo "APCu:  $(php -r 'echo phpversion("apcu");')"

php -r 'exit(function_exists("apcu_enabled") && apcu_enabled() ? 0 : 1);' \
    || fail "APCu is not enabled (check apc.enable_cli)"

php -r '
    apcu_store("smoke", ["ok" => true]);
    $v = apcu_fetch("smoke", $hit);
    exit($hit && $v === ["ok" => true] ? 0 : 1);
' || fail "apcu_store/apcu_fetch round trip"
echo "APCu:  store/fetch round trip ok"

php -r 'extension_loaded("mysqli") || exit(1);' || fail "mysqli extension not loaded"

php -l object-cache.php >/dev/null || fail "object-cache.php does not lint on this PHP version"
echo "Lint:  object-cache.php ok"

echo "OK"
