<?php

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use PHPUnit\Framework\TestCase;

class DtoEntityDefinitionProviderTest extends TestCase
{
    public function testBuildsEntityDefinitionFromDtoSchema()
    {
        $provider = new DtoEntityDefinitionProvider();

        $definition = $provider->getEntityDefinition('theme');

        $this->assertSame('theme', $definition->getType());
        $this->assertSame('gisclient_34', $definition->getSchema());
        $this->assertSame('theme', $definition->getTable());
        $this->assertSame('theme_id', $definition->getPrimaryKey());
        $this->assertSame(['project_name', 'theme_name', 'theme_title', 'theme_order'], $definition->getRequiredOnCreate());
        $this->assertSame(['theme_id', 'project_name', 'theme_name', 'theme_title'], $definition->getFilterableFields());
        $this->assertSame(['theme_id', 'theme_order', 'theme_title', 'theme_name'], $definition->getSortableFields());
        $this->assertSame('theme_order', $definition->getDefaultSort());
        $this->assertSame('project_name', $definition->getRelationships()['project']['local_key']);
        $this->assertContains('project', $definition->getRequiredRelationshipsOnWrite());
        $this->assertSame('numeric', $definition->getAttributeRule('theme_single')['type']);
        $this->assertSame('string', $definition->getAttributeRule('theme_name')['type']);
        $this->assertSame('symbol', $definition->getAttributeRule('symbol_name')['lookup']['table']);
    }

    public function testBuildsScopedEntityDefinitionFromDtoSchema()
    {
        $provider = new DtoEntityDefinitionProvider();

        $definition = $provider->getEntityDefinition('project_srs');

        $this->assertSame(['project_name'], $definition->getScopeFields());
        $this->assertSame(['project_name', 'srid'], $definition->getFilterableFields());
        $this->assertSame(['srid'], $definition->getSortableFields());
        $this->assertSame('project_name', $definition->getRelationships()['project']['local_key']);
    }
}
