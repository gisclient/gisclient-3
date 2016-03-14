CREATE SCHEMA geo;
SET search_path = geo, public;

CREATE TABLE developers (
  id serial,
  name varchar
);

ALTER TABLE developers
    ADD CONSTRAINT developers_pkey PRIMARY KEY (id);

SELECT AddGeometryColumn('geo', 'developers', 'geom', 4326, 'POINT', 2);

INSERT INTO developers (name, geom) VALUES ('R3 GIS', ST_SetSRID(ST_GeomFromText('POINT(46.633 11.162)'), 4326));