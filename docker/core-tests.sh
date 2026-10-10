#!/bin/sh
# Run WordPress core's own PHPUnit groups against this drop-in, then compare the
# failures with tests/conformance/known-failures.txt. Runs inside the wp-tests
# container (see `make test-core`).
set -u

cd "${WP_TESTS_DIR:-/opt/wordpress-develop}"

# The core groups that lean hardest on the object cache.
GROUPS_TO_RUN="${CORE_GROUPS:-cache,option,meta,taxonomy,user,comment,post}"
JUNIT=/tmp/core-junit.xml
rm -f "$JUNIT"

# PHPUnit exits non-zero because of the known failures; check.php decides.
php ./vendor/bin/phpunit --group "$GROUPS_TO_RUN" --log-junit "$JUNIT" "$@"

php /opt/object-cache-apcu/tests/conformance/check.php "$JUNIT" /opt/object-cache-apcu/tests/conformance/known-failures.txt
