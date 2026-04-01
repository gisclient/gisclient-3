DO $$
BEGIN
    -- Drop old check constraint, default, and NOT NULL first
    ALTER TABLE gisclient_34.field DROP CONSTRAINT IF EXISTS field_relation_id_check;
    ALTER TABLE gisclient_34.field ALTER COLUMN relation_id DROP DEFAULT;
    ALTER TABLE gisclient_34.field ALTER COLUMN relation_id DROP NOT NULL;

    -- Now update legacy sentinel value 0 to NULL (column is now nullable)
    UPDATE gisclient_34.field SET relation_id = NULL WHERE relation_id = 0;

    -- Add proper FK to relation table (SET NULL on delete to avoid orphan rows)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'fk_field__relation_id'
          AND table_schema = 'gisclient_34'
          AND table_name = 'field'
    ) THEN
        ALTER TABLE gisclient_34.field
            ADD CONSTRAINT fk_field__relation_id
                FOREIGN KEY (relation_id)
                REFERENCES gisclient_34.relation(relation_id)
                ON UPDATE CASCADE ON DELETE SET NULL;
    END IF;
END $$;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.6', 'author', '2026-04-01');
