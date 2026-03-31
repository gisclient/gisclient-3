DO $$
BEGIN
    ALTER TABLE gisclient_34.font ADD COLUMN IF NOT EXISTS font_data bytea;
END $$;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.5', 'author', '2026-03-30');
