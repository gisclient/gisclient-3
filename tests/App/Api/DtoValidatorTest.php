<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Validation\DtoValidator;
use PHPUnit\Framework\TestCase;

class DtoValidatorTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testRejectsMissingRequiredAttributesOnCreate(): void
    {
        $validator = new DtoValidator();
        $dto = $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
        ]);

        try {
            $validator->validate($dto, true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $codes = array_column($exception->getErrors(), 'code');

            $this->assertContains('missing_required_attribute', $codes);
        }
    }

    public function testRelationshipSatisfiesRequiredLocalKey(): void
    {
        $validator = new DtoValidator();
        $dto = $this->makeDto(ProjectSrsDto::class, 3857, [
            'projparam' => '+proj=merc',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $validator->validate($dto, true, false);

        $this->assertTrue(true);
    }

    public function testRejectsRelationshipWithoutIdentifier(): void
    {
        $validator = new DtoValidator();
        $related = new ProjectDto();
        $dto = $this->makeDto(ProjectSrsDto::class, 3857, [
            'projparam' => '+proj=merc',
        ], [
            'project' => $related,
        ]);

        try {
            $validator->validate($dto, true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_relationship', $exception->getErrors()[0]['code']);
            $this->assertSame('/data/relationships/project/data/id', $exception->getErrors()[0]['source']['pointer']);
        }
    }
}
