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

echo "* Import gisclient data on database ${DB_DBNAME}"
gunzip </docker-entrypoint-initdb.d/gisclient_34.dmp.gz | psql -q -1 -v ON_ERROR_STOP=1 --username "${DB_USER}" --dbname "${DB_DBNAME}" >/dev/null

echo "* Import spatial data data on database ${DB_DBNAME}"
gunzip </docker-entrypoint-initdb.d/geo.dmp.gz | psql -q -v ON_ERROR_STOP=1 --username "${DB_USER}" --dbname "${DB_DBNAME}" >/dev/null

echo "* Setup permissions"
psql -q -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${DB_DBNAME}" <<-EOSQL
    GRANT USAGE ON SCHEMA geo TO mapserver;
    GRANT SELECT ON TABLE geo.adm_bound_osm TO mapserver;
    GRANT SELECT ON TABLE geo.edifici_osm TO mapserver;
    GRANT SELECT ON TABLE geo.idro_line_osm TO mapserver;
    GRANT SELECT ON TABLE geo.idro_poli_osm TO mapserver;
    GRANT SELECT ON TABLE geo.place_osm TO mapserver;
    GRANT SELECT ON TABLE geo.railroad_osm TO mapserver;
    GRANT SELECT ON TABLE geo.strade_osm TO mapserver;
EOSQL

echo "* Optimize data"
psql -q -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${DB_DBNAME}" <<-EOSQL
    VACUUM FULL ANALYZE;
EOSQL

echo "* IMPORT SUCCESSFUL"
