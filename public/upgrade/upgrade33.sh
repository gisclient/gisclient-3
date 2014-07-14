pg_dump -f gc21.sql -n gisclient_21 mydb
cat gc21.sql | sed 's/gisclient_21/gisclient_33/' > gc33.sql
psql -f gc33.sql mydb
psql -f migrate.sql mydb
#esego migrate.php??
#psql -f aggiornamento_database_mapserver_6.sql mydb

