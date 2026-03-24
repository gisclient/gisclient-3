COMPOSE ?= docker compose
PHP_SERVICE ?= author-be
DOCKER ?= docker
PLATFORM ?= linux/amd64
BUILD_NUMBER ?= local
BACKEND_IMAGE ?= ghcr.io/gisclient/gisclient-3-backend
FRONTEND_IMAGE ?= ghcr.io/gisclient/gisclient-3-frontend
APP_VERSION ?= $(shell scripts/release-metadata.sh parse-version)
GIT_SHA ?= $(shell scripts/release-metadata.sh git-sha)
OCI_SOURCE ?= $(shell scripts/release-metadata.sh source-url)
VERSIONED_TAG ?= $(APP_VERSION)-$(BUILD_NUMBER)

.PHONY: start up down clean deps db-upgrade test test-ci phpstan phpstan-ci ecs ecs-ci rector rector-ci quality quality-ci ecs-fix rector-fix quality-fix cache-clear version-file build-backend build-frontend

start: version-file
	$(COMPOSE) up -d --build
	$(MAKE) deps
	$(MAKE) cache-clear
	$(MAKE) db-upgrade

up: version-file
	$(COMPOSE) up --build

down:
	$(COMPOSE) down

clean:
	$(COMPOSE) down -v --remove-orphans

deps:
	$(COMPOSE) exec -T -u 0 $(PHP_SERVICE) sh -lc 'COMPOSER_ALLOW_SUPERUSER=1 composer install'

db-upgrade:
	sh -lc 'until $(COMPOSE) exec -T $(PHP_SERVICE) php -r '\''$$host = getenv("DB_HOST"); $$port = getenv("DB_PORT") ?: "5432"; $$dbname = getenv("DB_DBNAME"); $$user = getenv("DB_USER"); $$password = getenv("DB_PASSWORD"); $$connection = @pg_connect("host=$$host port=$$port dbname=$$dbname user=$$user password=$$password connect_timeout=1"); if (!$$connection) { exit(1); } pg_close($$connection);'\'' >/dev/null 2>&1; do sleep 2; done'
	$(COMPOSE) exec -T $(PHP_SERVICE) php bin/console gisclient:dbupgrade

test:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run test

test-ci:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run test-ci

phpstan:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run phpstan

phpstan-ci:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run phpstan-ci

ecs:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run ecs

ecs-ci:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run ecs-ci

rector:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run rector

rector-ci:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run rector-ci

quality:
	$(MAKE) rector ecs phpstan

quality-ci:
	$(MAKE) rector-ci ecs-ci phpstan-ci

ecs-fix:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run ecs-fix

rector-fix:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run rector-fix

quality-fix:
	$(MAKE) rector-fix ecs-fix

cache-clear:
	$(COMPOSE) exec -T $(PHP_SERVICE) sh -lc 'rm -f /app/author/var/container.php'
	$(COMPOSE) restart $(PHP_SERVICE)

version-file:
	scripts/release-metadata.sh write-version-file

build-backend: version-file
	$(DOCKER) buildx build --load --platform $(PLATFORM) --target prod \
		--build-arg OCI_VERSION=$(VERSIONED_TAG) \
		--build-arg OCI_REVISION=$(GIT_SHA) \
		--build-arg OCI_SOURCE=$(OCI_SOURCE) \
		-t $(BACKEND_IMAGE):latest \
		-t $(BACKEND_IMAGE):$(VERSIONED_TAG) \
		-f docker/backend/Dockerfile .

build-frontend: version-file
	$(DOCKER) buildx build --load --platform $(PLATFORM) --target prod \
		--build-arg OCI_VERSION=$(VERSIONED_TAG) \
		--build-arg OCI_REVISION=$(GIT_SHA) \
		--build-arg OCI_SOURCE=$(OCI_SOURCE) \
		-t $(FRONTEND_IMAGE):latest \
		-t $(FRONTEND_IMAGE):$(VERSIONED_TAG) \
		-f docker/frontend/Dockerfile .
