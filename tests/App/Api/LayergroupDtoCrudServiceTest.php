<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\ThemeDto;
use PHPUnit\Framework\TestCase;

class LayergroupDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresThemeRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LayergroupDto::class, 5, [
            'layergroup_name' => 'base',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('layergroup', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/theme/data');
    }

    public function testCreateMapsThemeRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LayergroupDto::class, 5, [
            'layergroup_name' => 'base',
        ], [
            'theme' => $this->identifierDto(ThemeDto::class, 4),
        ]);

        $payload = $service->createResource('layergroup', $dto);

        $this->assertSame('4', (string) $repository->createdAttributes['theme_id']);
        $this->assertSame('4', $payload['data']['relationships']['theme']['data']['id']);
    }
}
