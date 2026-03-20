<?php

namespace GisClient\Author\Api\ProjectCopy;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;

class ProjectCopyRequestParser
{
    public const MAPSET_NAMING_REPLACE_PROJECT_NAME = 'replace_project_name';
    public const MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT = 'prefix_with_target_project';

    /**
     * @return array<int,string>
     */
    public static function mapsetNamingModes(): array
    {
        return [
            self::MAPSET_NAMING_REPLACE_PROJECT_NAME,
            self::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT,
        ];
    }

    public function parse(string $content): ProjectCopyRequest
    {
        if (trim($content) === '') {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        $payload = json_decode($content, true);
        if (!is_array($payload)) {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        $errors = [];

        $sourceProject = $this->readRequiredString($payload, 'source_project', $errors);
        $targetProject = $this->readRequiredString($payload, 'target_project', $errors);

        $mapsetNamingMode = $payload['mapset_naming_mode'] ?? self::MAPSET_NAMING_REPLACE_PROJECT_NAME;
        if (!is_string($mapsetNamingMode) || trim($mapsetNamingMode) === '') {
            $errors[] = $this->error(
                'invalid_attribute_type',
                'Invalid Attribute Type',
                "Field 'mapset_naming_mode' must be a non-empty string",
                '/mapset_naming_mode'
            );
        } elseif (!in_array($mapsetNamingMode, self::mapsetNamingModes(), true)) {
            $errors[] = $this->error(
                'invalid_mapset_naming_mode',
                'Invalid Mapset Naming Mode',
                sprintf(
                    "Field 'mapset_naming_mode' must be one of: %s",
                    implode(', ', self::mapsetNamingModes())
                ),
                '/mapset_naming_mode'
            );
        }

        $refresh = $payload['refresh'] ?? [];
        if (!is_array($refresh)) {
            $errors[] = $this->error(
                'invalid_attribute_type',
                'Invalid Attribute Type',
                "Field 'refresh' must be an object",
                '/refresh'
            );
            $refresh = [];
        }

        $refreshPrivate = $this->readBool($refresh, 'private_mapfiles', false, $errors, '/refresh/private_mapfiles');
        $refreshPublic = $this->readBool($refresh, 'public_mapfiles', false, $errors, '/refresh/public_mapfiles');

        if ($errors !== []) {
            throw new ValidationException($errors, 400);
        }

        return new ProjectCopyRequest(
            $sourceProject,
            $targetProject,
            $mapsetNamingMode,
            $refreshPrivate,
            $refreshPublic
        );
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<int,array<string,mixed>> $errors
     */
    private function readRequiredString(array $payload, string $field, array &$errors): string
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = $this->error(
                'missing_required_attribute',
                'Missing Required Attribute',
                sprintf("Field '%s' is required", $field),
                '/' . $field
            );
            return '';
        }

        if (!is_string($payload[$field])) {
            $errors[] = $this->error(
                'invalid_attribute_type',
                'Invalid Attribute Type',
                sprintf("Field '%s' must be a string", $field),
                '/' . $field
            );
            return '';
        }

        $value = trim($payload[$field]);
        if ($value === '') {
            $errors[] = $this->error(
                'missing_required_attribute',
                'Missing Required Attribute',
                sprintf("Field '%s' must not be empty", $field),
                '/' . $field
            );
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<int,array<string,mixed>> $errors
     */
    private function readBool(array $payload, string $field, bool $default, array &$errors, string $pointer): bool
    {
        if (!array_key_exists($field, $payload)) {
            return $default;
        }

        if (!is_bool($payload[$field])) {
            $errors[] = $this->error(
                'invalid_attribute_type',
                'Invalid Attribute Type',
                sprintf("Field '%s' must be a boolean", $field),
                $pointer
            );
            return $default;
        }

        return $payload[$field];
    }

    /**
     * @return array<string,mixed>
     */
    private function error(string $code, string $title, string $detail, string $pointer): array
    {
        return [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'source' => [
                'pointer' => $pointer,
            ],
        ];
    }
}
