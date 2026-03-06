<?php

use GisClient\Author\Api\Definition\TabEntityDefinitionProvider;
use GisClient\Author\Api\Exception\ApiException;
use PHPUnit\Framework\TestCase;

class TabEntityDefinitionProviderTest extends TestCase
{
    public function testBuildsDefinitionFromTabFile()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[standard]\n" .
            "table = \"sample\"\n" .
            "pkey = \"sample_id\"\n" .
            "dato[] = \"Name;sample_name;40;text\"\n" .
            "dato[] = \"Save;action;;submit\"\n");

        $provider = new TabEntityDefinitionProvider([
            'sample' => [
                'schema' => 'public',
                'table' => 'sample',
                'primary_key' => 'sample_id',
                'id_type' => 'int',
                'tab_file' => $tabFile,
                'required_on_create' => ['sample_name'],
                'required_on_put' => ['sample_name'],
                'filterable_fields' => ['sample_name'],
                'sortable_fields' => ['sample_name'],
                'default_sort' => 'sample_name',
            ],
        ]);

        $definition = $provider->getEntityDefinition('sample');

        $this->assertSame('sample', $definition->getType());
        $this->assertSame('sample', $definition->getTable());
        $this->assertSame('sample_id', $definition->getPrimaryKey());
        $this->assertContains('sample_name', $definition->getWritableFields());
        $this->assertNotContains('action', $definition->getWritableFields());

        @unlink($tabFile);
    }

    public function testThrowsOnUnknownEntity()
    {
        $provider = new TabEntityDefinitionProvider([
            'sample' => [
                'schema' => 'public',
                'table' => 'sample',
                'primary_key' => 'sample_id',
                'id_type' => 'int',
                'tab_file' => __FILE__,
                'required_on_create' => [],
                'required_on_put' => [],
                'filterable_fields' => [],
                'sortable_fields' => [],
                'default_sort' => 'sample_id',
            ],
        ]);

        try {
            $provider->getEntityDefinition('missing');
            $this->fail('Expected ApiException for unknown entity');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->getStatus());
            $this->assertSame('unknown_entity', $exception->getErrorCode());
        }
    }

    public function testThrowsOnMissingTabFile()
    {
        $provider = new TabEntityDefinitionProvider([
            'sample' => [
                'schema' => 'public',
                'table' => 'sample',
                'primary_key' => 'sample_id',
                'id_type' => 'int',
                'tab_file' => '/tmp/does-not-exist-tab-file.tab',
                'required_on_create' => [],
                'required_on_put' => [],
                'filterable_fields' => [],
                'sortable_fields' => [],
                'default_sort' => 'sample_id',
            ],
        ]);

        try {
            $provider->getEntityDefinition('sample');
            $this->fail('Expected ApiException for missing tab file');
        } catch (ApiException $exception) {
            $this->assertSame(500, $exception->getStatus());
            $this->assertSame('invalid_tab_definition', $exception->getErrorCode());
        }
    }

    public function testThrowsOnInvalidTabStructure()
    {
        $tabFile = tempnam(sys_get_temp_dir(), 'tab');
        file_put_contents($tabFile, "[wrong]\nfoo = \"bar\"\n");

        $provider = new TabEntityDefinitionProvider([
            'sample' => [
                'schema' => 'public',
                'table' => 'sample',
                'primary_key' => 'sample_id',
                'id_type' => 'int',
                'tab_file' => $tabFile,
                'required_on_create' => [],
                'required_on_put' => [],
                'filterable_fields' => [],
                'sortable_fields' => [],
                'default_sort' => 'sample_id',
            ],
        ]);

        try {
            $provider->getEntityDefinition('sample');
            $this->fail('Expected ApiException for invalid tab structure');
        } catch (ApiException $exception) {
            $this->assertSame(500, $exception->getStatus());
            $this->assertSame('invalid_tab_definition', $exception->getErrorCode());
        }

        @unlink($tabFile);
    }
}
