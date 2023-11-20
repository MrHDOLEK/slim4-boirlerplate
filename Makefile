.DEFAULT_GOAL := help
install: ## Init project
	cp -n .env.dist .env
	docker-compose build
	docker-compose run app composer install

start: ## Run docker for a project
	docker-compose up -d

stop: ## Stop all containers for a project
	docker-compose down --remove-orphans

bash: ## Exec bash for app container
	docker-compose exec app bash

phpstan: ## Run static analysis a code for a app container
	docker-compose exec app composer phpstan

phpunit: ## Run tests for a app container
	docker-compose exec app composer test

cs-check: ## Run check for a linter
	docker-compose exec app composer cs:check

cs-fix: ## Run linter
	docker-compose exec app composer cs:fix

run-tests: ## Run stage for test
	$(MAKE) cs-check
	$(MAKE) phpunit

fix-permissions: ## Change permision for volumen a app container
	docker-compose exec app	usermod -u 1000 www-data

composer-update: ## Run composer update for app container
	docker-compose exec app composer update

kill-all: ## Kill all running containers
	docker container kill $$(docker container ls -q)

openapi: ## Generate documentation for api
	docker-compose exec app vendor/bin/openapi /var/www/src --output resources/docs/openapi.yaml

db-create: ## Create db from migrations
	$(MAKE) migrate

migrate: ## Run migrations
	docker-compose exec app php vendor/bin/doctrine-migrations migrate

db-seed: ## Run Seeders to DB
	docker-compose exec app php bin/console.php db:seed

.PHONY: help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
