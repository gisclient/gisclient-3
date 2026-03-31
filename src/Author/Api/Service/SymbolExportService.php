<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class SymbolExportService
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
     * Exports all symbols and any pixmap image files as a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public function export(): array
    {
        $stmt = $this->db->query(
            'SELECT symbol_name, symbolcategory_id, icontype, symbol_def, symbol_type,
                    font_name, ascii_code, filled, points, image, symbol_image
             FROM ' . DB_SCHEMA . '.symbol
             ORDER BY symbol_name'
        );

        $symbols = [];
        $pixmapFiles = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $entry = [
                'symbol_name' => $row['symbol_name'],
                'symbolcategory_id' => $row['symbolcategory_id'] !== null ? (int) $row['symbolcategory_id'] : null,
                'icontype' => $row['icontype'] !== null ? (int) $row['icontype'] : null,
                'symbol_def' => $row['symbol_def'],
                'symbol_type' => $row['symbol_type'],
                'font_name' => $row['font_name'],
                'ascii_code' => $row['ascii_code'] !== null ? (int) $row['ascii_code'] : null,
                'filled' => $row['filled'] !== null ? (int) $row['filled'] : null,
                'points' => $row['points'],
                'image' => $row['image'],
                'symbol_image' => $row['symbol_image'] !== null ? base64_encode($row['symbol_image']) : null,
            ];

            $symbols[] = $entry;

            if ($row['image'] !== null) {
                $basename = basename($row['image']);
                if (!isset($pixmapFiles[$basename])) {
                    $filePath = $this->rootPath . 'pixmap/' . $basename;
                    if (is_file($filePath)) {
                        $pixmapFiles[$basename] = base64_encode((string) file_get_contents($filePath));
                    }
                }
            }
        }

        return [
            'format_version' => '1.0',
            'symbols' => $symbols,
            'pixmap_files' => $pixmapFiles,
        ];
    }
}
