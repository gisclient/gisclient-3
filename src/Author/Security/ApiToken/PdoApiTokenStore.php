<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * API Token store over the `api_token` table
 */
class PdoApiTokenStore implements ApiTokenStoreInterface
{
    /**
     * @var \PDO
     */
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function register(string $id, string $username, string $name, string $tokenHash): void
    {
        if (!ApiTokenFormat::isValidId($id)) {
            throw ApiTokenRegistrationException::malformedId($id);
        }
        if (!ApiTokenFormat::isValidHash($tokenHash)) {
            throw ApiTokenRegistrationException::malformedHash();
        }
        if (!$this->userExists($username)) {
            throw ApiTokenRegistrationException::unknownUser($username);
        }
        if (null !== $this->find($id)) {
            throw ApiTokenRegistrationException::duplicateId($id);
        }
        if ($this->nameExists($username, $name)) {
            throw ApiTokenRegistrationException::duplicateName($username, $name);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.api_token (id, username, name, token_hash) VALUES (:id, :username, :name, :token_hash)'
        );
        $stmt->execute([
            'id' => $id,
            'username' => $username,
            'name' => $name,
            'token_hash' => $tokenHash,
        ]);
    }

    public function find(string $id): ?ApiToken
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, name, token_hash, created_at FROM ' . DB_SCHEMA . '.api_token WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new ApiToken(
            (string) $row['id'],
            (string) $row['username'],
            (string) $row['name'],
            (string) $row['token_hash'],
            (string) $row['created_at']
        );
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ' . DB_SCHEMA . '.api_token WHERE id = :id');
        $stmt->execute([
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function userExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM ' . DB_SCHEMA . '.users WHERE username = :username');
        $stmt->execute([
            'username' => $username,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function nameExists(string $username, string $name): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM ' . DB_SCHEMA . '.api_token WHERE username = :username AND name = :name'
        );
        $stmt->execute([
            'username' => $username,
            'name' => $name,
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
