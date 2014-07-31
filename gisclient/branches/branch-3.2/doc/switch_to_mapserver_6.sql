-- MAPSERVER 6 --
SET search_path = gisclient_32, pg_catalog;

update style set pattern_id = e_pattern.pattern_id 
from e_pattern where symbol_name=pattern_name;

update style set symbol_name=NULL where pattern_id is not null;

INSERT INTO version (version_name,version_key, version_date) values ('6', 'mapserver', CURRENT_DATE);