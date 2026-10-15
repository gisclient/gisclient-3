-- API Token per l'accesso alla JSON:API senza sessione (Authorization: Bearer gca_<id>_<secret>).
-- Si salva solo l'hash SHA-256 (hex) del token completo, mai il token in chiaro.
-- La revoca cancella la riga.
CREATE TABLE IF NOT EXISTS gisclient_34.api_token (
    id character varying(16) PRIMARY KEY
        CHECK (id ~ '^[0-9a-f]{16}$'),
    username character varying NOT NULL
        REFERENCES gisclient_34.users(username)
        ON UPDATE CASCADE ON DELETE CASCADE,
    name character varying NOT NULL,
    token_hash character(64) NOT NULL
        CHECK (token_hash ~ '^[0-9a-f]{64}$'),
    created_at timestamp without time zone NOT NULL DEFAULT now(),
    CONSTRAINT api_token_username_name_key UNIQUE (username, name)
);

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.13', 'author', '2026-10-15');
