<?php

use GisClient\Author\Api\Error\JsonApiExceptionMapper;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use PHPUnit\Framework\TestCase;

class JsonApiExceptionMapperTest extends TestCase
{
    public function testMapsApiExceptionToJsonApiError()
    {
        $mapper = new JsonApiExceptionMapper(new JsonApiSerializer());
        $mapped = $mapper->map(new ApiException(400, 'invalid_payload', 'Invalid Payload', 'Bad input', '/data'));

        $this->assertSame(400, $mapped['status']);
        $this->assertSame('invalid_payload', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('/data', $mapped['payload']['errors'][0]['source']['pointer']);
    }

    public function testMapsInternalErrorDetailsForFiveHundreds()
    {
        $mapper = new JsonApiExceptionMapper(new JsonApiSerializer());
        $mapped = $mapper->map(new ApiException(500, 'database_error', 'Database Error', 'SQLSTATE details here'));

        $this->assertSame(500, $mapped['status']);
        $this->assertSame('database_error', $mapped['payload']['errors'][0]['code']);
        $this->assertSame('SQLSTATE details here', $mapped['payload']['errors'][0]['detail']);
    }

    public function testMapsValidationExceptionWithMultipleErrors()
    {
        $mapper = new JsonApiExceptionMapper(new JsonApiSerializer());
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
}
