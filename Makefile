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
	$(DOCKER_COMPOSE) run app composer tools:install

tools-install: ## Install isolated dev tools (tools/*: phpstan, cs-fixer, deptrac)
	$(DOCKER_COMPOSE) run app composer tools:install

tools-update: ## Update isolated dev tools to their latest allowed versions (bumps each tools/*/composer.lock)
	$(DOCKER_COMPOSE) run app composer tools:update

start: ## Run docker for a project
	$(DOCKER_COMPOSE) up -d

stop: ## Stop all containers for a project
	$(DOCKER_COMPOSE) down --remove-orphans

bash: ## Exec bash for app container
	$(DOCKER_COMPOSE) exec app bash

phpstan: ## Static analysis via isolated tools/phpstan (src + config + fixtures)
	$(DOCKER_COMPOSE) exec app composer phpstan

phpunit: ## Run tests for a app container
	$(DOCKER_COMPOSE) exec app composer test

cs-check: ## Check code style via isolated tools/cs-fixer
	$(DOCKER_COMPOSE) exec app composer cs:check

cs-fix: ## Fix code style via isolated tools/cs-fixer
	$(DOCKER_COMPOSE) exec app composer cs:fix

deptrac: ## Architecture guard via isolated tools/deptrac
	$(DOCKER_COMPOSE) exec app composer test:architecture

run-tests: ## Run stage for test
	$(MAKE) cs-check
	$(MAKE) phpstan
	$(MAKE) deptrac
	$(MAKE) phpunit

helm-lint: ## Lint the Helm chart in .k8s with the default values and both broker overlays
	helm lint .k8s
	helm lint .k8s -f .k8s/values-amqp.yaml
	helm lint .k8s -f .k8s/values-kafka.yaml

helm-template: ## Render the Helm chart in .k8s with the default values
	helm template slim4-app .k8s

helm-template-kafka: ## Render the Helm chart in .k8s with the Kafka overlay
	helm template slim4-app .k8s -f .k8s/values-kafka.yaml

fix-permissions: ## Change permision for volumen a app container
	$(DOCKER_COMPOSE) exec app	usermod -u 1000 www-data

composer-update: ## Run composer update for app container
	$(DOCKER_COMPOSE) exec app composer update

kill-all: ## Kill all running containers
	docker container kill $$(docker container ls -q)

openapi: ## Generate documentation for api
	$(DOCKER_COMPOSE) exec app vendor/bin/openapi /var/www/src --output resources/docs/openapi.json

db-create: ## Create db from doctrine schema
	$(DOCKER_COMPOSE) exec app php config/cli-config.php orm:schema-tool:create

migrate: ## Run migrations
	$(DOCKER_COMPOSE) exec app php vendor/bin/doctrine-migrations migrate

db-seed: ## Run Seeders to DB
	$(DOCKER_COMPOSE) exec app php bin/console.php db:seed

.PHONY: help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
