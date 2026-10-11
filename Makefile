# Everything runs in Docker. The only host requirement is docker + docker compose.
#
#   make smoke                       # sanity-check the environment
#   make smoke PHP_VERSION=7.4       # pick a PHP version (default 8.3)
#   make matrix                      # smoke test every supported PHP version

PHP_VERSION ?= 8.3
WP_VERSION  ?= 7.1.3
MATRIX      ?= 7.4 8.0 8.1 8.2 8.3 8.4 8.5

export PHP_VERSION
export WP_VERSION
export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

COMPOSE := docker compose

# The commits `make commits` checks: everything on this branch that is not on main.
COMMITS_FROM ?= origin/main
COMMITS_TO   ?= HEAD

.DEFAULT_GOAL := help
.PHONY: help build shell composer deps lint commits fix smoke test test-apcu test-known-bugs coverage test-wp test-wp-ms test-core test-composer stan rector rector-fix matrix clean bench

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "} {printf "  %-12s %s\n", $$1, $$2}'

build: ## Build the PHP image for PHP_VERSION
	$(COMPOSE) build php

shell: ## Open a shell in the PHP container
	$(COMPOSE) run --rm php bash

composer: ## Run composer, e.g. make composer ARGS="require --dev foo/bar"
	$(COMPOSE) run --rm composer $(ARGS)

vendor/autoload.php: composer.json composer.lock
	$(COMPOSE) run --rm composer install --no-interaction --no-progress
	@touch $@

deps: vendor/autoload.php ## Install Composer dev dependencies (phpcs, WPCS)

lint: deps ## Check WordPress Coding Standards (fails on errors and warnings)
	$(COMPOSE) run --build --rm php vendor/bin/phpcs

commits: ## Check that the commits on this branch follow Conventional Commits
	$(COMPOSE) run --build --rm commitlint --from $(COMMITS_FROM) --to $(COMMITS_TO) --verbose

fix: deps ## Auto-fix coding standard violations with phpcbf
	$(COMPOSE) run --build --rm php sh -c 'vendor/bin/phpcbf; s=$$?; [ $$s -le 1 ]'

smoke: ## Verify PHP, APCu, mysqli and that object-cache.php lints
	$(COMPOSE) run --build --rm php sh docker/smoke.sh

stan: deps ## Static analysis with PHPStan (WordPress stubs)
	$(COMPOSE) run --build --rm php vendor/bin/phpstan analyse --no-progress --memory-limit=1G $(ARGS)

rector: deps ## Show the PHP 7.4 syntax upgrades Rector would make (changes nothing)
	$(COMPOSE) run --build --rm php vendor/bin/rector process --dry-run --no-progress-bar $(ARGS)

rector-fix: deps ## Apply Rector's upgrades, then run make fix and make lint
	$(COMPOSE) run --build --rm php vendor/bin/rector process --no-progress-bar $(ARGS)

test-apcu: deps ## Run the plain APCu tests (PHPUnit, no WordPress)
	$(COMPOSE) run --build --rm php vendor/bin/phpunit $(ARGS)

test-known-bugs: deps ## Run tests that document open bugs (they are expected to fail)
	$(COMPOSE) run --build --rm php vendor/bin/phpunit --exclude-group none --group known-bug $(ARGS)

test-composer: deps ## Install the dist archive with Composer and check what lands in the content directory
	$(COMPOSE) run --build --rm php sh docker/composer-install-test.sh

bench: deps ## Time the *_multiple functions against loops of single calls
	$(COMPOSE) run --build --rm php php tests/bench/multiple.php

coverage: deps ## Run the plain APCu tests with a coverage report (pcov)
	$(COMPOSE) run --build --rm coverage vendor/bin/phpunit --coverage-text $(ARGS)

test-wp: ## Run our WordPress-based tests against a real WordPress (single site)
	$(COMPOSE) run --build --rm wp-tests sh /opt/object-cache-apcu/docker/wp-tests.sh $(ARGS)

test-wp-ms: ## Run our WordPress-based tests as multisite
	$(COMPOSE) run --build --rm -e WP_MULTISITE=1 wp-tests sh /opt/object-cache-apcu/docker/wp-tests.sh $(ARGS)

test-core: ## Run WordPress core's own tests (cache, options, meta, ...) against the drop-in
	$(COMPOSE) run --build --rm wp-tests sh /opt/object-cache-apcu/docker/core-tests.sh $(ARGS)

test: test-apcu test-wp test-wp-ms test-core test-composer ## Run every test layer

matrix: deps ## Smoke test and run the plain APCu tests on every PHP version in MATRIX
	@for v in $(MATRIX); do \
		echo "=== PHP $$v ==="; \
		PHP_VERSION=$$v $(COMPOSE) run --build --rm php sh -c 'sh docker/smoke.sh && vendor/bin/phpunit' || exit 1; \
	done

clean: ## Remove containers, volumes and built images
	$(COMPOSE) down --volumes --remove-orphans
	-docker image ls --format '{{.Repository}}:{{.Tag}}' | grep '^object-cache-apcu:' | xargs -r docker image rm
