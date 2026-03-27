#!/bin/bash

set -e

POSTGRES_USER=${POSTGRES_USER:-postgres}

echo "* Import geo spatial data"
gunzip </sample-data/geo.dmp.gz \
    | psql -q -v ON_ERROR_STOP=1 \
           --username "${DB_USER}" --dbname "${DB_DBNAME}" >/dev/null

echo "* Grant geo schema access to ${MAP_USER}"
psql -q -v ON_ERROR_STOP=1 \
     --username "${POSTGRES_USER}" --dbname "${DB_DBNAME}" <<-EOSQL
    GRANT USAGE ON SCHEMA geo TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.adm_bound_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.edifici_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.idro_line_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.idro_poli_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.place_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.railroad_osm TO ${MAP_USER};
    GRANT SELECT ON TABLE geo.strade_osm TO ${MAP_USER};
EOSQL

echo "* Optimize"
psql -q -v ON_ERROR_STOP=1 \
     --username "${POSTGRES_USER}" --dbname "${DB_DBNAME}" \
     -c "VACUUM ANALYZE;"

echo "* SEED SUCCESSFUL"
