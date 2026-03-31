<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class SymbolImportService
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
     * Imports symbols from an export document produced by SymbolExportService.
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    public function import(array $document): array
    {
        $this->validateDocument($document);

        $symbolsUpserted = 0;
        $pixmapFilesWritten = 0;

        $this->db->beginTransaction();

        try {
            foreach ($document['symbols'] as $symbol) {
                $this->upsertSymbol($symbol);
                $symbolsUpserted++;
            }

            foreach ($document['pixmap_files'] ?? [] as $filename => $base64Data) {
                $this->writePixmapFile((string) $filename, (string) $base64Data);
                $pixmapFilesWritten++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'symbols_imported' => $symbolsUpserted,
            'pixmap_files_written' => $pixmapFilesWritten,
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

        if (!isset($document['symbols']) || !is_array($document['symbols'])) {
            throw new \InvalidArgumentException("Invalid document: 'symbols' array is required.");
        }
    }

    /**
     * @param array<string,mixed> $symbol
     */
    private function upsertSymbol(array $symbol): void
    {
        $symbolImage = isset($symbol['symbol_image']) && $symbol['symbol_image'] !== null
            ? base64_decode((string) $symbol['symbol_image'], true)
            : null;

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.symbol
                (symbol_name, symbolcategory_id, icontype, symbol_def, symbol_type,
                 font_name, ascii_code, filled, points, image, symbol_image)
             VALUES
                (:symbol_name, :symbolcategory_id, :icontype, :symbol_def, :symbol_type,
                 :font_name, :ascii_code, :filled, :points, :image, :symbol_image)
             ON CONFLICT (symbol_name) DO UPDATE SET
                symbolcategory_id = EXCLUDED.symbolcategory_id,
                icontype          = EXCLUDED.icontype,
                symbol_def        = EXCLUDED.symbol_def,
                symbol_type       = EXCLUDED.symbol_type,
                font_name         = EXCLUDED.font_name,
                ascii_code        = EXCLUDED.ascii_code,
                filled            = EXCLUDED.filled,
                points            = EXCLUDED.points,
                image             = EXCLUDED.image,
                symbol_image      = EXCLUDED.symbol_image'
        );

        $stmt->bindValue(':symbol_name', $symbol['symbol_name']);
        $stmt->bindValue(':symbolcategory_id', $symbol['symbolcategory_id'] ?? null, \PDO::PARAM_INT);
        $stmt->bindValue(':icontype', $symbol['icontype'] ?? null, \PDO::PARAM_INT);
        $stmt->bindValue(':symbol_def', $symbol['symbol_def'] ?? null);
        $stmt->bindValue(':symbol_type', $symbol['symbol_type'] ?? null);
        $stmt->bindValue(':font_name', $symbol['font_name'] ?? null);
        $stmt->bindValue(':ascii_code', $symbol['ascii_code'] ?? null, \PDO::PARAM_INT);
        $stmt->bindValue(':filled', $symbol['filled'] ?? null);
        $stmt->bindValue(':points', $symbol['points'] ?? null);
        $stmt->bindValue(':image', $symbol['image'] ?? null);

        if ($symbolImage !== false && $symbolImage !== null) {
            $stmt->bindValue(':symbol_image', $symbolImage, \PDO::PARAM_LOB);
        } else {
            $stmt->bindValue(':symbol_image', null, \PDO::PARAM_NULL);
        }

        $stmt->execute();
    }

    private function writePixmapFile(string $filename, string $base64Data): void
    {
        $basename = basename($filename);
        $data = base64_decode($base64Data, true);

        if ($data === false) {
            throw new \InvalidArgumentException("Invalid base64 data for pixmap file '$basename'.");
        }

        $pixmapDir = $this->rootPath . 'pixmap/';

        if (!is_dir($pixmapDir)) {
            mkdir($pixmapDir, 0755, true);
        }

        file_put_contents($pixmapDir . $basename, $data);
    }
}
