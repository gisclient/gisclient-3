DO $$
BEGIN
    UPDATE gisclient_34.layer SET data_type = 'linestring' WHERE data_type = 'line';
END $$;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.10', 'author', '2026-06-08');
