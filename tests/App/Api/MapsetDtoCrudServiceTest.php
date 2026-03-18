<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectDto;
use PHPUnit\Framework\TestCase;

class MapsetDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetDto::class, 'base', [
            'mapset_title' => 'Base map',
            'mapset_srid' => 3857,
            'displayprojection' => 4326,
            'maxscale' => 50000,
            'mapset_extent' => '0 0 10 10',
            'mapset_order' => 1,
            'private' => 0,
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('mapset', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testCreateMapsProjectRelationshipAndKeepsSridAttributes(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(MapsetDto::class, 'base', [
            'mapset_title' => 'Base map',
            'mapset_srid' => 3857,
            'displayprojection' => 4326,
            'maxscale' => 50000,
            'mapset_extent' => '0 0 10 10',
            'mapset_order' => 1,
            'private' => 0,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $payload = $service->createResource('mapset', $dto);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame(3857, $repository->createdAttributes['mapset_srid']);
        $this->assertSame(4326, $repository->createdAttributes['displayprojection']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertSame(3857, $payload['data']['attributes']['mapset_srid']);
        $this->assertSame(4326, $payload['data']['attributes']['displayprojection']);
    }

    public function testGetKeepsSridFieldsAsAttributes(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn ($definition, $id) => [
            'mapset_name' => (string) $id,
            'project_name' => 'milano',
            'mapset_title' => 'Base map',
            'mapset_srid' => '3857',
            'displayprojection' => '4326',
            'maxscale' => '50000',
            'mapset_extent' => '0 0 10 10',
            'mapset_order' => 1,
            'private' => '0',
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('mapset', 'base');

        $this->assertSame(3857, $payload['data']['attributes']['mapset_srid']);
        $this->assertSame(4326, $payload['data']['attributes']['displayprojection']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }
}
