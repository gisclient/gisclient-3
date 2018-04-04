#!/bin/bash

#******************************************
# **** CONSTANTS
#******************************************
PG_HOSTNAME=localhost
PG_PORT=5432
PG_USERNAME=postgres

#******************************************

[ -z "$1" -o -z "$2" -o -z "$3" ] && { echo "Usage: gisclient_dump.sh <gisclient database> <gisclient schema> <dump filename>"; exit 1; }

[ -z "$(which pg_dump)" ] && { echo "pg_dump non trovato, impossibile proseguire"; exit 2; }

FILE_PATH=$3
FILE_PATH=${FILE_PATH%/*}
[ -n "$FILE_PATH" -a "$FILE_PATH" != "$3" ] && { if ! mkdir -p $FILE_PATH; then echo "Impossibile salvare il dump in $3"; exit 3; fi; }

RES_VALUE=0
pg_dump -h $PG_HOSTNAME -p $PG_PORT -U $PG_USERNAME -n $2 -F p -s -v $1 > $3
RES_VALUE=$(($RES_VALUE+$?))
pg_dump -h $PG_HOSTNAME -p $PG_PORT -U $PG_USERNAME -F p -a -v -t "$2".'e_*' -t "$2".form_level $1 | sed -e 's/^SET.*//g' >> $3
RES_VALUE=$(($RES_VALUE+$?))

# **** Display result and exit
if [ $RES_VALUE -eq 0 ]
then
  echo "Dump dello schema gisclient generato correttamente"
  exit 0
else
  echo "La generazione dello schema gisclient è fallita"
  exit 4
fi
