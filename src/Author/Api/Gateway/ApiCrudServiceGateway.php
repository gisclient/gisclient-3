<?php

namespace GisClient\Author\Api\Gateway;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Service\ApiCrudService;

class ApiCrudServiceGateway implements ApiCrudGatewayInterface
{
    /**
     * @var ApiCrudService
     */
    private $apiCrudService;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var int
     */
    private $savepointCounter = 0;

    public function __construct(ApiCrudService $apiCrudService, ?\PDO $db = null)
    {
        $this->apiCrudService = $apiCrudService;
        $this->db = $db ?: \GCApp::getDB();
    }

    public function findResource(string $type, $id): ?JsonApiDto
    {
        try {
            return $this->apiCrudService->getResource($type, $id);
        } catch (ApiException $exception) {
            if ($exception->getStatus() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    public function listResources(string $type, array $filter = [], ?string $sort = null): array
    {
        $items = [];
        $offset = 0;

        do {
            $query = [
                'limit' => 200,
                'offset' => $offset,
                'filter' => $filter,
            ];
            if ($sort !== null) {
                $query['sort'] = $sort;
            }

            $page = $this->apiCrudService->listResources($type, $query);
            foreach ($page->getItems() as $item) {
                $items[] = $item;
            }
            $offset += $page->getLimit();
        } while ($offset < $page->getTotal());

        return $items;
    }

    public function createResource(string $type, JsonApiDto $dto): JsonApiDto
    {
        return $this->apiCrudService->createResource($type, $dto);
    }

    public function deleteResource(string $type, $id): void
    {
        $this->apiCrudService->deleteResource($type, $id);
    }

    public function listProjectLanguages(string $projectName): array
    {
        $stmt = $this->db->prepare(
            'SELECT project_name, language_id
            FROM ' . DB_SCHEMA . '.project_languages
            WHERE project_name = :project_name
            ORDER BY language_id'
        );
        $stmt->execute([
            ':project_name' => $projectName,
        ]);

        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[] = $this->hydrateProjectLanguageDto($row);
        }

        return $items;
    }

    public function createProjectLanguage(ProjectLanguageDto $dto): ProjectLanguageDto
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.project_languages (project_name, language_id)
            VALUES (:project_name, :language_id)'
        );
        $stmt->execute([
            ':project_name' => $dto->project->id,
            ':language_id' => $dto->languageId,
        ]);

        return $dto;
    }

    public function listProjectAdmins(string $projectName): array
    {
        $stmt = $this->db->prepare(
            'SELECT project_name, username
            FROM ' . DB_SCHEMA . '.project_admin
            WHERE project_name = :project_name
            ORDER BY username'
        );
        $stmt->execute([
            ':project_name' => $projectName,
        ]);

        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[] = $this->hydrateProjectAdminDto($row);
        }

