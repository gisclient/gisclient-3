<?php

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;

class ProjectCopyGatewayStub implements ApiCrudGatewayInterface
{
    /**
     * @var array<string,array<int,object>>
     */
    public $resources = [];

    /**
     * @var array<int,array{type:string,dto:object}>
     */
    public $created = [];

    /**
     * @var array<string,int>
     */
    private $nextIds = [
        'project_srs' => 900,
        'catalog' => 950,
        'link' => 975,
        'theme' => 1000,
        'layergroup' => 2000,
        'layer' => 3000,
        'class' => 4000,
        'style' => 5000,
        'field' => 6000,
        'selgroup' => 7000,
        'mapset_layergroup' => 8000,
    ];

    /**
     * @param array<string,array<int,object>> $resources
     */
    public function __construct(array $resources = [])
    {
        $this->resources = $resources;
    }

    public function findResource(string $type, $id): ?JsonApiDto
    {
        foreach ($this->resources[$type] ?? [] as $item) {
            if ($item instanceof JsonApiDto && $item->getId() == $id) {
                return $item;
            }
        }

        return null;
    }

    public function listResources(string $type, array $filter = [], ?string $sort = null): array
    {
        $items = array_values(array_filter($this->resources[$type] ?? [], fn ($item): bool => $this->matchesFilter($item, $filter)));

        return $items;
    }

    public function createResource(string $type, JsonApiDto $dto): JsonApiDto
    {
        $created = clone $dto;
        if (isset($this->nextIds[$type]) && property_exists($created, 'id') && $created->id === null) {
            $created->id = $this->nextIds[$type]++;
            $created->markPresent('id');
        }

        $this->created[] = [
            'type' => $type,
            'dto' => $created,
        ];
        $this->resources[$type][] = $created;

        return $created;
    }

    public function deleteResource(string $type, $id): void
    {
    }

    public function listProjectLanguages(string $projectName): array
    {
        return array_values(array_filter($this->resources['project_languages'] ?? [], static fn (ProjectLanguageDto $dto): bool => $dto->project->id === $projectName));
    }

    public function createProjectLanguage(ProjectLanguageDto $dto): ProjectLanguageDto
    {
        $this->created[] = [
            'type' => 'project_languages',
            'dto' => $dto,
        ];
        $this->resources['project_languages'][] = $dto;

        return $dto;
    }

    public function listProjectAdmins(string $projectName): array
    {
        return array_values(array_filter($this->resources['project_admin'] ?? [], static fn (ProjectAdminDto $dto): bool => $dto->project->id === $projectName));
    }

    public function createProjectAdmin(ProjectAdminDto $dto): ProjectAdminDto
    {
        $this->created[] = [
            'type' => 'project_admin',
            'dto' => $dto,
        ];
        $this->resources['project_admin'][] = $dto;

        return $dto;
    }

    public function listSelgroups(string $projectName): array
    {
        return array_values(array_filter($this->resources['selgroup'] ?? [], static fn (SelgroupDto $dto): bool => $dto->project->id === $projectName));
    }

    public function createSelgroup(SelgroupDto $dto): SelgroupDto
    {
        if ($dto->id === null) {
            $dto->id = $this->nextIds['selgroup']++;
            $dto->markPresent('id');
        }

        $this->created[] = [
            'type' => 'selgroup',
            'dto' => $dto,
        ];
        $this->resources['selgroup'][] = $dto;

        return $dto;
    }

    public function listSelgroupLayers(string $projectName): array
    {
        return array_values(array_filter($this->resources['selgroup_layer'] ?? [], function (SelgroupLayerDto $dto) use ($projectName): bool {
            foreach ($this->resources['selgroup'] ?? [] as $selgroup) {
                if ($selgroup->id === $dto->selgroup->id && $selgroup->project->id === $projectName) {
                    return true;
                }
            }

            return false;
        }));
    }

    public function createSelgroupLayer(SelgroupLayerDto $dto): SelgroupLayerDto
    {
        $this->created[] = [
            'type' => 'selgroup_layer',
            'dto' => $dto,
        ];
        $this->resources['selgroup_layer'][] = $dto;

        return $dto;
    }

    public function runAtomically(callable $operation)
    {
        return $operation();
    }

    /**
     * @param object $item
     */
    private function matchesFilter($item, array $filter): bool
    {
        foreach ($filter as $field => $value) {
            $property = $this->fieldToProperty($field);
            if ($field === 'project_name' && property_exists($item, 'project') && $item->project instanceof JsonApiDto) {
                if ($item->project->id != $value) {
                    return false;
                }
                continue;
            }
            if ($field === 'theme_id' && property_exists($item, 'theme') && $item->theme instanceof JsonApiDto) {
                if ($item->theme->id != $value) {
                    return false;
                }
                continue;
            }
            if ($field === 'layergroup_id' && property_exists($item, 'layergroup') && $item->layergroup instanceof JsonApiDto) {
                if ($item->layergroup->id != $value) {
                    return false;
                }
                continue;
            }
            if ($field === 'layer_id' && property_exists($item, 'layer') && $item->layer instanceof JsonApiDto) {
                if ($item->layer->id != $value) {
                    return false;
                }
                continue;
            }
            if ($field === 'class_id' && property_exists($item, 'class') && $item->class instanceof JsonApiDto) {
                if ($item->class->id != $value) {
                    return false;
                }
                continue;
            }
            if ($field === 'mapset_name' && property_exists($item, 'mapset') && $item->mapset instanceof JsonApiDto) {
                if ($item->mapset->id != $value) {
                    return false;
                }
                continue;
            }
            if (!property_exists($item, $property)) {
                return false;
            }
            if ($item->{$property} != $value) {
                return false;
            }
        }

        return true;
    }

    private function fieldToProperty(string $field): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field))));
    }
}
