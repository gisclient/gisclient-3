<?php

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Definition\TabFieldExtractor;
use GisClient\Author\Api\Definition\TabVisibilityEntityDefinitionProvider;
use GisClient\Author\Api\Model\EntityDefinition;
use PHPUnit\Framework\TestCase;

class TabVisibilityEntityDefinitionProviderTest extends TestCase
{
    public function testAppliesTabVisibilityFilteringAndKeepsPrimaryKey()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Name;project_title;40;text\"\n");

        $base = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title', 'internal_only_field'],
            ['project_title', 'internal_only_field'],
            ['project_name', 'project_title', 'internal_only_field'],
            ['project_title', 'internal_only_field'],
            ['project_name', 'project_title', 'internal_only_field'],
            ['project_title', 'internal_only_field'],
            'internal_only_field',
            [
                'project_title' => [
                    'type' => 'string',
                ],
                'internal_only_field' => [
                    'type' => 'string',
                ],
                'project_name' => [
                    'type' => 'string',
                ],
            ]
        );

        $inner = new class($base) implements EntityDefinitionProviderInterface {
            private $definition;

            public function __construct(EntityDefinition $definition)
            {
                $this->definition = $definition;
            }

            public function getEntityDefinition($entity)
            {
                return $this->definition;
            }
        };

        $provider = new TabVisibilityEntityDefinitionProvider(
            $inner,
            new TabFieldExtractor(),
            [
                'project' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('project');

        $this->assertSame(['project_title', 'project_name'], $definition->getReadableFields());
        $this->assertSame(['project_title'], $definition->getWritableFields());
        $this->assertSame(['project_name', 'project_title'], $definition->getRequiredOnCreate());
        $this->assertSame(['project_title'], $definition->getRequiredOnPut());
        $this->assertSame(['project_name', 'project_title'], $definition->getFilterableFields());
        $this->assertSame(['project_title', 'project_name'], $definition->getSortableFields());
        $this->assertSame('project_name', $definition->getDefaultSort());
        $this->assertNotNull($definition->getAttributeRule('project_title'));
        $this->assertNull($definition->getAttributeRule('internal_only_field'));

        @unlink($tabFile);
    }

    public function testOrdersReadableAndWritableFieldsByTabSequence()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Y;yc;40;text\"\n" .
            "dato[] = \"X;xc;40;text\"\n");

        $base = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'xc', 'yc'],
            ['xc', 'yc'],
            ['project_name', 'xc'],
            ['xc'],
            ['project_name', 'xc', 'yc'],
            ['xc', 'yc'],
            'xc'
        );

        $inner = new class($base) implements EntityDefinitionProviderInterface {
            private $definition;

            public function __construct(EntityDefinition $definition)
            {
                $this->definition = $definition;
            }

            public function getEntityDefinition($entity)
            {
                return $this->definition;
            }
        };

        $provider = new TabVisibilityEntityDefinitionProvider(
            $inner,
            new TabFieldExtractor(),
            [
                'project' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('project');

        $this->assertSame(['yc', 'xc', 'project_name'], $definition->getReadableFields());
        $this->assertSame(['yc', 'xc'], $definition->getWritableFields());

        @unlink($tabFile);
    }
}
