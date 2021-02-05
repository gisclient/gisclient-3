CREATE OR REPLACE VIEW gisclient_3.seldb_qt_relationtype AS
 SELECT e_qt_relationtype.qtrelationtype_id AS id,
    e_qt_relationtype.qtrelationtype_name AS opzione
   FROM gisclient_3.e_qt_relationtype;

ALTER TABLE gisclient_3.seldb_qt_relationtype
  OWNER TO "geowebAdmin";
