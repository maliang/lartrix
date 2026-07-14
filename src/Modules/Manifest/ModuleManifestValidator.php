<?php

namespace Lartrix\Modules\Manifest;

/** 校验 Nwidart 根清单以及嵌套的 Trix 生态清单。 */
final class ModuleManifestValidator
{
    public const SCHEMA_VERSION = 'trix.module.v1';

    /** @var array<int, string> */
    private const MODULE_TYPES = ['pure_schema', 'contract', 'native'];

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateNwidart(array $data): array
    {
        $errors = [];

        foreach (['name', 'alias', 'description'] as $field) {
            self::validateRequiredString($data, $field, $errors);
        }

        if (!array_key_exists('priority', $data) || !is_int($data['priority'])) {
            $errors['priority'] = 'priority must be an integer.';
        }

        self::validateStringList($data, 'keywords', $errors);
        self::validateStringList($data, 'providers', $errors);
        self::validateStringList($data, 'files', $errors);

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validate(array $data): array
    {
        $errors = [];

        foreach (['schema_version', 'id', 'name', 'version', 'type'] as $field) {
            self::validateRequiredString($data, $field, $errors);
        }

        if (isset($data['schema_version']) && $data['schema_version'] !== self::SCHEMA_VERSION) {
            $errors['schema_version'] = 'schema_version must be trix.module.v1.';
        }

        if (isset($data['type']) && !in_array($data['type'], self::MODULE_TYPES, true)) {
            $errors['type'] = 'type must be pure_schema, contract, or native.';
        }

        foreach (['logo', 'thumbnail', 'author', 'author_url'] as $field) {
            if (array_key_exists($field, $data) && !is_string($data[$field])) {
                $errors[$field] = "$field must be a string.";
            }
        }

        self::validateAdapter($data, $errors);
        self::validateListEntries($data, 'menus', ['key', 'title', 'path'], $errors);
        self::validateListEntries($data, 'permissions', ['name', 'title'], $errors);
        self::validateListEntries($data, 'schemas', ['key', 'title', 'path'], $errors);

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateForAdapter(array $data, string $language, string $framework): array
    {
        $errors = self::validate($data);
        $adapter = $data['adapter'] ?? null;

        if (!is_array($adapter)) {
            return $errors;
        }

        if (($adapter['language'] ?? null) !== $language || ($adapter['framework'] ?? null) !== $framework) {
            $errors['adapter.framework'] = "adapter $language/$framework is not declared.";
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateRequiredString(array $data, string $key, array &$errors): void
    {
        if (!array_key_exists($key, $data) || !is_string($data[$key]) || trim($data[$key]) === '') {
            $errors[$key] = "$key is required.";
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateAdapter(array $data, array &$errors): void
    {
        $adapter = $data['adapter'] ?? null;

        if (!is_array($adapter)) {
            $errors['adapter'] = 'adapter is required.';
            return;
        }

        foreach (['language', 'framework', 'package_type'] as $field) {
            if (!array_key_exists($field, $adapter) || !is_string($adapter[$field]) || trim($adapter[$field]) === '') {
                $errors["adapter.$field"] = "$field is required.";
            }
        }

        foreach (['language_version', 'framework_version'] as $field) {
            if (array_key_exists($field, $adapter) && !is_string($adapter[$field])) {
                $errors["adapter.$field"] = "$field must be a string.";
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateStringList(array $data, string $key, array &$errors): void
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key]) || !array_is_list($data[$key])) {
            $errors[$key] = "$key must be an array.";
            return;
        }

        foreach ($data[$key] as $index => $value) {
            if (!is_string($value) || trim($value) === '') {
                $errors["$key.$index"] = "$key entry must be a non-empty string.";
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $requiredFields
     * @param array<string, string> $errors
     */
    private static function validateListEntries(array $data, string $listKey, array $requiredFields, array &$errors): void
    {
        if (!array_key_exists($listKey, $data)) {
            return;
        }

        if (!is_array($data[$listKey]) || !array_is_list($data[$listKey])) {
            $errors[$listKey] = "$listKey must be an array.";
            return;
        }

        foreach ($data[$listKey] as $index => $entry) {
            if (!is_array($entry)) {
                $errors["$listKey.$index"] = "$listKey entry must be an object.";
                continue;
            }

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $entry) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors["$listKey.$index.$field"] = "$field is required.";
                }
            }
        }
    }
}
