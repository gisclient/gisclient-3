<?php

use GisClient\Author\Api\Error\JsonApiExceptionMapper;
use GisClient\Author\Api\Exception\ApiException;
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
}