        return $items;
    }

    public function createProjectAdmin(ProjectAdminDto $dto): ProjectAdminDto
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.project_admin (project_name, username)
            VALUES (:project_name, :username)'
        );
        $stmt->execute([
            ':project_name' => $dto->project->id,
            ':username' => $dto->username,
        ]);

        return $dto;
    }

    public function listSelgroups(string $projectName): array
    {
        $stmt = $this->db->prepare(
            'SELECT selgroup_id, project_name, selgroup_name, selgroup_title, selgroup_order
            FROM ' . DB_SCHEMA . '.selgroup
            WHERE project_name = :project_name
            ORDER BY selgroup_order, selgroup_id'
        );
        $stmt->execute([
            ':project_name' => $projectName,
        ]);

        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[] = $this->hydrateSelgroupDto($row);
        }

        return $items;
    }

    public function createSelgroup(SelgroupDto $dto): SelgroupDto
    {
        if ($dto->id === null) {
            $dto->id = \GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, 'selgroup', 'selgroup_id');
            $dto->markPresent('id');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.selgroup (selgroup_id, project_name, selgroup_name, selgroup_title, selgroup_order)
            VALUES (:selgroup_id, :project_name, :selgroup_name, :selgroup_title, :selgroup_order)'
        );
        $stmt->execute([
            ':selgroup_id' => $dto->id,
            ':project_name' => $dto->project->id,
            ':selgroup_name' => $dto->selgroupName,
            ':selgroup_title' => $dto->selgroupTitle,
            ':selgroup_order' => $dto->selgroupOrder,
        ]);

        return $dto;
    }

    public function listSelgroupLayers(string $projectName): array
    {
        $stmt = $this->db->prepare(
            'SELECT sl.selgroup_id, sl.layer_id
            FROM ' . DB_SCHEMA . '.selgroup_layer sl
            INNER JOIN ' . DB_SCHEMA . '.selgroup s ON s.selgroup_id = sl.selgroup_id
            WHERE s.project_name = :project_name
            ORDER BY sl.selgroup_id, sl.layer_id'
        );
        $stmt->execute([
            ':project_name' => $projectName,
        ]);

        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[] = $this->hydrateSelgroupLayerDto($row);
        }

        return $items;
    }

    public function createSelgroupLayer(SelgroupLayerDto $dto): SelgroupLayerDto
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . DB_SCHEMA . '.selgroup_layer (selgroup_id, layer_id)
            VALUES (:selgroup_id, :layer_id)'
        );
        $stmt->execute([
            ':selgroup_id' => $dto->selgroup->id,
            ':layer_id' => $dto->layer->id,
        ]);

        return $dto;
    }

    public function runAtomically(callable $operation)
    {
        $savepoint = null;
        $ownsTransaction = false;

        if ($this->db->inTransaction()) {
            $savepoint = $this->createSavepointName();
            $this->db->exec('SAVEPOINT ' . $savepoint);
        } else {
            $this->db->beginTransaction();
            $ownsTransaction = true;
        }

        try {
            $result = $operation();
            if ($savepoint !== null) {
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            } elseif ($ownsTransaction) {
                $this->db->commit();
            }

            return $result;
        } catch (\Throwable $exception) {
            if ($savepoint !== null) {
                $this->rollbackSavepoint($savepoint);
            } elseif ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function createSavepointName(): string
    {
        $this->savepointCounter++;

        return 'gc_project_copy_sp_' . $this->savepointCounter;
    }

    private function rollbackSavepoint(string $savepoint): void
    {
        try {
            $this->db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
        } catch (\Throwable $rollbackException) {
            // Keep the original failure.
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrateProjectLanguageDto(array $row): ProjectLanguageDto
    {
        $dto = new ProjectLanguageDto();
        $dto->languageId = (string) $row['language_id'];
        $dto->markPresent('language_id');
        $dto->project = $this->projectIdentifier((string) $row['project_name']);
        $dto->markPresent('project');

        return $dto;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrateProjectAdminDto(array $row): ProjectAdminDto
    {
        $dto = new ProjectAdminDto();
        $dto->username = (string) $row['username'];
        $dto->markPresent('username');
        $dto->project = $this->projectIdentifier((string) $row['project_name']);
        $dto->markPresent('project');

        return $dto;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrateSelgroupDto(array $row): SelgroupDto
    {
        $dto = new SelgroupDto();
        $dto->id = (int) $row['selgroup_id'];
        $dto->markPresent('id');
        $dto->selgroupName = (string) $row['selgroup_name'];
        $dto->markPresent('selgroup_name');
        $dto->selgroupTitle = $row['selgroup_title'] !== null ? (string) $row['selgroup_title'] : null;
        $dto->markPresent('selgroup_title');
        $dto->selgroupOrder = $row['selgroup_order'] !== null ? (int) $row['selgroup_order'] : null;
        $dto->markPresent('selgroup_order');
        $dto->project = $this->projectIdentifier((string) $row['project_name']);
        $dto->markPresent('project');

        return $dto;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrateSelgroupLayerDto(array $row): SelgroupLayerDto
    {
        $dto = new SelgroupLayerDto();
        $dto->selgroup = $this->selgroupIdentifier((int) $row['selgroup_id']);
        $dto->markPresent('selgroup');
        $dto->layer = $this->layerIdentifier((int) $row['layer_id']);
        $dto->markPresent('layer');

        return $dto;
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
