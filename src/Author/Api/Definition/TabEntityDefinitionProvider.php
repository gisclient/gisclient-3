<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;

class TabEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    /**
     * @var array
     */
    private $registry;

    public function __construct(?array $registry = null)
    {
        $this->registry = $registry ?: EntityRegistry::getDefinitions();
    }
    public function getEntityDefinition($entity)
    {
        if (!isset($this->registry[$entity])) {
            throw new ApiException(404, 'unknown_entity', 'Unknown Entity', sprintf("Entity '%s' is not managed by this API", $entity));
        }

        $config = $this->registry[$entity];
        $tabFields = $this->extractTabFields($config['tab_file']);

        $writableFields = array_merge($tabFields, $config['writable_extra'] ?? []);
        $readableFields = array_merge($tabFields, $config['readable_extra'] ?? []);

        $primaryKey = $config['primary_key'];
        if (!in_array($primaryKey, $readableFields, true)) {
            $readableFields[] = $primaryKey;
        }

        return new EntityDefinition(
            $entity,
            $config['schema'],
            $config['table'],
            $primaryKey,
            $config['id_type'] ?? 'string',
            $readableFields,
            $writableFields,
            $config['required_on_create'] ?? [],
            $config['required_on_put'] ?? [],
            $config['filterable_fields'] ?? [],
            $config['sortable_fields'] ?? [],
            $config['default_sort'] ?? $primaryKey
        );
    }

    /**
     * @param string $file
     * @return array
     */
    private function extractTabFields($file)
    {
        if (!is_file($file)) {
            throw new ApiException(500, 'invalid_tab_definition', 'Invalid Definition', sprintf("Tab definition file '%s' not found", $file));
        }

        $tab = parse_ini_file($file, true);
        if ($tab === false || !isset($tab['standard'])) {
            throw new ApiException(500, 'invalid_tab_definition', 'Invalid Definition', sprintf("Could not parse tab definition file '%s'", $file));
        }

        $rawRows = $tab['standard']['dato'] ?? [];
        if (!is_array($rawRows)) {
            $rawRows = [$rawRows];
        }

        $fields = [];
        foreach ($rawRows as $row) {
            $chunks = explode('|', $row);
            foreach ($chunks as $chunk) {
                $parts = array_pad(explode(';', $chunk), 4, '');
                $field = trim($parts[1]);
                $type = trim($parts[3]);

                if ($field === '' || in_array($type, ['button', 'submit'], true)) {
                    continue;
                }
                $fields[] = $field;
            }
        }

        return array_values(array_unique($fields));
    }
}
