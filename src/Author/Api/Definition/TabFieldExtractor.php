<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Exception\ApiException;

class TabFieldExtractor
{
    /**
     * @param string $file
     * @return string[]
     */
    public function extractFields($file)
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
