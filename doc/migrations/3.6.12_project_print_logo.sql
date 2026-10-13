-- Loghi di stampa per progetto: sostituiscono GC_PRINT_LOGO_SX/DX quando presenti.
-- logo_data contiene l'immagine in base64 (PNG o JPEG), cosi' la stampa la
-- inserisce come data URI senza scaricarla da un URL.
CREATE TABLE IF NOT EXISTS gisclient_34.project_print_logo (
    project_print_logo_id serial PRIMARY KEY,
    project_name character varying NOT NULL
        REFERENCES gisclient_34.project(project_name)
        ON UPDATE CASCADE ON DELETE CASCADE,
    logo_position character varying NOT NULL
        CHECK (logo_position IN ('sx', 'dx')),
    logo_data text NOT NULL,
    CONSTRAINT project_print_logo_project_position_key UNIQUE (project_name, logo_position)
);

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.12', 'author', '2026-10-12');
