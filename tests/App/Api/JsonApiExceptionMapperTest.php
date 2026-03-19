<?php

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use PHPUnit\Framework\TestCase;

class JsonApiExceptionMapperTest extends TestCase
{
    public function testMapsApiExceptionToJsonApiError()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new ApiException(400, 'invalid_payload', 'Invalid Payload', 'Bad input', '/data'));

        $this->assertSame(400, $mapped['status']);
        $this->assertSame('invalid_payload', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('/data', $mapped['payload']['errors'][0]['source']['pointer']);
    }

    public function testMapsInternalErrorDetailsForFiveHundreds()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new ApiException(500, 'database_error', 'Database Error', 'SQLSTATE details here'));

        $this->assertSame(500, $mapped['status']);
        $this->assertSame('database_error', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('SQLSTATE details here', $mapped['payload']['errors'][0]['detail']);
    }

    public function testMapsValidationExceptionWithMultipleErrors()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new ValidationException([
            [
                'status' => '422',
                'code' => 'invalid_attribute',
                'title' => 'Invalid Attribute',
                'detail' => "Attribute 'foo' is not writable",
                'source' => [
                    'attribute' => 'foo',
                ],
            ],
            [
                'status' => '422',
                'code' => 'invalid_attribute_type',
                'title' => 'Invalid Attribute Type',
                'detail' => "Attribute 'bar' must be numeric",
                'source' => [
                    'attribute' => 'bar',
                ],
            ],
        ]));

        $this->assertSame(422, $mapped['status']);
        $this->assertCount(2, $mapped['payload']['errors']);
        $this->assertSame('invalid_attribute', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('invalid_attribute_type', $mapped['payload']['errors'][1]['code']);
    }

    public function testMapsValidationExceptionStatus()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new ValidationException([
            [
                'code' => 'invalid_attribute_type',
                'title' => 'Bad Request',
                'detail' => "Attribute 'xc' must be a number, string given",
                'source' => [
                    'pointer' => '/data/attributes/xc',
                ],
            ],
        ], 400));

        $this->assertSame(400, $mapped['status']);
    }

    public function testMapsGenericThrowableToInternalError()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new \RuntimeException('Unexpected failure'));

        $this->assertSame(500, $mapped['status']);
        $this->assertSame('internal_error', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('Unexpected failure', $mapped['payload']['errors'][0]['detail']);
    }

    public function testMapsValidationExceptionSourceVariantsToPointers()
    {
        $mapper = new JsonApiExceptionMapper();
        $mapped = $mapper->map(new ValidationException([
            [
                'status' => '422',
                'code' => 'invalid_id',
                'title' => 'Invalid Id',
                'detail' => 'Invalid identifier',
                'source' => [
                    'id' => true,
                ],
            ],
            [
                'status' => '422',
                'code' => 'invalid_relationship',
                'title' => 'Invalid Relationship',
                'detail' => 'Invalid relationship',
                'source' => [
                    'relationship' => 'theme',
                ],
            ],
            [
                'status' => '422',
                'code' => 'invalid_relationship_type',
                'title' => 'Invalid Relationship Type',
                'detail' => 'Invalid relationship type',
                'source' => [
                    'relationship_type' => 'theme',
                ],
            ],
            [
                'status' => '422',
                'code' => 'invalid_relationship_id',
                'title' => 'Invalid Relationship Id',
                'detail' => 'Invalid relationship id',
                'source' => [
                    'relationship_id' => 'theme',
                ],
            ],
            [
                'status' => '422',
                'code' => 'invalid_parameter',
                'title' => 'Invalid Parameter',
                'detail' => 'Invalid parameter',
                'source' => [
                    'parameter' => 'filter/name',
                ],
            ],
            [
                'status' => '422',
                'code' => 'unknown_source',
                'title' => 'Unknown Source',
                'detail' => 'Unknown source format',
                'source' => [
                    'foo' => 'bar',
                ],
            ],
        ]));

        $this->assertSame('/data/id', $mapped['payload']['errors'][0]['source']['pointer']);
        $this->assertSame('/data/relationships/theme/data', $mapped['payload']['errors'][1]['source']['pointer']);
        $this->assertSame('/data/relationships/theme/data/type', $mapped['payload']['errors'][2]['source']['pointer']);
        $this->assertSame('/data/relationships/theme/data/id', $mapped['payload']['errors'][3]['source']['pointer']);
        $this->assertSame('/filter/name', $mapped['payload']['errors'][4]['source']['pointer']);
        $this->assertArrayNotHasKey('source', $mapped['payload']['errors'][5]);
    }
}
