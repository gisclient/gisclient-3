<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectPrintLogoDto;
use GisClient\Author\Shared\Metadata\ProjectPrintLogoMetadata;
use PHPUnit\Framework\TestCase;

class ProjectPrintLogoDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    // PNG 1x1
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    // GIF 1x1
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function testCreateStoresLogoAndRendersParentRelationship(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);

        $payload = $service->createResource('project_print_logo', $this->logoDto('sx', self::PNG));

        $this->assertSame('default', $repository->createdAttributes['project_name']);
        $this->assertSame('sx', $repository->createdAttributes['logo_position']);
        $this->assertSame(self::PNG, $repository->createdAttributes['logo_data']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
        $this->assertSame(self::PNG, $payload['data']['attributes']['logo_data']);
    }

    /**
     * @dataProvider invalidLogoDataProvider
     */
    public function testCreateRejectsInvalidLogoData(string $logoData): void
    {
        $service = TestApiCrudService::create();

        $this->assertValidationException(function () use ($service, $logoData): void {
            $service->createResource('project_print_logo', $this->logoDto('dx', $logoData));
        }, 422, 'invalid_attribute_value', '/data/attributes/logo_data');
    }

    public function invalidLogoDataProvider(): array
    {
        return [
            'not base64' => ['not base64!'],
            'not an image' => [base64_encode('hello world')],
            'gif not allowed' => [self::GIF],
            'svg not allowed' => [base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            'too large' => [base64_encode(
                base64_decode(self::PNG) . str_repeat("\0", ProjectPrintLogoMetadata::MAX_LOGO_BYTES)
            )],
        ];
    }

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(ProjectPrintLogoDto::class, 1, [
            'logo_position' => 'sx',
            'logo_data' => self::PNG,
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('project_print_logo', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    private function logoDto(string $position, string $logoData): ProjectPrintLogoDto
    {
        /** @var ProjectPrintLogoDto $dto */
        $dto = $this->makeDto(ProjectPrintLogoDto::class, 1, [
            'logo_position' => $position,
            'logo_data' => $logoData,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'default'),
        ]);

        return $dto;
    }
}
