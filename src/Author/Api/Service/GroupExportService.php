<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class GroupExportService
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
     * Exports all groups as a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public function export(): array
    {
        $stmt = $this->db->query(
            'SELECT groupname, description FROM ' . DB_SCHEMA . '.groups ORDER BY groupname'
        );

        return [
            'format_version' => '1.0',
            'groups' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
        ];
    }
}
