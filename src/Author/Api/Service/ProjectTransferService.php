<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\ProjectCopy\ProjectCopyContext;

class ProjectTransferService
{
    /**
     * Creates a full project graph in the database using the provided gateway, reading from $graph.
     * Used by both ProjectCopyService (graph from DB) and ProjectImportService (graph from JSON).
     *
     * @param array<string,mixed> $graph
     */
    public function createProjectGraph(
        ApiCrudGatewayInterface $gateway,
        array $graph,
        ProjectCopyContext $context
    ): void {
        $this->cloneCrudCollection($gateway, 'project', [$graph['project']], $context);
        $this->cloneCrudCollection($gateway, 'project_srs', $graph['project_srs'], $context);
        $this->cloneProjectLanguages($gateway, $graph['project_languages'], $context);
        $this->cloneCrudCollection($gateway, 'catalog', $graph['catalog'], $context);
        $this->cloneCrudCollection($gateway, 'link', $graph['link'], $context);
        $this->cloneCrudCollection($gateway, 'theme', $graph['theme'], $context);
        $this->cloneCrudCollection($gateway, 'layergroup', $graph['layergroup'], $context);
        $this->cloneCrudCollection($gateway, 'mapset', $graph['mapset'], $context);
        $this->cloneCrudCollection($gateway, 'layer', $graph['layer'], $context);
        $this->cloneCrudCollection($gateway, 'class', $graph['class'], $context);
        $this->cloneCrudCollection($gateway, 'style', $graph['style'], $context);
        $this->cloneCrudCollection($gateway, 'field', $graph['field'], $context);
        $this->cloneCrudCollection($gateway, 'mapset_layergroup', $graph['mapset_layergroup'], $context);
        $this->cloneSelgroups($gateway, $graph['selgroup'], $context);
        $this->cloneSelgroupLayers($gateway, $graph['selgroup_layer'], $context);
        $this->cloneProjectAdmins($gateway, $graph['project_admin'], $context);
    }

    /**
     * Loads the full entity graph for a project from the database.
     *
     * @return array<string,mixed>
     */
    public function loadProjectGraph(ApiCrudGatewayInterface $gateway, string $projectName, ProjectDto $project): array
    {
        $themes = $gateway->listResources('theme', [
            'project_name' => $projectName,
        ]);
        $layergroups = $this->listByParentIds($gateway, 'layergroup', 'theme_id', $themes);
        $catalogs = $gateway->listResources('catalog', [
            'project_name' => $projectName,
        ]);
        $mapsets = $gateway->listResources('mapset', [
            'project_name' => $projectName,
        ]);
        $layers = $this->listByParentIds($gateway, 'layer', 'layergroup_id', $layergroups);
        $classes = $this->listByParentIds($gateway, 'class', 'layer_id', $layers);
        $styles = $this->listByParentIds($gateway, 'style', 'class_id', $classes);
        $fields = $this->listByParentIds($gateway, 'field', 'layer_id', $layers);
        $mapsetLayergroups = $this->listMapsetLayergroups($gateway, $mapsets);

        return [
            'project' => $project,
            'project_srs' => $gateway->listResources('project_srs', [
                'project_name' => $projectName,
            ]),
            'project_languages' => $gateway->listProjectLanguages($projectName),
            'catalog' => $catalogs,
            'link' => $gateway->listResources('link', [
                'project_name' => $projectName,
            ]),
            'theme' => $themes,
            'layergroup' => $layergroups,
            'mapset' => $mapsets,
            'layer' => $layers,
            'class' => $classes,
            'style' => $styles,
            'field' => $fields,
            'mapset_layergroup' => $mapsetLayergroups,
            'selgroup' => $gateway->listSelgroups($projectName),
            'selgroup_layer' => $gateway->listSelgroupLayers($projectName),
            'project_admin' => $gateway->listProjectAdmins($projectName),
        ];
    }

