<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class UserImportService
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
     * Imports users from an export document produced by UserExportService.
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    public function import(array $document): array
    {
        $this->validateDocument($document);

        $userStmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.users
                (username, nome, cognome, email, attivato, data_creazione,
                 data_scadenza, data_modifica, ultimo_accesso, userdata, enc_pwd)
             VALUES
                (:username, :nome, :cognome, :email, :attivato, :data_creazione,
                 :data_scadenza, :data_modifica, :ultimo_accesso, :userdata, :enc_pwd)
             ON CONFLICT (username) DO UPDATE SET
                nome            = EXCLUDED.nome,
                cognome         = EXCLUDED.cognome,
                email           = EXCLUDED.email,
                attivato        = EXCLUDED.attivato,
                data_creazione  = EXCLUDED.data_creazione,
                data_scadenza   = EXCLUDED.data_scadenza,
                data_modifica   = EXCLUDED.data_modifica,
                ultimo_accesso  = EXCLUDED.ultimo_accesso,
                userdata        = EXCLUDED.userdata,
                enc_pwd         = EXCLUDED.enc_pwd'
        );

        $deleteGroupsStmt = $this->db->prepare(
            'DELETE FROM ' . DB_SCHEMA . '.user_group WHERE username = :username'
        );

        $insertGroupStmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.user_group (username, groupname) VALUES (:username, :groupname)'
        );

        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($document['users'] as $user) {
                $username = (string) ($user['username'] ?? '');
                $userStmt->execute([
                    ':username' => $username,
                    ':nome' => $user['nome'] ?? null,
                    ':cognome' => $user['cognome'] ?? null,
                    ':email' => $user['email'] ?? null,
                    ':attivato' => isset($user['attivato']) ? (int) $user['attivato'] : 1,
                    ':data_creazione' => $user['data_creazione'] ?? null,
                    ':data_scadenza' => $user['data_scadenza'] ?? null,
                    ':data_modifica' => $user['data_modifica'] ?? null,
                    ':ultimo_accesso' => $user['ultimo_accesso'] ?? null,
                    ':userdata' => $user['userdata'] ?? null,
                    ':enc_pwd' => $user['enc_pwd'] ?? null,
                ]);

                $deleteGroupsStmt->execute([
                    ':username' => $username,
                ]);
                foreach ((array) ($user['groups'] ?? []) as $groupname) {
                    $insertGroupStmt->execute([
                        ':username' => $username,
                        ':groupname' => (string) $groupname,
                    ]);
                }

                $count++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'users_imported' => $count,
        ];
    }

    /**
     * @param array<string,mixed> $document
     */
    private function validateDocument(array $document): void
    {
        if (($document['format_version'] ?? null) !== '1.0') {
            throw new \InvalidArgumentException("Unsupported format_version. Expected '1.0'.");
        }

        if (!isset($document['users']) || !is_array($document['users'])) {
            throw new \InvalidArgumentException("Invalid document: 'users' array is required.");
        }
    }
}
