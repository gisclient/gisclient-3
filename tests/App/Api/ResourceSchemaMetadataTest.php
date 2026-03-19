<?php

use GisClient\Author\Api\Dto\ProjectSrsDto;
use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Persistence\EntitySchemaRegistry;
use PHPUnit\Framework\TestCase;

class ResourceSchemaMetadataTest extends TestCase
{
    public function testThemeResourceSchemaExposesContractMetadata(): void
    {
        $schema = ThemeDto::schema();

        $this->assertSame('theme', $schema->getType());
        $this->assertSame('theme_id', $schema->getPrimaryKey());
        $this->assertSame(['project', 'theme_name', 'theme_title', 'theme_order'], $schema->getRequiredOnCreate());
        $this->assertSame(['theme_id', 'project_name', 'theme_name', 'theme_title'], $schema->getFilterableFields());
        $this->assertSame(['theme_id', 'theme_order', 'theme_title', 'theme_name'], $schema->getSortableFields());
        $this->assertSame('theme_order', $schema->getDefaultSort());
        $this->assertSame('project', $schema->getRelationship('project')->getJsonApiName());
    }

    public function testThemeEntitySchemaExposesPersistenceMetadata(): void
    {
        $schema = EntitySchemaRegistry::schemaForType('theme');

        $this->assertSame('theme', $schema->getType());
        $this->assertSame('gisclient_34', $schema->getResolvedDbSchema());
        $this->assertSame('theme', $schema->getResolvedTable());
        $this->assertSame('theme_id', $schema->getPrimaryKey());
        $this->assertSame('project_name', $schema->getRelationshipColumn('project'));
        $this->assertContains('project_name', $schema->getWritableDbFields());
        $this->assertSame('numeric', $schema->getAttributeRule('theme_single')['type']);
        $this->assertSame('string', $schema->getAttributeRule('theme_name')['type']);
        $this->assertSame('symbol', $schema->getAttributeRule('symbol_name')['lookup']['table']);
    }

    public function testProjectSrsSchemaExposesContractMetadata(): void
    {
        $schema = ProjectSrsDto::schema();

        $this->assertSame('id', $schema->getPrimaryKey());
        $this->assertSame(['id', 'project_name', 'srid'], $schema->getFilterableFields());
        $this->assertSame(['id', 'srid'], $schema->getSortableFields());
        $this->assertSame('project', $schema->getRelationship('project')->getJsonApiName());
    }
}
