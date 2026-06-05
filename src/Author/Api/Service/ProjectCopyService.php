<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\ProjectCopy\ProjectCopyContext;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequest;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\ProjectCopy\ProjectCopyResponse;
use GisClient\MapServer\Writer\MapfileWriterInterface;

class ProjectCopyService
{
    /**
     * @var ApiCrudGatewayInterface
     */
    private $gateway;

    /**
     * @var ProjectTransferService
     */
    private $transferService;

    /**
     * @var MapfileWriterInterface
     */
    private $mapfileWriter;

    public function __construct(
        ApiCrudGatewayInterface $gateway,
        ProjectTransferService $transferService,
        MapfileWriterInterface $mapfileWriter
    ) {
        $this->gateway = $gateway;
        $this->transferService = $transferService;
        $this->mapfileWriter = $mapfileWriter;
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

        $project = $this->requireProject($sourceProject);
        $graph = $this->transferService->loadProjectGraph($this->gateway, $sourceProject, $project);
        $context = new ProjectCopyContext($sourceProject, $targetProject);

        if ($request->getProjectTitle() !== null) {
            $graph['project']->projectTitle = $request->getProjectTitle();
        }

        $this->prepareMapsetNames($request, $graph['mapset'], $context);
        $this->augmentProjectAdmins($graph, $sourceProject);

        $this->gateway->runAtomically(function () use ($graph, $context): void {
            $this->transferService->createProjectGraph($this->gateway, $graph, $context);
        });

        $this->refreshMapfilesIfRequested($request, $context);

        return new ProjectCopyResponse(
            $sourceProject,
            $targetProject,
            $context->getCreatedCounters(),
            $context->getWarnings()
        );
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
     * @param array<string,mixed> $graph
     */
    private function augmentProjectAdmins(array &$graph, string $sourceProject): void
    {
        $username = $this->currentUsername();
        if ($username === null) {
            return;
        }

        foreach ($graph['project_admin'] as $item) {
            if ($item->username === $username) {
                return;
            }
        }

        $dto = new ProjectAdminDto();
        $dto->username = $username;
        $dto->markPresent('username');
        $dto->project = $this->projectIdentifier($sourceProject);
        $dto->markPresent('project');
        $graph['project_admin'][] = $dto;
    }

    /**
     * @param array<int,\GisClient\Author\Api\Dto\MapsetDto> $mapsets
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

        $targetProject = $context->getTargetProject();
        $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
        $mapsets = \GCAuthor::getMapsets($targetProject);

        if ($request->shouldRefreshPrivateMapfiles()) {
            try {
                foreach ($mapsets as $mapsetData) {
                    $this->mapfileWriter->refreshMapset($targetProject, $mapsetData['mapset_name'], false, $refreshLayerMapfile);
                }
                $this->assertNoRefreshErrors();
            } catch (\Throwable $exception) {
                $context->addWarning('Private mapfile refresh failed: ' . $exception->getMessage());
            }
        }

        if ($request->shouldRefreshPublicMapfiles()) {
            try {
                foreach ($mapsets as $mapsetData) {
                    $this->mapfileWriter->refreshMapset($targetProject, $mapsetData['mapset_name'], true, $refreshLayerMapfile);
                }
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
}
