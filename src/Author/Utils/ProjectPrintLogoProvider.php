<?php

namespace GisClient\Author\Utils;

/**
 * Legge i loghi di stampa del progetto (tabella project_print_logo).
 */
class ProjectPrintLogoProvider
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var string
     */
    private $dbSchema;

    public function __construct(\PDO $db, string $dbSchema = DB_SCHEMA)
    {
        $this->db = $db;
        $this->dbSchema = $dbSchema;
    }

    /**
     * @return array<string,string> data URI per posizione ('sx', 'dx')
     */
    public function getDataUris(string $projectName): array
    {
        $stmt = $this->db->prepare(sprintf(
            'SELECT logo_position, logo_data FROM %s.project_print_logo WHERE project_name = :project_name',
            $this->dbSchema
        ));
        $stmt->execute([
            ':project_name' => $projectName,
        ]);

        $dataUris = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $image = Base64Image::fromBase64((string) $row['logo_data']);
            $dataUri = $image !== null ? $image->toDataUri() : null;
            if ($dataUri !== null) {
                $dataUris[$row['logo_position']] = $dataUri;
            }
        }

        return $dataUris;
    }
}
