<?php

use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Persistence\EntitySchemaRegistry;
use PHPUnit\Framework\TestCase;

class ResourceSchemaMetadataTest extends TestCase
{
    public function testThemeResourceSchemaExposesContractMetadata(): void
    {
        $schema = DtoSchemaRegistry::schemaForType('theme');

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

    public function testLocalizationResourceSchemaExposesContractMetadata(): void
    {
        $schema = DtoSchemaRegistry::schemaForType('localization');

        $this->assertSame('localization', $schema->getType());
        $this->assertSame('localization_id', $schema->getPrimaryKey());
        $this->assertSame(['project', 'pkey_id'], $schema->getRequiredOnCreate());
        $this->assertContains('localization_id', $schema->getFilterableFields());
        $this->assertContains('project_name', $schema->getFilterableFields());
        $this->assertContains('pkey_id', $schema->getFilterableFields());
        $this->assertContains('language_id', $schema->getFilterableFields());
        $this->assertContains('i18nf_id', $schema->getFilterableFields());
        $this->assertSame('localization_id', $schema->getDefaultSort());
        $this->assertSame('project', $schema->getRelationship('project')->getJsonApiName());
    }

    public function testLocalizationEntitySchemaExposesPersistenceMetadata(): void
    {
        $schema = EntitySchemaRegistry::schemaForType('localization');

        $this->assertSame('gisclient_34', $schema->getResolvedDbSchema());
        $this->assertSame('localization', $schema->getResolvedTable());
        $this->assertSame('localization_id', $schema->getPrimaryKey());
        $this->assertSame('project_name', $schema->getRelationshipColumn('project'));
        $this->assertContains('project_name', $schema->getWritableDbFields());
        $this->assertSame('e_language', $schema->getAttributeRule('language_id')['lookup']['table']);
        $this->assertSame('i18n_field', $schema->getAttributeRule('i18nf_id')['lookup']['table']);
    }

    public function testProjectSrsSchemaExposesContractMetadata(): void
    {
        $schema = DtoSchemaRegistry::schemaForType('project_srs');

        $this->assertSame('id', $schema->getPrimaryKey());
        $this->assertSame(['id', 'project_name', 'srid'], $schema->getFilterableFields());
        $this->assertSame(['id', 'srid'], $schema->getSortableFields());
        $this->assertSame('project', $schema->getRelationship('project')->getJsonApiName());
    }
}
