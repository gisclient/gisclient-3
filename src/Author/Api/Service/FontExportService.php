<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class FontExportService
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var string
     */
    private $rootPath;

    public function __construct(?\PDO $db = null, string $rootPath = '')
    {
        $this->db = $db ?: \GCApp::getDB();
        $this->rootPath = $rootPath !== '' ? $rootPath : ROOT_PATH;
    }

    /**
     * Exports all fonts including their binary TTF data as a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public function export(): array
    {
        $stmt = $this->db->query(
            'SELECT font_name, file_name, font_data
             FROM ' . DB_SCHEMA . '.font
             ORDER BY font_name'
        );

        $fonts = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $fontData = $row['font_data'];

            if ($fontData === null) {
                $filePath = $this->rootPath . 'fonts/' . $row['file_name'];
                if (is_file($filePath)) {
                    $fontData = file_get_contents($filePath);
                }
            }

            $fonts[] = [
                'font_name' => $row['font_name'],
                'file_name' => $row['file_name'],
                'font_data' => $fontData !== null && $fontData !== false
                    ? base64_encode((string) $fontData)
                    : null,
            ];
        }

        return [
            'format_version' => '1.0',
            'fonts' => $fonts,
        ];
    }
}
