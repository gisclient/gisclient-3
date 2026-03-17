<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\LinkDto;
use GisClient\Author\Api\Dto\ProjectDto;
use PHPUnit\Framework\TestCase;

class LinkDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LinkDto::class, 2, [
            'link_name' => 'Docs',
            'link_def' => 'https://example.test',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('link', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LinkDto::class, 2, [
            'link_name' => 'Docs',
            'link_def' => 'https://example.test',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $payload = $service->createResource('link', $dto);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }
}
