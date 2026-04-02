<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class UserExportService
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
     * Exports all users (with their group memberships) as a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public function export(): array
    {
        $stmt = $this->db->query(
            'SELECT u.username, u.nome, u.cognome, u.email, u.attivato,
                    u.data_creazione, u.data_scadenza, u.data_modifica, u.ultimo_accesso,
                    u.userdata, u.enc_pwd
             FROM ' . DB_SCHEMA . '.users u
             ORDER BY u.username'
        );

        $groupStmt = $this->db->prepare(
            'SELECT groupname FROM ' . DB_SCHEMA . '.user_group WHERE username = :username ORDER BY groupname'
        );

        $users = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $groupStmt->execute([
                ':username' => $row['username'],
            ]);
            $row['groups'] = $groupStmt->fetchAll(\PDO::FETCH_COLUMN);
            $users[] = $row;
        }

        return [
            'format_version' => '1.0',
            'users' => $users,
        ];
    }
}
