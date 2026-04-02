<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class DataImportService
{
    /**
     * @var SymbolImportService
     */
    private $symbolImportService;

    /**
     * @var FontImportService
     */
    private $fontImportService;

    /**
     * @var GroupImportService
     */
    private $groupImportService;

    /**
     * @var UserImportService
     */
    private $userImportService;

    public function __construct(
        ?SymbolImportService $symbolImportService = null,
        ?FontImportService $fontImportService = null,
        ?GroupImportService $groupImportService = null,
        ?UserImportService $userImportService = null
    ) {
        $this->symbolImportService = $symbolImportService ?: new SymbolImportService();
        $this->fontImportService = $fontImportService ?: new FontImportService();
        $this->groupImportService = $groupImportService ?: new GroupImportService();
        $this->userImportService = $userImportService ?: new UserImportService();
    }

    /**
     * Imports a document produced by DataExportService (format_version 2.0) or
     * by the legacy per-entity export commands (format_version 1.0).
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    public function import(array $document): array
    {
        $version = $document['format_version'] ?? null;

        if ($version === '2.0') {
            return $this->importV2($document);
        }

        if ($version === '1.0') {
            return $this->importV1($document);
        }

        throw new \InvalidArgumentException(
            sprintf("Unsupported format_version '%s'. Expected '1.0' or '2.0'.", $version)
        );
    }

    /**
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    private function importV2(array $document): array
    {
        $counts = [
            'symbols_imported' => 0,
            'pixmap_files_written' => 0,
            'fonts_imported' => 0,
            'groups_imported' => 0,
            'users_imported' => 0,
        ];

        if (isset($document['symbols'])) {
            $symbolDoc = [
                'format_version' => '1.0',
                'symbols' => $document['symbols'],
                'pixmap_files' => $document['pixmap_files'] ?? [],
            ];
            $result = $this->symbolImportService->import($symbolDoc);
            $counts['symbols_imported'] = $result['symbols_imported'];
            $counts['pixmap_files_written'] = $result['pixmap_files_written'];
        }

        if (isset($document['fonts'])) {
            $fontDoc = [
                'format_version' => '1.0',
                'fonts' => $document['fonts'],
            ];
            $result = $this->fontImportService->import($fontDoc);
            $counts['fonts_imported'] = $result['fonts_imported'];
        }

        if (isset($document['groups'])) {
            $groupDoc = [
                'format_version' => '1.0',
                'groups' => $document['groups'],
            ];
            $result = $this->groupImportService->import($groupDoc);
            $counts['groups_imported'] = $result['groups_imported'];
        }

        if (isset($document['users'])) {
            $userDoc = [
                'format_version' => '1.0',
                'users' => $document['users'],
            ];
            $result = $this->userImportService->import($userDoc);
            $counts['users_imported'] = $result['users_imported'];
        }

        return array_filter($counts, static fn (int $v) => $v > 0);
    }

    /**
     * Handles legacy 1.0 format files (single-entity exports).
     *
     * @param array<string,mixed> $document
     * @return array<string,int>
     */
    private function importV1(array $document): array
    {
        if (isset($document['symbols'])) {
            return $this->symbolImportService->import($document);
        }

        if (isset($document['fonts'])) {
            return $this->fontImportService->import($document);
        }

        if (isset($document['groups'])) {
            return $this->groupImportService->import($document);
        }

        if (isset($document['users'])) {
            return $this->userImportService->import($document);
        }

        throw new \InvalidArgumentException(
            "Invalid 1.0 document: must contain a 'symbols', 'fonts', 'groups', or 'users' array."
        );
    }
}
