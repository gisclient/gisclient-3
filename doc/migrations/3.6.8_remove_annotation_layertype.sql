-- Remove deprecated 'annotation' layer type (layertype_id=5).
-- The ANNOTATION layer type was removed in MapServer 6.2; we run MapServer 7.6.
-- Any existing layers using this type must be manually reassigned before running
-- this migration (the FK constraint on layer.layertype_id will abort otherwise).
DELETE FROM gisclient_34.e_layertype WHERE layertype_id = 5;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.8', 'author', '2026-05-04');
