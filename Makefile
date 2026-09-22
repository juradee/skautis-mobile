# Everything runs in Docker - no PHP, Composer or Node needed on the host.

DC  := docker compose
PHP := $(DC) exec -T php

.DEFAULT_GOAL := help

help: ## List available targets
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk -F':.*?## ' '{printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build the PHP image
	$(DC) build

install: ## Install PHP and frontend dependencies (needed after a fresh clone)
	$(DC) run --rm --no-deps php composer install

up: ## Start the stack (http://skautis.localhost)
	$(DC) up -d

down: ## Stop the stack
	$(DC) down

restart: down up ## Restart the stack

logs: ## Tail logs from all services
	$(DC) logs -f

sh: ## Shell inside the PHP container
	$(DC) exec php sh

composer: ## Run Composer, e.g. make composer c="require foo/bar"
	$(PHP) composer $(c)

console: ## Run bin/console, e.g. make console c="debug:router"
	$(PHP) php bin/console $(c)

cc: ## Clear the Symfony cache
	$(PHP) php bin/console cache:clear

test: ## Run PHPUnit
	$(PHP) php bin/phpunit

test-bundle: ## Run the bundle's own suite against its own dependencies
	$(DC) run --rm --no-deps -w /app/packages/skautis-symfony php sh -c 'test -d vendor || composer install --no-interaction; vendor/bin/phpunit'

stan: ## Run PHPStan
	$(PHP) vendor/bin/phpstan analyse

cs: ## Fix coding standards (aplikace i balíček mají vlastní sadu pravidel)
	$(PHP) vendor/bin/php-cs-fixer fix
	$(PHP) vendor/bin/php-cs-fixer fix --config packages/skautis-symfony/.php-cs-fixer.dist.php

.PHONY: help build install up down restart logs sh composer console cc test test-bundle stan cs
