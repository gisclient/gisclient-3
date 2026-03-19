<?php

use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\ResourceData;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use PHPUnit\Framework\TestCase;

class JsonApiSerializerTest extends TestCase
{
    public function testDeserializeRequestBodyReturnsDtoWithRelationships()
    {
        $serializer = new JsonApiSerializer();

        $dto = $serializer->deserializeRequestBody(json_encode([
            'data' => [
                'type' => 'theme',
                'id' => '3',
                'attributes' => [
                    'theme_name' => 'base',
                    'theme_title' => 'Base',
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'milano',
                        ],
                    ],
                ],
            ],
        ]), 'theme');

        $this->assertSame(3, $dto->getId());
        $this->assertTrue($dto->isPresent('theme_name'));
        $this->assertTrue($dto->isPresent('project'));
        $this->assertSame('milano', $dto->project->getId());
        $this->assertTrue($dto->project->isIdentifierOnly());
    }

    public function testSerializeResourceUsesDtoRelationshipsAndTypedAttributes()
    {
        $serializer = new JsonApiSerializer();
        $schema = ThemeDto::schema();

        $payload = $serializer->serializeResource(new ResourceData($schema, [
            'theme_id' => 3,
            'theme_name' => 'base',
            'theme_single' => '0',
            'radio' => '1',
            'project_name' => 'milano',
        ]));

        $this->assertSame('3', $payload['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['theme_single']);
        $this->assertSame(1, $payload['data']['attributes']['radio']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }

    public function testDeserializeRequestBodyRejectsStringifiedNumericAttributes()
    {
        $serializer = new JsonApiSerializer();

        try {
            $serializer->deserializeRequestBody(json_encode([
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'xc' => '501090',
                        'yc' => '5022596',
                        'project_srid' => '32632',
                    ],
                ],
            ]), 'project');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getStatus());
            $errors = $exception->getErrors();
            $this->assertCount(3, $errors);
            $this->assertSame('/data/attributes/xc', $errors[0]['source']['pointer']);
            $this->assertSame('/data/attributes/yc', $errors[1]['source']['pointer']);
            $this->assertSame('/data/attributes/project_srid', $errors[2]['source']['pointer']);
        }
    }

    public function testDeserializeRequestBodyRejectsUnknownAttributes()
    {
        $serializer = new JsonApiSerializer();

        try {
            $serializer->deserializeRequestBody(json_encode([
                'data' => [
                    'type' => 'theme',
                    'id' => '3',
                    'attributes' => [
                        'theme_name' => 'base',
                        'theme_title' => 'Base',
                        'mapset_name' => 'base',
                    ],
                ],
            ]), 'theme');
            $this->fail('Expected ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(400, $exception->getStatus());
            $this->assertSame('invalid_attribute', $exception->getErrorCode());
            $this->assertSame('/data/attributes/mapset_name', $exception->getSourcePointer());
            $this->assertSame(
                "Attribute 'mapset_name' is not allowed for resource type 'theme'",
                $exception->getDetail()
            );
        }
    }

    public function testDeserializeRequestBodyRejectsUnknownRelationships()
    {
        $serializer = new JsonApiSerializer();

        try {
            $serializer->deserializeRequestBody(json_encode([
                'data' => [
                    'type' => 'theme',
                    'id' => '3',
                    'attributes' => [
                        'theme_name' => 'base',
                        'theme_title' => 'Base',
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'project',
                                'id' => 'milano',
                            ],
                        ],
                        'mapset' => [
                            'data' => [
                                'type' => 'mapset',
                                'id' => 'base',
                            ],
                        ],
                    ],
                ],
            ]), 'theme');
            $this->fail('Expected ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(400, $exception->getStatus());
            $this->assertSame('invalid_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/mapset', $exception->getSourcePointer());
            $this->assertSame(
                "Relationship 'mapset' is not allowed for resource type 'theme'",
                $exception->getDetail()
            );
        }
    }

    public function testDeserializeRequestBodyRejectsRelationshipTypeMismatchWithSpecificCode()
    {
        $serializer = new JsonApiSerializer();

        try {
            $serializer->deserializeRequestBody(json_encode([
                'data' => [
                    'type' => 'theme',
                    'id' => '3',
                    'attributes' => [
                        'theme_name' => 'base',
                        'theme_title' => 'Base',
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'mapset',
                                'id' => 'milano',
                            ],
                        ],
                    ],
                ],
            ]), 'theme');
            $this->fail('Expected ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('invalid_relationship_type', $exception->getErrorCode());
            $this->assertSame('/data/relationships/project/data/type', $exception->getSourcePointer());
        }
    }

    public function testDeserializeRequestBodyRejectsRelationshipWithoutIdWithSpecificCode()
    {
        $serializer = new JsonApiSerializer();

        try {
            $serializer->deserializeRequestBody(json_encode([
                'data' => [
                    'type' => 'theme',
                    'id' => '3',
                    'attributes' => [
                        'theme_name' => 'base',
                        'theme_title' => 'Base',
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'project',
                            ],
                        ],
                    ],
                ],
            ]), 'theme');
            $this->fail('Expected ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('invalid_relationship_id', $exception->getErrorCode());
            $this->assertSame('/data/relationships/project/data/id', $exception->getSourcePointer());
        }
    }
}
