<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

class DataExportService
{
    /**
     * @var SymbolExportService
     */
    private $symbolExportService;

    /**
     * @var FontExportService
     */
    private $fontExportService;

    /**
     * @var GroupExportService
     */
    private $groupExportService;

    /**
     * @var UserExportService
     */
    private $userExportService;

    public function __construct(
        ?SymbolExportService $symbolExportService = null,
        ?FontExportService $fontExportService = null,
        ?GroupExportService $groupExportService = null,
        ?UserExportService $userExportService = null
    ) {
        $this->symbolExportService = $symbolExportService ?: new SymbolExportService();
        $this->fontExportService = $fontExportService ?: new FontExportService();
        $this->groupExportService = $groupExportService ?: new GroupExportService();
        $this->userExportService = $userExportService ?: new UserExportService();
    }

    /**
     * Exports non-project entities to a JSON-serialisable document.
     *
     * @param array<int,string> $only  If non-empty, only export the listed sections
     *                                 ('symbols', 'fonts', 'users', 'groups').
     *                                 Empty means export everything.
     * @return array<string,mixed>
     */
    public function export(array $only = []): array
    {
        $all = $only === [];
        $sections = array_flip($only);

        $document = [
            'format_version' => '2.0',
        ];

        if ($all || isset($sections['symbols'])) {
            $symbolDoc = $this->symbolExportService->export();
            $document['symbols'] = $symbolDoc['symbols'] ?? [];
            $document['pixmap_files'] = $symbolDoc['pixmap_files'] ?? [];
        }

        if ($all || isset($sections['fonts'])) {
            $fontDoc = $this->fontExportService->export();
            $document['fonts'] = $fontDoc['fonts'] ?? [];
        }

        if ($all || isset($sections['groups'])) {
            $groupDoc = $this->groupExportService->export();
            $document['groups'] = $groupDoc['groups'] ?? [];
        }

        if ($all || isset($sections['users'])) {
            $userDoc = $this->userExportService->export();
            $document['users'] = $userDoc['users'] ?? [];
        }

        return $document;
    }
}
