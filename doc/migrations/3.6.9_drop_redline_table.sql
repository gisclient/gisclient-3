-- Drop the redline annotations table created by the now-removed redline.php service.
-- Default table was public.annotazioni; if REDLINE_SCHEMA/REDLINE_TABLE were customised,
-- drop that table manually.
DROP TABLE IF EXISTS public.annotazioni;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.9', 'author', '2026-05-04');
