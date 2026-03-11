COMPOSE ?= docker compose
PHP_SERVICE ?= author-be

.PHONY: start up down clean deps db-upgrade test test-ci phpstan phpstan-ci ecs ecs-ci rector rector-ci quality quality-ci ecs-fix rector-fix quality-fix cache-clear

start:
	$(COMPOSE) up -d --build

up:
	$(COMPOSE) up --build

down:
	$(COMPOSE) down

clean:
	$(COMPOSE) down -v --remove-orphans

deps:
	$(COMPOSE) exec -T -u 0 $(PHP_SERVICE) sh -lc 'COMPOSER_ALLOW_SUPERUSER=1 composer install'

db-upgrade:
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
