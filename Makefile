OS := $(shell uname -s)

.DEFAULT_GOAL := help

ifeq ($(OS),Linux)
    DOCKER_COMPOSE = docker compose
else ifeq ($(OS),Darwin)
    DOCKER_COMPOSE = docker-compose
else
    DOCKER_COMPOSE = docker-compose
endif

install: ## Init project
	cp -n .env.dist .env
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) run app composer install

start: ## Run docker for a project
	$(DOCKER_COMPOSE) up -d

stop: ## Stop all containers for a project
	$(DOCKER_COMPOSE) down --remove-orphans

bash: ## Exec bash for app container
	$(DOCKER_COMPOSE) exec app bash

phpstan: ## Run static analysis a code for a app container
	$(DOCKER_COMPOSE) exec app composer phpstan

phpunit: ## Run tests for a app container
	$(DOCKER_COMPOSE) exec app composer test

cs-check: ## Run check for a linter
	$(DOCKER_COMPOSE) exec app composer cs:check

cs-fix: ## Run linter
	$(DOCKER_COMPOSE) exec app composer cs:fix

run-tests: ## Run stage for test
	$(MAKE) cs-check
	$(MAKE) phpunit

fix-permissions: ## Change permision for volumen a app container
	$(DOCKER_COMPOSE) exec app	usermod -u 1000 www-data

composer-update: ## Run composer update for app container
	$(DOCKER_COMPOSE) exec app composer update

kill-all: ## Kill all running containers
	docker container kill $$(docker container ls -q)

openapi: ## Generate documentation for api
	$(DOCKER_COMPOSE) exec app vendor/bin/openapi /var/www/src --output resources/docs/openapi.json

db-create: ## Create db from migrations
	$(MAKE) migrate

migrate: ## Run migrations
	$(DOCKER_COMPOSE) exec app php vendor/bin/doctrine-migrations migrate

db-seed: ## Run Seeders to DB
	$(DOCKER_COMPOSE) exec app php bin/console.php db:seed

.PHONY: help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
