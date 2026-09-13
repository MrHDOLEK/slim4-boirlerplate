dc := if os() == "linux" { "docker compose" } else { "docker-compose" }

[private]
default:
    @just --list

# Init project
install:
    cp -n .env.dist .env
    {{ dc }} build
    {{ dc }} run app composer install
    {{ dc }} run app composer tools:install

# Install isolated dev tools (tools/*: phpstan, cs-fixer, deptrac)
tools-install:
    {{ dc }} run app composer tools:install

# Update isolated dev tools to their latest allowed versions (bumps each tools/*/composer.lock)
tools-update:
    {{ dc }} run app composer tools:update

# Run docker for a project
start:
    {{ dc }} up -d

# Stop all containers for a project
stop:
    {{ dc }} down --remove-orphans

# Exec bash for app container
bash:
    {{ dc }} exec app bash

# Static analysis via isolated tools/phpstan (src + config + fixtures)
phpstan:
    {{ dc }} exec app composer phpstan

# Run tests for a app container
phpunit:
    {{ dc }} exec app composer test

# Check code style via isolated tools/cs-fixer
cs-check:
    {{ dc }} exec app composer cs:check

# Fix code style via isolated tools/cs-fixer
cs-fix:
    {{ dc }} exec app composer cs:fix

# Architecture guard via isolated tools/deptrac
deptrac:
    {{ dc }} exec app composer test:architecture

# Run stage for test
run-tests: cs-check phpstan deptrac phpunit

# Lint the Helm chart in .k8s with the default values and both broker overlays
helm-lint:
    helm lint .k8s
    helm lint .k8s -f .k8s/values-amqp.yaml
    helm lint .k8s -f .k8s/values-kafka.yaml

# Render the Helm chart in .k8s with the default values
helm-template:
    helm template slim4-app .k8s

# Render the Helm chart in .k8s with the Kafka overlay
helm-template-kafka:
    helm template slim4-app .k8s -f .k8s/values-kafka.yaml

# Change permision for volumen a app container
fix-permissions:
    {{ dc }} exec app usermod -u 1000 www-data

# Run composer update for app container
composer-update:
    {{ dc }} exec app composer update

# Kill all running containers
kill-all:
    docker container kill $(docker container ls -q)

# Generate documentation for api
openapi:
    {{ dc }} exec app vendor/bin/openapi /var/www/src --output resources/docs/openapi.json

# Create db from doctrine schema
db-create:
    {{ dc }} exec app php config/cli-config.php orm:schema-tool:create

# Run migrations
migrate:
    {{ dc }} exec app php vendor/bin/doctrine-migrations migrate

# Run Seeders to DB
db-seed:
    {{ dc }} exec app php bin/console.php db:seed
