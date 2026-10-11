#!/bin/sh
# Sets the end of the copyright range in object-cache.php: "2024" becomes
# "2024-<year>" and an existing range is moved on. Run by the release workflow.
set -eu

year="${1:?usage: update-copyright-year.sh <year>}"
file="$(dirname "$0")/../object-cache.php"

if [ "$year" = "2024" ]; then
	exit 0
fi

sed -E -i "s/(Copyright 2024)(-[0-9]{4})?/\1-${year}/" "$file"
