#!/bin/sh
set -e

mkdir -p /app/author/map
chown 48:48 /app/author/map

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

exec "$@"
