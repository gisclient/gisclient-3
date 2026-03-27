#!/bin/bash

set -e

echo "* Initialize ${DB_DBNAME} database"
psql -q -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${POSTGRES_DB}" <<-EOSQL
    -- CREATE USER ${DB_USER} SUPERUSER PASSWORD '${DB_PASSWORD}';
    CREATE USER ${DB_USER} PASSWORD '${DB_PASSWORD}';   --  WITH GRANT OPTION;
    CREATE USER ${MAP_USER} PASSWORD '${MAP_PASSWORD}';
    CREATE DATABASE ${DB_DBNAME} OWNER ${DB_USER};
EOSQL

echo "* Create postgis extension on database ${DB_DBNAME}"
psql -q -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${DB_DBNAME}" <<-EOSQL
    CREATE EXTENSION postgis;
EOSQL

echo "* INIT SUCCESSFUL"
