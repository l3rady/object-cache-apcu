#!/bin/sh
# Builds the WordPress plugin version of this repository.
#
#   bin/build-plugin.sh
#
# Produces build/<slug>/ (what WordPress.org serves) and build/<slug>-<version>.zip:
# the plugin files, the drop-in, readme.txt, LICENSE and nothing else. The drop-in
# is copied from the repository root, so the plugin ships the same object-cache.php
# byte for byte. Run with `make plugin`.
set -eu

slug="apcu-object-cache"

cd "$(dirname "$0")/.."

fail() {
	echo "FAIL: $*" >&2
	exit 1
}

version="$(sed -n 's/^ \* Version: //p' "plugin/$slug.php" | head -n 1)"
[ -n "$version" ] || fail "no Version header in plugin/$slug.php."

# The three places that carry the version must agree, or a release would ship a mismatch.
dropin_version="$(sed -n 's/^ \* Version: //p' object-cache.php | head -n 1)"
stable_tag="$(sed -n 's/^Stable tag: //p' plugin/readme.txt | head -n 1)"
[ "$dropin_version" = "$version" ] || fail "object-cache.php is $dropin_version but the plugin is $version."
[ "$stable_tag" = "$version" ] || fail "readme.txt Stable tag is $stable_tag but the plugin is $version."

rm -rf build
mkdir -p "build/$slug"

cp -R plugin/. "build/$slug/"
cp object-cache.php LICENSE "build/$slug/"

# Empty directories are kept in git with a .gitkeep, which does not belong in the plugin.
find "build/$slug" -name .gitkeep -delete

cmp -s object-cache.php "build/$slug/object-cache.php" || fail "the built drop-in differs from the repository's."

# Nothing but the plugin may land in the build: the archive's content is the contract.
unexpected="$(find "build/$slug" -type f ! -path "build/$slug/$slug.php" ! -path "build/$slug/object-cache.php" ! -path "build/$slug/readme.txt" ! -path "build/$slug/LICENSE" ! -path "build/$slug/includes/*" ! -path "build/$slug/admin/*")"
[ -z "$unexpected" ] || fail "unexpected files in the build:
$unexpected"

# Fixed timestamps and order, so the same commit always gives the same zip.
find "build/$slug" -exec touch -d '2000-01-01 00:00:00' {} +
(cd build && find "$slug" -type f | LC_ALL=C sort | zip -X -q "$slug-$version.zip" -@)

echo "Built build/$slug-$version.zip:"
unzip -Z1 "build/$slug-$version.zip"
