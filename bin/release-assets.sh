#!/bin/sh
# Attaches object-cache.php and its SHA-256 checksum to a GitHub release, so people
# who do not use Composer can download exactly the one file.
#
#   bin/release-assets.sh <tag> [--dry-run]
#
# Run from a checkout of the tag (the release workflow does that), so the file
# is the one that was released, not the one at the tip of a branch. With
# --dry-run the assets are built and checked but nothing is uploaded.
set -eu

tag="${1:?usage: release-assets.sh <tag> [--dry-run]}"
dry_run="${2:-}"

cd "$(dirname "$0")/.."

# The tag and the file must agree, or the wrong file would be attached.
version="$(sed -n 's/^ \* Version: //p' object-cache.php | head -n 1)"
[ "v$version" = "$tag" ] || {
	echo "The tag is $tag but object-cache.php says version $version." >&2
	exit 1
}

out="$(mktemp -d)"
trap 'rm -rf "$out"' EXIT

cp object-cache.php "$out/object-cache.php"
# sha256sum's own format, so `sha256sum -c object-cache.php.sha256` verifies a download.
(cd "$out" && sha256sum object-cache.php > object-cache.php.sha256 && sha256sum -c object-cache.php.sha256)

if [ "$dry_run" = "--dry-run" ]; then
	echo "Dry run: would upload to $tag:"
	ls -l "$out"
	exit 0
fi

gh release upload "$tag" "$out/object-cache.php" "$out/object-cache.php.sha256" --clobber
echo "Attached object-cache.php and object-cache.php.sha256 to $tag."
