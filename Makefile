COMPOSE ?= docker compose
PHP_SERVICE ?= author-be

.PHONY: start up down deps test cache-clear

start:
	$(COMPOSE) up -d --build

up:
	$(COMPOSE) up --build

down:
	$(COMPOSE) down

deps:
	$(COMPOSE) exec -T -u 0 $(PHP_SERVICE) sh -lc 'COMPOSER_ALLOW_SUPERUSER=1 composer install'

test:
	$(COMPOSE) exec -T $(PHP_SERVICE) composer run test

cache-clear:
	$(COMPOSE) exec -T $(PHP_SERVICE) sh -lc 'rm -f /app/author/var/container.php'
	$(COMPOSE) restart $(PHP_SERVICE)
