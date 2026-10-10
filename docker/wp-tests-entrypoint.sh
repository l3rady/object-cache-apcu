#!/bin/sh
# Render wp-tests-config.php for the wordpress-develop checkout from the
# environment, wait for the database, then run the given command.
set -eu

cd "${WP_TESTS_DIR:-/opt/wordpress-develop}"

DB_NAME="${WP_DB_NAME:-wordpress_test}"
DB_USER="${WP_DB_USER:-root}"
DB_PASSWORD="${WP_DB_PASSWORD:-root}"
DB_HOST="${WP_DB_HOST:-db}"

sed \
    -e "s/youremptytestdbnamehere/${DB_NAME}/" \
    -e "s/yourusernamehere/${DB_USER}/" \
    -e "s/yourpasswordhere/${DB_PASSWORD}/" \
    -e "s/'localhost'/'${DB_HOST}'/" \
    wp-tests-config-sample.php > wp-tests-config.php

# The drop-in is bind-mounted at src/wp-content/object-cache.php by compose.
if [ ! -f src/wp-content/object-cache.php ]; then
    echo "warning: no object-cache.php mounted; running against core's default cache" >&2
fi

exec "$@"
