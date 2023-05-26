.DEFAULT_GOAL := help
install: ## Init project
	cp -n .env.dist .env
	docker-compose build
	docker-compose run php composer install

start: ## Run docker for a project
	docker-compose up -d

stop: ## Stop all containers for a project
	docker-compose down --remove-orphans

bash: ## Exec bash for php container
	docker-compose exec php bash

phpstan: ## Run static analysis a code for a php container
	docker-compose exec php composer phpstan

phpunit: ## Run tests for a php container
	docker-compose exec php composer test

cs-check: ## Run check for a linter
	docker-compose exec php composer cs:check

cs-fix: ## Run linter
	docker-compose exec php composer cs:fix

run-tests: ## Run stage for test
	$(MAKE) cs-check
	$(MAKE) phpunit

fix-permissions: ## Change permision for volumen a php container
	docker-compose exec php	usermod -u 1000 www-data

composer-update: ## Run composer update for php container
	docker-compose exec php composer update

kill-all: ## Kill all running containers
	docker container kill $$(docker container ls -q)

openapi: ## Generate documentation for api
	docker-compose exec php vendor/bin/openapi /var/www/src --output resources/docs/openapi.yaml

db-create: ## Create db from doctrine schema
	docker-compose exec php php vendor/bin/doctrine orm:schema-tool:create

db-seed: ## Run seeder to db
	docker-compose exec php php bin/console.php db:seed

.PHONY: help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
