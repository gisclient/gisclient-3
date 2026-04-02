<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class GroupImportService
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
     * Imports groups from an export document produced by GroupExportService.
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    public function import(array $document): array
    {
        $this->validateDocument($document);

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.groups (groupname, description)
             VALUES (:groupname, :description)
             ON CONFLICT (groupname) DO UPDATE SET
                description = EXCLUDED.description'
        );

        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($document['groups'] as $group) {
                $stmt->execute([
                    ':groupname' => (string) ($group['groupname'] ?? ''),
                    ':description' => $group['description'] ?? null,
                ]);
                $count++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'groups_imported' => $count,
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

        if (!isset($document['groups']) || !is_array($document['groups'])) {
            throw new \InvalidArgumentException("Invalid document: 'groups' array is required.");
        }
    }
}