    /**
     * @param array<int,JsonApiDto> $items
     */
    private function cloneCrudCollection(ApiCrudGatewayInterface $gateway, string $type, array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $target = $this->buildCrudCloneDto($type, $item, $context);
            $created = $gateway->createResource($type, $target);
            $context->incrementCreated($type);

            if ($item->getId() !== null && $created->getId() !== null) {
                $context->rememberMapping($type, $item->getId(), $created->getId());
            }
        }
    }

    /**
     * @param array<int,ProjectLanguageDto> $items
     */
    private function cloneProjectLanguages(ApiCrudGatewayInterface $gateway, array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $dto = new ProjectLanguageDto();
            $dto->languageId = $item->languageId;
            $dto->markPresent('language_id');
            $dto->project = $this->projectIdentifier($context->getTargetProject());
            $dto->markPresent('project');

            $gateway->createProjectLanguage($dto);
            $context->incrementCreated('project_languages');
        }
    }

    /**
     * @param array<int,SelgroupDto> $items
     */
    private function cloneSelgroups(ApiCrudGatewayInterface $gateway, array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $dto = new SelgroupDto();
            $dto->selgroupName = $item->selgroupName;
            $dto->markPresent('selgroup_name');
            $dto->selgroupTitle = $item->selgroupTitle;
            $dto->markPresent('selgroup_title');
            $dto->selgroupOrder = $item->selgroupOrder;
            $dto->markPresent('selgroup_order');
            $dto->project = $this->projectIdentifier($context->getTargetProject());
            $dto->markPresent('project');

            $created = $gateway->createSelgroup($dto);
            $context->rememberMapping('selgroup', $item->id, $created->id);
            $context->incrementCreated('selgroup');
        }
    }

    /**
     * @param array<int,SelgroupLayerDto> $items
     */
    private function cloneSelgroupLayers(ApiCrudGatewayInterface $gateway, array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $dto = new SelgroupLayerDto();
            $dto->selgroup = $this->selgroupIdentifier((int) $context->mapId('selgroup', $item->selgroup->id));
            $dto->markPresent('selgroup');
            $dto->layer = $this->layerIdentifier((int) $context->mapId('layer', $item->layer->id));
            $dto->markPresent('layer');

            $gateway->createSelgroupLayer($dto);
            $context->incrementCreated('selgroup_layer');
        }
    }

    /**
     * @param array<int,ProjectAdminDto> $items
     */
    private function cloneProjectAdmins(ApiCrudGatewayInterface $gateway, array $items, ProjectCopyContext $context): void
    {
        $seen = [];
        foreach ($items as $item) {
            if (isset($seen[$item->username])) {
                continue;
            }

            $dto = new ProjectAdminDto();
            $dto->username = $item->username;
            $dto->markPresent('username');
            $dto->project = $this->projectIdentifier($context->getTargetProject());
            $dto->markPresent('project');

            $gateway->createProjectAdmin($dto);
            $context->incrementCreated('project_admin');
            $seen[$item->username] = true;
        }
    }

    private function buildCrudCloneDto(string $type, JsonApiDto $source, ProjectCopyContext $context): JsonApiDto
    {
        $schema = DtoSchemaRegistry::schemaForType($type);
        $dtoClass = $schema->getDtoClass();
        /** @var JsonApiDto $target */
        $target = new $dtoClass();

        if ($type === 'project') {
            $target->id = $context->getTargetProject();
            $target->markPresent('id');
        } elseif ($type === 'mapset') {
            $target->id = $context->mapId('mapset', $source->getId());
            $target->markPresent('id');
        }

        foreach ($schema->getAttributes() as $fieldName => $fieldDefinition) {
            if (!$fieldDefinition->isWritable()) {
                continue;
            }
            $property = $fieldDefinition->getPropertyName();
            if (!DtoPropertyAccessor::isInitialized($source, $property)) {
                continue;
            }
            $value = DtoPropertyAccessor::get($source, $property);
            DtoPropertyAccessor::set($target, $property, $value);
            $target->markPresent($fieldName);
        }

        foreach ($schema->getRelationships() as $fieldName => $fieldDefinition) {
            if (!$fieldDefinition->isWritable()) {
                continue;
            }
            $property = $fieldDefinition->getPropertyName();
            if (!DtoPropertyAccessor::isInitialized($source, $property)) {
                continue;
            }
            $related = DtoPropertyAccessor::get($source, $property);
            if (!$related instanceof JsonApiDto) {
                continue;
            }
            $remapped = $this->remapRelationshipIdentifier($fieldDefinition->getTargetType(), $related->getId(), $context);
            DtoPropertyAccessor::set($target, $property, $remapped);
            $target->markPresent($fieldName);
        }

        return $target;
    }

    private function remapRelationshipIdentifier(string $type, $sourceId, ProjectCopyContext $context): JsonApiDto
    {
        if ($type === 'project') {
            return $this->projectIdentifier($context->getTargetProject());
        }

        if ($type === 'mapset') {
            $dto = new MapsetDto();
            $dto->id = (string) $context->mapId('mapset', $sourceId);
            $dto->markPresent('id');
            $dto->markAsIdentifierOnly();

            return $dto;
        }

        $dtoClass = DtoSchemaRegistry::classFromType($type);
        /** @var JsonApiDto $dto */
        $dto = new $dtoClass();
        $dto->id = $context->mapId($type, $sourceId);
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }

    /**
     * @param array<int,JsonApiDto> $items
     * @return array<int,JsonApiDto>
     */
    private function listByParentIds(ApiCrudGatewayInterface $gateway, string $type, string $filterField, array $items): array
    {
        $results = [];
        foreach ($items as $item) {
            foreach ($gateway->listResources($type, [
                $filterField => $item->getId(),
            ]) as $child) {
                $results[] = $child;
            }
        }

        return $results;
    }

    /**
     * @param array<int,MapsetDto> $mapsets
     * @return array<int,JsonApiDto>
     */
    private function listMapsetLayergroups(ApiCrudGatewayInterface $gateway, array $mapsets): array
    {
        $results = [];
        foreach ($mapsets as $mapset) {
            foreach ($gateway->listResources('mapset_layergroup', [
                'mapset_name' => $mapset->id,
            ]) as $item) {
                $results[] = $item;
            }
        }

        return $results;
    }

    private function projectIdentifier(string $id): ProjectDto
    {
        $dto = new ProjectDto();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }

    private function selgroupIdentifier(int $id): SelgroupDto
    {
        $dto = new SelgroupDto();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }

    private function layerIdentifier(int $id): LayerDto
    {
        $dto = new LayerDto();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }
}
