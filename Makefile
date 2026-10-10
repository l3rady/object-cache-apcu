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

.DEFAULT_GOAL := help
.PHONY: help build shell composer deps lint fix smoke test-core matrix clean

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

fix: deps ## Auto-fix coding standard violations with phpcbf
	$(COMPOSE) run --build --rm php sh -c 'vendor/bin/phpcbf; s=$$?; [ $$s -le 1 ]'

smoke: ## Verify PHP, APCu, mysqli and that object-cache.php lints
	$(COMPOSE) run --build --rm php sh docker/smoke.sh

test-core: ## Run WordPress core's cache tests against the drop-in (WP_VERSION)
	$(COMPOSE) run --build --rm wp-tests $(if $(ARGS),$(ARGS),php ./vendor/bin/phpunit --group cache)

matrix: ## Smoke test every PHP version in MATRIX
	@for v in $(MATRIX); do \
		echo "=== PHP $$v ==="; \
		PHP_VERSION=$$v $(COMPOSE) run --build --rm php sh docker/smoke.sh || exit 1; \
	done

clean: ## Remove containers, volumes and built images
	$(COMPOSE) down --volumes --remove-orphans
	-docker image ls --format '{{.Repository}}:{{.Tag}}' | grep '^object-cache-apcu:' | xargs -r docker image rm
