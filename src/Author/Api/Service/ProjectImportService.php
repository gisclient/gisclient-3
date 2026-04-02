<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\ProjectCopy\ProjectCopyContext;

class ProjectImportService
{
    /**
     * @var ProjectTransferService
     */
    private $transferService;

    public function __construct(ProjectTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Imports a project from an export document (produced by ProjectExportService).
     *
     * @param array<string,mixed> $document
     */
    public function importProject(
        ApiCrudGatewayInterface $gateway,
        array $document,
        string $targetProjectName
    ): void {
        $this->validateDocument($document);

        $sourceProjectName = $document['project_name'];
        $resources = $document['resources'];

        if ($gateway->findResource('project', $targetProjectName) !== null) {
            throw new ApiException(409, 'target_project_exists', 'Conflict', "Project '$targetProjectName' already exists", '/target_project');
        }

        $context = new ProjectCopyContext($sourceProjectName, $targetProjectName);
        $graph = $this->buildGraph($resources, $sourceProjectName, $context);

        $gateway->runAtomically(function () use ($gateway, $graph, $context): void {
            $this->transferService->createProjectGraph($gateway, $graph, $context);
        });
    }

    /**
     * @param array<string,mixed> $document
     */
    private function validateDocument(array $document): void
    {
        if (!isset($document['format_version'], $document['project_name'], $document['resources'])) {
            throw new \InvalidArgumentException('Invalid import document: missing required keys (format_version, project_name, resources)');
        }

        if (!is_array($document['resources'])) {
            throw new \InvalidArgumentException('Invalid import document: resources must be an object');
        }
    }

    /**
     * Builds the graph array from the raw document resources, hydrating DTOs and pre-registering mapset mappings.
     *
     * @param array<string,mixed> $resources
     * @return array<string,mixed>
     */
    private function buildGraph(array $resources, string $sourceProjectName, ProjectCopyContext $context): array
    {
        $standardTypes = ['project_srs', 'catalog', 'link', 'theme', 'layergroup', 'mapset', 'layer', 'relation', 'class', 'style', 'field', 'mapset_layergroup'];

        // Project (single)
        $projectResources = $resources['project'] ?? [];
        if (empty($projectResources)) {
            throw new \InvalidArgumentException('Import document must contain at least one project resource');
        }
        $project = $this->hydrateStandardDto('project', $projectResources[0]);

        // Pre-register mapset name mappings before creating the graph
        // (mapset IDs are string-keyed and need explicit context mapping)
        foreach ($resources['mapset'] ?? [] as $mapsetObj) {
            $sourceMapsetId = (string) ($mapsetObj['id'] ?? '');
            $targetMapsetId = str_replace($sourceProjectName, $context->getTargetProject(), $sourceMapsetId);
            $context->rememberMapping('mapset', $sourceMapsetId, $targetMapsetId);
        }

        $graph = [
            'project' => $project,
        ];

        foreach ($standardTypes as $type) {
            $graph[$type] = [];
            foreach ($resources[$type] ?? [] as $resourceObj) {
                $graph[$type][] = $this->hydrateStandardDto($type, $resourceObj);
            }
        }

        // Project languages
        $graph['project_languages'] = [];
        foreach ($resources['project_languages'] ?? [] as $item) {
            $dto = new ProjectLanguageDto();
            $dto->languageId = (string) ($item['language_id'] ?? '');
            $dto->markPresent('language_id');
            $dto->project = $this->projectIdentifier($sourceProjectName);
            $dto->markPresent('project');
            $graph['project_languages'][] = $dto;
        }

        // Selgroups
        $graph['selgroup'] = [];
        foreach ($resources['selgroup'] ?? [] as $item) {
            $dto = new SelgroupDto();
            $dto->id = isset($item['id']) ? (int) $item['id'] : null;
            $dto->selgroupName = (string) ($item['selgroup_name'] ?? '');
            $dto->selgroupTitle = isset($item['selgroup_title']) ? (string) $item['selgroup_title'] : null;
            $dto->selgroupOrder = isset($item['selgroup_order']) ? (int) $item['selgroup_order'] : null;
            // project is set by ProjectTransferService::cloneSelgroups using context->getTargetProject()
            $dto->project = $this->projectIdentifier($sourceProjectName);
            $graph['selgroup'][] = $dto;
        }

        // Selgroup layers
        $graph['selgroup_layer'] = [];
        foreach ($resources['selgroup_layer'] ?? [] as $item) {
            $dto = new SelgroupLayerDto();
            $selgroupDto = new SelgroupDto();
            $selgroupDto->id = (int) ($item['selgroup_id'] ?? 0);
            $dto->selgroup = $selgroupDto;
            $layerDto = new LayerDto();
            $layerDto->id = (int) ($item['layer_id'] ?? 0);
            $dto->layer = $layerDto;
            $graph['selgroup_layer'][] = $dto;
        }

        // Project admins
        $graph['project_admin'] = [];
        foreach ($resources['project_admin'] ?? [] as $item) {
            $dto = new ProjectAdminDto();
            $dto->username = (string) ($item['username'] ?? '');
            $dto->markPresent('username');
            $dto->project = $this->projectIdentifier($sourceProjectName);
            $dto->markPresent('project');
            $graph['project_admin'][] = $dto;
        }

        return $graph;
    }

    /**
     * Builds a DTO for a standard (schema-registered) resource type from its JSON:API resource object.
     * Relationship IDs are stored as-is from the JSON; ProjectTransferService::buildCrudCloneDto resolves
     * them through the ProjectCopyContext idMap before writing to the DB.
     *
     * @param array<string,mixed> $resourceObj
     */
    private function hydrateStandardDto(string $type, array $resourceObj): JsonApiDto
    {
        $schema = DtoSchemaRegistry::schemaForType($type);
        $dtoClass = $schema->getDtoClass();

        /** @var JsonApiDto $dto */
        $dto = new $dtoClass();

        // Set the source id (used by context idMap after creation)
        if (isset($resourceObj['id']) && $resourceObj['id'] !== null) {
            $rawId = $resourceObj['id'];
            $id = $schema->getIdPhpType() === 'int' ? (int) $rawId : (string) $rawId;
            DtoPropertyAccessor::set($dto, 'id', $id);
            $dto->markPresent('id');
        }

        // Attributes
        foreach ($schema->getAttributes() as $fieldName => $fieldDef) {
            if (!$fieldDef->isWritable()) {
                continue;
            }
            $attributes = $resourceObj['attributes'] ?? [];
            if (!array_key_exists($fieldName, $attributes)) {
                continue;
            }
            $value = $attributes[$fieldName];
            if ($value !== null) {
                $value = $this->coerceToPhpType($value, $fieldDef->getPhpType());
            }
            DtoPropertyAccessor::set($dto, $fieldDef->getPropertyName(), $value);
            $dto->markPresent($fieldName);
        }

        // Relationships — store with source IDs (resolved later by buildCrudCloneDto via idMap)
        foreach ($schema->getRelationships() as $fieldName => $fieldDef) {
            if (!$fieldDef->isWritable()) {
                continue;
            }
            $relData = $resourceObj['relationships'][$fieldName]['data'] ?? null;
            if ($relData === null) {
                DtoPropertyAccessor::set($dto, $fieldDef->getPropertyName(), null);
                $dto->markPresent($fieldName);
                continue;
            }
            if (!is_array($relData) || !isset($relData['id'])) {
                continue;
            }

            $relType = (string) ($relData['type'] ?? $fieldDef->getTargetType());
            $relSchema = DtoSchemaRegistry::schemaForType($relType);
            $relDtoClass = $relSchema->getDtoClass();

            /** @var JsonApiDto $relDto */
            $relDto = new $relDtoClass();
            $rawRelId = $relData['id'];
            $relId = $relSchema->getIdPhpType() === 'int' ? (int) $rawRelId : (string) $rawRelId;
            DtoPropertyAccessor::set($relDto, 'id', $relId);
            $relDto->markPresent('id');
            $relDto->markAsIdentifierOnly();

            DtoPropertyAccessor::set($dto, $fieldDef->getPropertyName(), $relDto);
            $dto->markPresent($fieldName);
        }

        return $dto;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function coerceToPhpType($value, string $phpType)
    {
        if ($phpType === 'int') {
            return (int) $value;
        }
        if ($phpType === 'float') {
            return (float) $value;
        }
        if ($phpType === 'string') {
            return (string) $value;
        }

        return $value;
    }

    private function projectIdentifier(string $id): ProjectDto
    {
        $dto = new ProjectDto();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }
}
