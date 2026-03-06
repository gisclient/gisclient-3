<?php

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Definition\CachedEntityDefinitionProvider;
use GisClient\Author\Api\Model\EntityDefinition;
use PHPUnit\Framework\TestCase;

class CachedEntityDefinitionProviderTest extends TestCase
{
    public function testCachesEntityDefinitionByEntityName()
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name'],
            ['project_name'],
            ['project_name'],
            [],
            ['project_name'],
            ['project_name'],
            'project_name'
        );

        $provider = new class($definition) implements EntityDefinitionProviderInterface {
            public $calls = 0;
            private $definition;

            public function __construct(EntityDefinition $definition)
            {
                $this->definition = $definition;
            }

            public function getEntityDefinition($entity)
            {
                $this->calls++;
                return $this->definition;
            }
        };

        $cached = new CachedEntityDefinitionProvider($provider);
        $cached->getEntityDefinition('project');
        $cached->getEntityDefinition('project');

        $this->assertSame(1, $provider->calls);
    }
}
