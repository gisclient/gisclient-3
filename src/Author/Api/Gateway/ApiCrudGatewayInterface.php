<?php

namespace GisClient\Author\Api\Gateway;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;

interface ApiCrudGatewayInterface
{
    public function findResource(string $type, $id): ?JsonApiDto;

    /**
     * @return array<int,JsonApiDto>
     */
    public function listResources(string $type, array $filter = [], ?string $sort = null): array;

    public function createResource(string $type, JsonApiDto $dto): JsonApiDto;

    public function deleteResource(string $type, $id): void;

    /**
     * @return array<int,ProjectLanguageDto>
     */
    public function listProjectLanguages(string $projectName): array;

    public function createProjectLanguage(ProjectLanguageDto $dto): ProjectLanguageDto;

    /**
     * @return array<int,ProjectAdminDto>
     */
    public function listProjectAdmins(string $projectName): array;

    public function createProjectAdmin(ProjectAdminDto $dto): ProjectAdminDto;

    /**
     * @return array<int,SelgroupDto>
     */
    public function listSelgroups(string $projectName): array;

    public function createSelgroup(SelgroupDto $dto): SelgroupDto;

    /**
     * @return array<int,SelgroupLayerDto>
     */
    public function listSelgroupLayers(string $projectName): array;

    public function createSelgroupLayer(SelgroupLayerDto $dto): SelgroupLayerDto;

    /**
     * @template T
     * @param callable():T $operation
     * @return T
     */
    public function runAtomically(callable $operation);
}
