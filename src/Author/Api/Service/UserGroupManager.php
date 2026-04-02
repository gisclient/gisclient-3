<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class UserGroupManager
{
    /**
     * @var \PDO
     */
    private $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?: \GCApp::getDB();
    }

    /**
     * Returns the groupnames the given user belongs to, sorted alphabetically.
     *
     * @return array<int,string>
     */
    public function getGroups(string $username): array
    {
        $stmt = $this->db->prepare(
            'SELECT groupname FROM ' . DB_SCHEMA . '.user_group
             WHERE username = :username
             ORDER BY groupname'
        );
        $stmt->execute([
            ':username' => $username,
        ]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Replaces all group memberships for the given user with the supplied list.
     * Passing an empty array removes all memberships.
     *
     * @param array<int,string> $groupnames
     */
    public function setGroups(string $username, array $groupnames): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM ' . DB_SCHEMA . '.user_group WHERE username = :username'
        );
        $stmt->execute([
            ':username' => $username,
        ]);

        if ($groupnames === []) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.user_group (username, groupname)
             VALUES (:username, :groupname)'
        );
        foreach (array_unique($groupnames) as $groupname) {
            $stmt->execute([
                ':username' => $username,
                ':groupname' => $groupname,
            ]);
        }
    }
}
