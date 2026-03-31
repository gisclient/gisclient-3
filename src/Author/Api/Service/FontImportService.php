<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class FontImportService
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
     * Imports fonts from an export document produced by FontExportService.
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    public function import(array $document): array
    {
        $this->validateDocument($document);

        $fontsImported = 0;

        $this->db->beginTransaction();

        try {
            foreach ($document['fonts'] as $font) {
                $this->importFont($font);
                $fontsImported++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'fonts_imported' => $fontsImported,
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

        if (!isset($document['fonts']) || !is_array($document['fonts'])) {
            throw new \InvalidArgumentException("Invalid document: 'fonts' array is required.");
        }
    }

    /**
     * @param array<string,mixed> $font
     */
    private function importFont(array $font): void
    {
        $fontName = (string) $font['font_name'];
        $fileName = (string) $font['file_name'];

        $fontBinary = null;

        if (isset($font['font_data']) && $font['font_data'] !== null) {
            $decoded = base64_decode((string) $font['font_data'], true);

            if ($decoded === false) {
                throw new \InvalidArgumentException("Invalid base64 font_data for font '$fontName'.");
            }

            $fontBinary = $decoded;

            $fontsDir = $this->rootPath . 'fonts/';

            if (!is_dir($fontsDir)) {
                mkdir($fontsDir, 0755, true);
            }

            file_put_contents($fontsDir . $fileName, $fontBinary);
            $this->updateFontsList($fontName, $fileName);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.font (font_name, file_name, font_data)
             VALUES (:font_name, :file_name, :font_data)
             ON CONFLICT (font_name) DO UPDATE SET
                file_name = EXCLUDED.file_name,
                font_data = EXCLUDED.font_data'
        );

        $stmt->bindValue(':font_name', $fontName);
        $stmt->bindValue(':file_name', $fileName);

        if ($fontBinary !== null) {
            $stmt->bindValue(':font_data', $fontBinary, \PDO::PARAM_LOB);
        } else {
            $stmt->bindValue(':font_data', null, \PDO::PARAM_NULL);
        }

        $stmt->execute();
    }

    private function updateFontsList(string $fontName, string $fileName): void
    {
        $fontsListPath = $this->rootPath . 'fonts/fonts.list';

        $lines = is_file($fontsListPath)
            ? file($fontsListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        $newEntry = $fontName . ' ' . $fileName;
        $found = false;

        foreach ($lines as &$line) {
            if (strncmp($line, $fontName . ' ', strlen($fontName) + 1) === 0) {
                $line = $newEntry;
                $found = true;
                break;
            }
        }
        unset($line);

        if (!$found) {
            $lines[] = $newEntry;
        }

        sort($lines);
        file_put_contents($fontsListPath, implode("\n", $lines) . "\n");
    }
}
