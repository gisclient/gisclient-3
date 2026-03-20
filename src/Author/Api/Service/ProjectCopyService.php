<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\MapsetLayergroupDto;
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
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequest;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\ProjectCopy\ProjectCopyResponse;

class ProjectCopyService
{
    /**
     * @var ApiCrudGatewayInterface
     */
    private $gateway;

    public function __construct(ApiCrudGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function execute(ProjectCopyRequest $request): ProjectCopyResponse
    {
        $targetProject = $this->validateTargetProjectName($request->getTargetProject());
        $sourceProject = $request->getSourceProject();

        $sourceRoot = $this->gateway->findResource('project', $sourceProject);
        if ($sourceRoot === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("project '%s' not found", $sourceProject), '/source_project');
        }

        if ($this->gateway->findResource('project', $targetProject) !== null) {
            throw new ApiException(409, 'target_project_exists', 'Conflict', 'Target project already exists', '/target_project');
        }

        $graph = $this->loadSourceGraph($sourceProject);
        $context = new ProjectCopyContext($sourceProject, $targetProject);

        $this->prepareMapsetNames($request, $graph['mapset'], $context);

        $this->gateway->runAtomically(function () use ($graph, $context): void {
            $this->cloneCrudCollection('project', [$graph['project']], $context);
            $this->cloneCrudCollection('project_srs', $graph['project_srs'], $context);
            $this->cloneProjectLanguages($graph['project_languages'], $context);
            $this->cloneCrudCollection('catalog', $graph['catalog'], $context);
            $this->cloneCrudCollection('link', $graph['link'], $context);
            $this->cloneCrudCollection('theme', $graph['theme'], $context);
            $this->cloneCrudCollection('layergroup', $graph['layergroup'], $context);
            $this->cloneCrudCollection('mapset', $graph['mapset'], $context);
            $this->cloneCrudCollection('layer', $graph['layer'], $context);
            $this->cloneCrudCollection('class', $graph['class'], $context);
            $this->cloneCrudCollection('style', $graph['style'], $context);
            $this->cloneCrudCollection('field', $graph['field'], $context);
            $this->cloneCrudCollection('mapset_layergroup', $graph['mapset_layergroup'], $context);
            $this->cloneSelgroups($graph['selgroup'], $context);
            $this->cloneSelgroupLayers($graph['selgroup_layer'], $context);
            $this->cloneProjectAdmins($graph['project_admin'], $context);
        });

        $this->refreshMapfilesIfRequested($request, $context);

        return new ProjectCopyResponse(
            $sourceProject,
            $targetProject,
            $context->getCreatedCounters(),
            $context->getWarnings()
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function loadSourceGraph(string $sourceProject): array
    {
        $themes = $this->gateway->listResources('theme', [
            'project_name' => $sourceProject,
        ]);
        $layergroups = $this->listByParentIds('layergroup', 'theme_id', $themes);
        $catalogs = $this->gateway->listResources('catalog', [
            'project_name' => $sourceProject,
        ]);
        $mapsets = $this->gateway->listResources('mapset', [
            'project_name' => $sourceProject,
        ]);
        $layers = $this->listByParentIds('layer', 'layergroup_id', $layergroups);
        $classes = $this->listByParentIds('class', 'layer_id', $layers);
        $styles = $this->listByParentIds('style', 'class_id', $classes);
        $fields = $this->listByParentIds('field', 'layer_id', $layers);
        $mapsetLayergroups = $this->listMapsetLayergroups($mapsets);

        return [
            'project' => $this->requireProject($sourceProject),
            'project_srs' => $this->gateway->listResources('project_srs', [
                'project_name' => $sourceProject,
            ]),
            'project_languages' => $this->gateway->listProjectLanguages($sourceProject),
            'catalog' => $catalogs,
            'link' => $this->gateway->listResources('link', [
                'project_name' => $sourceProject,
            ]),
            'theme' => $themes,
            'layergroup' => $layergroups,
            'mapset' => $mapsets,
            'layer' => $layers,
            'class' => $classes,
            'style' => $styles,
            'field' => $fields,
            'mapset_layergroup' => $mapsetLayergroups,
            'selgroup' => $this->gateway->listSelgroups($sourceProject),
            'selgroup_layer' => $this->gateway->listSelgroupLayers($sourceProject),
            'project_admin' => $this->augmentProjectAdmins($this->gateway->listProjectAdmins($sourceProject), $sourceProject),
        ];
    }

    private function requireProject(string $projectName): ProjectDto
    {
        $project = $this->gateway->findResource('project', $projectName);
        if (!$project instanceof ProjectDto) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("project '%s' not found", $projectName), '/source_project');
        }

        return $project;
    }

