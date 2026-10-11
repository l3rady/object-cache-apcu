#!/bin/sh
# Installs the dist archive of HEAD the way Composer users do and checks what
# lands in the content directory. Run with `make test-composer`.
#
# The package comes from a local `package` repository so the archive under test
# is used, not whatever Packagist has. Only the installer plugin is downloaded.
set -eu

repo="$(cd "$(dirname "$0")/.." && pwd)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

fail() {
	echo "FAIL: $*" >&2
	exit 1
}

archive() { # version, output file: HEAD's dist archive with the version header changed
	rm -rf "$work/src" && mkdir "$work/src"
	git -C "$repo" archive HEAD | tar -x -C "$work/src"
	sed -i "s/^ \* Version: .*/ * Version: $1/" "$work/src/object-cache.php"
	(cd "$work/src" && tar -czf "$2" ./*)
}

# --- the archive itself ------------------------------------------------------
expected="CHANGELOG.md
LICENSE
README.md
composer.json
object-cache.php"
actual="$(git -C "$repo" archive HEAD | tar -t | sort)"
[ "$actual" = "$expected" ] || fail "the dist archive holds the wrong files (a new file must be export-ignored in .gitattributes, or added here on purpose):
$actual"
echo "ok   dist archive holds exactly: $(echo "$expected" | tr '\n' ' ')"

archive 1.0.0 "$work/1.0.0.tgz"
archive 1.0.1 "$work/1.0.1.tgz"

# --- installs ----------------------------------------------------------------
project() { # name, target directory, rule, version
	dir="$work/$1"
	mkdir -p "$dir/$2" && cd "$dir"
	cat > composer.json <<JSON
{
  "repositories": [{ "type": "package", "package": [
    { "name": "l3rady/object-cache-apcu", "version": "1.0.0", "type": "wordpress-dropin", "dist": { "url": "$work/1.0.0.tgz", "type": "tar" } },
    { "name": "l3rady/object-cache-apcu", "version": "1.0.1", "type": "wordpress-dropin", "dist": { "url": "$work/1.0.1.tgz", "type": "tar" } }
  ] }],
  "require": { "koodimonni/composer-dropin-installer": "^1.4", "l3rady/object-cache-apcu": "$4" },
  "extra": { "dropin-paths": { "$2/": ["$3"] } },
  "config": { "allow-plugins": { "koodimonni/composer-dropin-installer": true } }
}
JSON
	composer install --no-interaction --no-progress --quiet
}

only_the_dropin() { # name, target directory
	found="$(cd "$work/$1/$2" && find . -type f | sort)"
	[ "$found" = "./object-cache.php" ] || fail "$1: expected only object-cache.php in $2, found:
$found"
}

check() { # name, target directory, rule
	project "$1" "$2" "$3" 1.0.0
	only_the_dropin "$1" "$2"
	grep -q "Version: 1.0.0" "$work/$1/$2/object-cache.php" || fail "$1: wrong file installed"
	echo "ok   $1: only object-cache.php in $2/"
}

check "package-rule" "wp-content" "package:l3rady/object-cache-apcu:object-cache.php"
check "type-rule" "wp-content" "type:wordpress-dropin"
check "custom-content-dir" "web/app" "package:l3rady/object-cache-apcu:object-cache.php"

# --- the file is the one in the repository -----------------------------------
git -C "$repo" show HEAD:object-cache.php | sed "s/^ \* Version: .*/ * Version: 1.0.0/" > "$work/expected.php"
cmp -s "$work/expected.php" "$work/package-rule/wp-content/object-cache.php" \
	|| fail "the installed object-cache.php differs from the one in the repository"
echo "ok   the installed file is the repository's object-cache.php (apart from the version header)"

# --- updating replaces the file ----------------------------------------------
cd "$work/package-rule"
sed -i 's/"l3rady\/object-cache-apcu": "1.0.0"/"l3rady\/object-cache-apcu": "1.0.1"/' composer.json
composer update l3rady/object-cache-apcu --no-interaction --no-progress --quiet
grep -q "Version: 1.0.1" wp-content/object-cache.php || fail "composer update did not replace the drop-in"
only_the_dropin "package-rule" "wp-content"
echo "ok   composer update replaces the drop-in"

echo "Composer install checks passed."
