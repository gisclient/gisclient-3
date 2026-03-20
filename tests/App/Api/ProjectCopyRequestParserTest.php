<?php

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use PHPUnit\Framework\TestCase;

class ProjectCopyRequestParserTest extends TestCase
{
    public function testParseReturnsRequestWithDefaults()
    {
        $parser = new ProjectCopyRequestParser();

        $request = $parser->parse(json_encode([
            'source_project' => ' oldproj ',
            'target_project' => ' newproj ',
        ]));

        $this->assertSame('oldproj', $request->getSourceProject());
        $this->assertSame('newproj', $request->getTargetProject());
        $this->assertNull($request->getProjectTitle());
        $this->assertSame(ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME, $request->getMapsetNamingMode());
        $this->assertFalse($request->shouldRefreshPrivateMapfiles());
        $this->assertFalse($request->shouldRefreshPublicMapfiles());
        $this->assertFalse($request->shouldRefreshAnyMapfiles());
    }

    public function testParseReturnsExplicitOptions()
    {
        $parser = new ProjectCopyRequestParser();

        $request = $parser->parse(json_encode([
            'source_project' => 'source',
            'target_project' => 'target',
            'project_title' => 'Target project title',
            'mapset_naming_mode' => ProjectCopyRequestParser::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT,
            'refresh' => [
                'private_mapfiles' => true,
                'public_mapfiles' => true,
            ],
        ]));

        $this->assertSame('Target project title', $request->getProjectTitle());
        $this->assertSame(ProjectCopyRequestParser::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT, $request->getMapsetNamingMode());
        $this->assertTrue($request->shouldRefreshPrivateMapfiles());
        $this->assertTrue($request->shouldRefreshPublicMapfiles());
        $this->assertTrue($request->shouldRefreshAnyMapfiles());
    }

    public function testParseRejectsInvalidJson()
    {
        $parser = new ProjectCopyRequestParser();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Request body must be valid JSON');

        $parser->parse('{invalid');
    }

    public function testParseRejectsMissingAndInvalidFields()
    {
        $parser = new ProjectCopyRequestParser();

        try {
            $parser->parse(json_encode([
                'source_project' => '',
                'project_title' => '',
                'mapset_naming_mode' => 'invalid-mode',
                'refresh' => [
                    'private_mapfiles' => 'yes',
                ],
            ]));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertSame(400, $exception->getStatus());
            $this->assertCount(5, $errors);
            $this->assertSame('/source_project', $errors[0]['source']['pointer']);
            $this->assertSame('/target_project', $errors[1]['source']['pointer']);
            $this->assertSame('/project_title', $errors[2]['source']['pointer']);
            $this->assertSame('invalid_mapset_naming_mode', $errors[3]['code']);
            $this->assertSame('/refresh/private_mapfiles', $errors[4]['source']['pointer']);
        }
    }
}
