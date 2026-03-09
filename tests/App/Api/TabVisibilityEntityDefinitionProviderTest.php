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

    public function testKeepsScopeAndRelationshipLocalFieldsWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"SRID;srid;40;intero\"\n" .
            "dato[] = \"Param;projparam;40;text\"\n");

        $base = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'projparam', 'project_name'],
            ['srid', 'projparam', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid', 'project_name'],
            ['srid'],
            'srid',
            [
                'project_name' => [
                    'type' => 'string',
                ],
                'srid' => [
                    'type' => 'integer',
                ],
            ],
            ['project_name'],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
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
                'project_srs' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('project_srs');

        $this->assertContains('project_name', $definition->getReadableFields());
        $this->assertContains('project_name', $definition->getWritableFields());
        $this->assertSame(['project_name'], $definition->getScopeFields());
        $this->assertArrayHasKey('project', $definition->getRelationships());

        @unlink($tabFile);
    }

    public function testKeepsRelationshipLocalKeyReadableAndFilterableWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Name;layergroup_name;40;text\"\n" .
            "dato[] = \"Title;layergroup_title;40;text\"\n");

        $base = new EntityDefinition(
            'layergroup',
            'gisclient_34',
            'layergroup',
            'layergroup_id',
            'int',
            ['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title'],
            ['theme_id', 'layergroup_name', 'layergroup_title'],
            ['theme_id', 'layergroup_name', 'layergroup_title'],
            ['layergroup_name', 'layergroup_title'],
            ['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title'],
            ['layergroup_id', 'theme_id', 'layergroup_name'],
            'theme_id',
            [
                'theme_id' => [
                    'type' => 'integer',
                ],
            ],
            [],
            [
                'theme' => [
                    'type' => 'theme',
                    'local_key' => 'theme_id',
                ],
            ],
            ['theme']
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
                'layergroup' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('layergroup');

        $this->assertContains('theme_id', $definition->getReadableFields());
        $this->assertContains('theme_id', $definition->getFilterableFields());
        $this->assertNotContains('theme_id', $definition->getWritableFields());
        $this->assertArrayHasKey('theme', $definition->getRelationships());

        @unlink($tabFile);
    }

    public function testKeepsMultipleRelationshipLocalKeysReadableAndFilterableWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Name;layer_name;40;text\"\n" .
            "dato[] = \"Title;layer_title;40;text\"\n");

        $base = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layer_title'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layer_title'],
            ['layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_name'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layer_title'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            'layer_id',
            [
                'layergroup_id' => [
                    'type' => 'integer',
                ],
                'catalog_id' => [
                    'type' => 'integer',
                ],
            ],
            [],
            [
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
            ],
            ['layergroup', 'catalog']
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
                'layer' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('layer');

        $this->assertContains('layergroup_id', $definition->getReadableFields());
        $this->assertContains('catalog_id', $definition->getReadableFields());
        $this->assertContains('layergroup_id', $definition->getFilterableFields());
        $this->assertContains('catalog_id', $definition->getFilterableFields());
        $this->assertNotContains('layergroup_id', $definition->getWritableFields());
        $this->assertNotContains('catalog_id', $definition->getWritableFields());
        $this->assertArrayHasKey('layergroup', $definition->getRelationships());
        $this->assertArrayHasKey('catalog', $definition->getRelationships());

        @unlink($tabFile);
    }

    public function testKeepsClassLayerRelationshipLocalKeyReadableAndFilterableWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Name;class_name;40;text\"\n" .
            "dato[] = \"Title;class_title;40;text\"\n");

        $base = new EntityDefinition(
            'class',
            'gisclient_34',
            'class',
            'class_id',
            'int',
            ['class_id', 'layer_id', 'class_name', 'class_title'],
            ['layer_id', 'class_name', 'class_title'],
            ['class_name'],
            ['class_name'],
            ['class_id', 'layer_id', 'class_name', 'class_title'],
            ['class_id', 'class_name'],
            'class_id',
            [
                'layer_id' => [
                    'type' => 'integer',
                ],
            ],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
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
                'class' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('class');

        $this->assertContains('layer_id', $definition->getReadableFields());
        $this->assertContains('layer_id', $definition->getFilterableFields());
        $this->assertNotContains('layer_id', $definition->getWritableFields());
        $this->assertArrayHasKey('layer', $definition->getRelationships());

        @unlink($tabFile);
    }

    public function testKeepsStyleClassRelationshipLocalKeyReadableAndFilterableWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Name;style_name;40;text\"\n" .
            "dato[] = \"Order;style_order;40;intero\"\n");

        $base = new EntityDefinition(
            'style',
            'gisclient_34',
            'style',
            'style_id',
            'int',
            ['style_id', 'class_id', 'style_name', 'style_order'],
            ['class_id', 'style_name', 'style_order'],
            ['class_id', 'style_name'],
            ['class_id', 'style_name'],
            ['style_id', 'class_id', 'style_name'],
            ['style_id', 'style_order', 'style_name'],
            'style_order',
            [
                'class_id' => [
                    'type' => 'integer',
                ],
            ],
            [],
            [
                'class' => [
                    'type' => 'class',
                    'local_key' => 'class_id',
                ],
            ],
            ['class']
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
                'style' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('style');

        $this->assertContains('class_id', $definition->getReadableFields());
        $this->assertContains('class_id', $definition->getFilterableFields());
        $this->assertNotContains('class_id', $definition->getWritableFields());
        $this->assertArrayHasKey('class', $definition->getRelationships());

        @unlink($tabFile);
    }

    public function testKeepsFieldLayerRelationshipLocalKeyReadableAndFilterableWhenNotInTab()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "dato[] = \"Field;field_name;40;text\"\n" .
            "dato[] = \"Header;field_header;40;text\"\n");

        $base = new EntityDefinition(
            'field',
            'gisclient_34',
            'field',
            'field_id',
            'int',
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_id', 'field_name'],
            'field_id',
            [
                'layer_id' => [
                    'type' => 'integer',
                ],
                'relation_id' => [
                    'type' => 'integer',
                ],
            ],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
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
                'field' => [
                    'tab_file' => $tabFile,
                ],
            ]
        );

        $definition = $provider->getEntityDefinition('field');

        $this->assertContains('layer_id', $definition->getReadableFields());
        $this->assertContains('layer_id', $definition->getFilterableFields());
        $this->assertNotContains('layer_id', $definition->getWritableFields());
        $this->assertNotContains('relation_id', $definition->getWritableFields());
        $this->assertArrayHasKey('layer', $definition->getRelationships());
        $this->assertArrayNotHasKey('relation', $definition->getRelationships());

        @unlink($tabFile);
    }
}
