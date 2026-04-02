<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\Serializer\DtoSerializer;

class ProjectExportService
{
    /**
     * @var ProjectTransferService
     */
    private $transferService;

    /**
     * @var DtoSerializer
     */
    private $serializer;

    public function __construct(ProjectTransferService $transferService, DtoSerializer $serializer)
    {
        $this->transferService = $transferService;
        $this->serializer = $serializer;
    }

    /**
     * Exports a full project graph as a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public function exportProject(ApiCrudGatewayInterface $gateway, string $projectName): array
    {
        $project = $gateway->findResource('project', $projectName);
        if (!$project instanceof ProjectDto) {
            throw new \InvalidArgumentException(sprintf("Project '%s' not found", $projectName));
        }

        $graph = $this->transferService->loadProjectGraph($gateway, $projectName, $project);

        $standardTypes = ['project_srs', 'catalog', 'link', 'theme', 'layergroup', 'mapset', 'layer', 'relation', 'class', 'style', 'field', 'mapset_layergroup'];

        $resources = [];
        $resources['project'] = [$this->serializer->serialize($graph['project'])];

        foreach ($standardTypes as $type) {
            $resources[$type] = array_map([$this->serializer, 'serialize'], $graph[$type]);
        }

        $resources['project_languages'] = array_map(
            static fn (ProjectLanguageDto $dto): array => [
                'language_id' => $dto->languageId,
            ],
            $graph['project_languages']
        );

        $resources['selgroup'] = array_map(
            static fn (SelgroupDto $dto): array => [
                'id' => (string) $dto->id,
                'selgroup_name' => $dto->selgroupName,
                'selgroup_title' => $dto->selgroupTitle,
                'selgroup_order' => $dto->selgroupOrder,
            ],
            $graph['selgroup']
        );

        $resources['selgroup_layer'] = array_map(
            static fn (SelgroupLayerDto $dto): array => [
                'selgroup_id' => (string) $dto->selgroup->id,
                'layer_id' => (string) $dto->layer->id,
            ],
            $graph['selgroup_layer']
        );

        $resources['project_admin'] = array_map(
            static fn (ProjectAdminDto $dto): array => [
                'username' => $dto->username,
            ],
            $graph['project_admin']
        );

        return [
            'format_version' => '1.0',
            'project_name' => $projectName,
            'resources' => $resources,
        ];
    }
}