    /**
     * @param array<int,JsonApiDto> $items
     */
    private function cloneCrudCollection(string $type, array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $target = $this->buildCrudCloneDto($type, $item, $context);
            $created = $this->gateway->createResource($type, $target);
            $context->incrementCreated($type);

            if ($item->getId() !== null && $created->getId() !== null) {
                $context->rememberMapping($type, $item->getId(), $created->getId());
            }
        }
    }

    /**
     * @param array<int,ProjectLanguageDto> $items
     */
    private function cloneProjectLanguages(array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $dto = new ProjectLanguageDto();
            $dto->languageId = $item->languageId;
            $dto->markPresent('language_id');
            $dto->project = $this->projectIdentifier($context->getTargetProject());
            $dto->markPresent('project');

            $this->gateway->createProjectLanguage($dto);
            $context->incrementCreated('project_languages');
        }
    }

    /**
     * @param array<int,SelgroupDto> $items
     */
    private function cloneSelgroups(array $items, ProjectCopyContext $context): void
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

            $created = $this->gateway->createSelgroup($dto);
            $context->rememberMapping('selgroup', $item->id, $created->id);
            $context->incrementCreated('selgroup');
        }
    }

    /**
     * @param array<int,SelgroupLayerDto> $items
     */
    private function cloneSelgroupLayers(array $items, ProjectCopyContext $context): void
    {
        foreach ($items as $item) {
            $dto = new SelgroupLayerDto();
            $dto->selgroup = $this->selgroupIdentifier((int) $context->mapId('selgroup', $item->selgroup->id));
            $dto->markPresent('selgroup');
            $dto->layer = $this->layerIdentifier((int) $context->mapId('layer', $item->layer->id));
            $dto->markPresent('layer');

            $this->gateway->createSelgroupLayer($dto);
            $context->incrementCreated('selgroup_layer');
        }
    }

    /**
     * @param array<int,ProjectAdminDto> $items
     */
    private function cloneProjectAdmins(array $items, ProjectCopyContext $context): void
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

            $this->gateway->createProjectAdmin($dto);
            $context->incrementCreated('project_admin');
            $seen[$item->username] = true;
        }
    }

    /**
     * @param array<int,MapsetDto> $mapsets
     */
    private function prepareMapsetNames(ProjectCopyRequest $request, array $mapsets, ProjectCopyContext $context): void
    {
        $generated = [];

        foreach ($mapsets as $mapset) {
            $targetName = $this->generateMapsetName(
                $request->getMapsetNamingMode(),
                (string) $mapset->id,
                $context->getSourceProject(),
                $context->getTargetProject()
            );

            if (isset($generated[$targetName])) {
                throw new ApiException(409, 'mapset_name_collision', 'Conflict', 'Generated mapset names must be unique', '/mapset_naming_mode');
            }

            if ($this->gateway->findResource('mapset', $targetName) !== null) {
                throw new ApiException(409, 'mapset_name_collision', 'Conflict', sprintf("Generated mapset '%s' already exists", $targetName), '/mapset_naming_mode');
            }

            $generated[$targetName] = true;
            $context->rememberMapping('mapset', $mapset->id, $targetName);
        }
    }

    private function generateMapsetName(string $mode, string $sourceMapsetName, string $sourceProject, string $targetProject): string
    {
        if ($mode === ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME) {
            return str_replace($sourceProject, $targetProject, $sourceMapsetName);
        }

        if ($mode === ProjectCopyRequestParser::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT) {
            $logicalName = $sourceMapsetName;
            $prefix = $sourceProject . '_';
            if (strpos($sourceMapsetName, $prefix) === 0) {
                $logicalName = substr($sourceMapsetName, strlen($prefix));
            }

            return $targetProject . '_' . $logicalName;
        }

        throw new ApiException(400, 'invalid_mapset_naming_mode', 'Invalid Mapset Naming Mode', 'Unsupported mapset naming mode', '/mapset_naming_mode');
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
    private function listByParentIds(string $type, string $filterField, array $items): array
    {
        $results = [];
        foreach ($items as $item) {
            foreach ($this->gateway->listResources($type, [
                $filterField => $item->getId(),
            ]) as $child) {
                $results[] = $child;
            }
        }

        return $results;
    }

    /**
     * @param array<int,MapsetDto> $mapsets
     * @return array<int,MapsetLayergroupDto>
     */
    private function listMapsetLayergroups(array $mapsets): array
    {
        $results = [];
        foreach ($mapsets as $mapset) {
            foreach ($this->gateway->listResources('mapset_layergroup', [
                'mapset_name' => $mapset->id,
            ]) as $item) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * @param array<int,ProjectAdminDto> $items
     * @return array<int,ProjectAdminDto>
     */
    private function augmentProjectAdmins(array $items, string $sourceProject): array
    {
        $username = $this->currentUsername();
        if ($username === null) {
            return $items;
        }

        foreach ($items as $item) {
            if ($item->username === $username) {
                return $items;
            }
        }

        $dto = new ProjectAdminDto();
        $dto->username = $username;
        $dto->markPresent('username');
        $dto->project = $this->projectIdentifier($sourceProject);
        $dto->markPresent('project');
        $items[] = $dto;

        return $items;
    }

    private function currentUsername(): ?string
    {
        $auth = \GCApp::getAuthenticationHandler();
        if (!$auth->isAuthenticated()) {
            return null;
        }

        return $auth->getToken()->getUsername();
    }

    private function refreshMapfilesIfRequested(ProjectCopyRequest $request, ProjectCopyContext $context): void
    {
        if (!$request->shouldRefreshAnyMapfiles()) {
            return;
        }

        $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;

        if ($request->shouldRefreshPrivateMapfiles()) {
            try {
                \GCAuthor::refreshMapfiles($context->getTargetProject(), false, $refreshLayerMapfile);
                $this->assertNoRefreshErrors();
            } catch (\Throwable $exception) {
                $context->addWarning('Private mapfile refresh failed: ' . $exception->getMessage());
            }
        }

        if ($request->shouldRefreshPublicMapfiles()) {
            try {
                \GCAuthor::refreshMapfiles($context->getTargetProject(), true, $refreshLayerMapfile);
                $this->assertNoRefreshErrors();
            } catch (\Throwable $exception) {
                $context->addWarning('Public mapfile refresh failed: ' . $exception->getMessage());
            }
        }
    }

    private function assertNoRefreshErrors(): void
    {
        if (!class_exists('GCError', false)) {
            return;
        }

        $errors = \GCError::get();
        if (!empty($errors)) {
            throw new \RuntimeException(implode("\n", $errors));
        }
    }

    private function validateTargetProjectName(string $targetProject): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $targetProject) !== 1) {
            throw new ApiException(
                422,
                'invalid_target_project_name',
                'Invalid Target Project Name',
                'Target project name must match /^[A-Za-z_][A-Za-z0-9_]*$/',
                '/target_project'
            );
        }

        return $targetProject;
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

    private function layerIdentifier(int $id): \GisClient\Author\Api\Dto\LayerDto
    {
        $dto = new \GisClient\Author\Api\Dto\LayerDto();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }
}
