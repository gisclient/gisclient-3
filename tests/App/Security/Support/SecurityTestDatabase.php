<?php

include_once __DIR__ . '/../../../../config/config.db.php';

/**
 * In-memory SQLite database with the tables read by the user provider and the API Token store
 */
class SecurityTestDatabase
{
    public static function create(): \PDO
    {
        $db = new \PDO('sqlite::memory:');
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->exec("ATTACH DATABASE ':memory:' AS " . DB_SCHEMA);
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.users (
            username VARCHAR PRIMARY KEY,
            enc_pwd VARCHAR,
            nome VARCHAR,
            cognome VARCHAR
        )');
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.project_admin (username VARCHAR NOT NULL, project_name VARCHAR NOT NULL)');
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.user_group (username VARCHAR NOT NULL, groupname VARCHAR NOT NULL)');
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.api_token (
            id VARCHAR(16) PRIMARY KEY,
            username VARCHAR NOT NULL REFERENCES users (username) ON DELETE CASCADE,
            name VARCHAR NOT NULL,
            token_hash CHAR(64) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (username, name)
        )');

        return $db;
    }

    public static function addUser(\PDO $db, string $username): void
    {
        $stmt = $db->prepare('INSERT INTO ' . DB_SCHEMA . '.users (username, enc_pwd) VALUES (?, ?)');
        $stmt->execute([$username, md5('secret')]);
    }
}
