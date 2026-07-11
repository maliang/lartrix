<?php

namespace Lartrix\Modules\Manifest;

/** 校验 Trix 清单结构、版本要求及当前框架适配器是否可安装。 */
final class ModuleManifestValidator
{
    public const SCHEMA_VERSION = 'trix.module.v1';

    // type 描述模块的产品形态，不直接决定是否可安装。
    /** @var array<int, string> */
    private const MODULE_TYPES = ['pure_schema', 'contract', 'native'];

    // 安装器只允许这些 adapter 状态落地，planned/unsupported 只用于市场展示。
    /** @var array<int, string> */
    private const INSTALLABLE_STATUSES = ['stable', 'compatible', 'experimental'];

    /** @var array<int, string> */
    private const ADAPTER_STATUSES = ['stable', 'compatible', 'experimental', 'planned', 'unsupported'];

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validate(array $data): array
    {
        $errors = [];

        self::validateRequiredString($data, 'schema_version', $errors);
        self::validateRequiredString($data, 'id', $errors);
        self::validateRequiredString($data, 'name', $errors);
        self::validateRequiredString($data, 'version', $errors);
        self::validateRequiredString($data, 'type', $errors);

        if (($data['schema_version'] ?? null) !== null && ($data['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            $errors['schema_version'] = 'schema_version must be trix.module.v1.';
        }

        if (($data['type'] ?? null) !== null && !in_array($data['type'], self::MODULE_TYPES, true)) {
            $errors['type'] = 'type must be pure_schema, contract, or native.';
        }

        foreach (['logo', 'thumbnail', 'author', 'author_url'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                $errors[$field] = "$field must be a string.";
            }
        }

        self::validateAdapter($data, $errors);
        self::validateMenus($data, $errors);
        self::validatePermissions($data, $errors);
        self::validateSchemas($data, $errors);

        return $errors;
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateForAdapter(array $data, string $language, string $framework): array
    {
        $errors = self::validate($data);
        $adapter = $data['adapter'] ?? null;

        if (!is_array($adapter)) {
            $errors['adapter'] = 'adapter is required.';

            return $errors;
        }

        if (($adapter['language'] ?? null) !== $language || ($adapter['framework'] ?? null) !== $framework) {
            $errors['adapter.framework'] = "adapter $language/$framework is not declared.";
        }

        // 即使 language/framework 匹配，也必须确认状态允许在当前框架安装。
        $status = $adapter['status'] ?? null;
        if (!is_string($status) || !in_array($status, self::INSTALLABLE_STATUSES, true)) {
            $errors['adapter.status'] = "adapter $language/$framework is not installable.";
        }

        return $errors;
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateRequiredString(array $data, string $key, array &$errors): void
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') {
            $errors[$key] = "$key is required.";
        }
    }

    /**
     * 校验输入数据是否满足当前约束。
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

        foreach (['language', 'framework', 'status'] as $field) {
            if (!isset($adapter[$field]) || !is_string($adapter[$field]) || trim($adapter[$field]) === '') {
                $errors["adapter.$field"] = "$field is required.";
            }
        }

        $status = $adapter['status'] ?? null;
        if (is_string($status) && !in_array($status, self::ADAPTER_STATUSES, true)) {
            $errors['adapter.status'] = 'adapter status is invalid.';
        }

        foreach (['language_version', 'framework_version'] as $field) {
            if (isset($adapter[$field]) && !is_string($adapter[$field])) {
                $errors["adapter.$field"] = "$field must be a string.";
            }
        }
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateMenus(array $data, array &$errors): void
    {
        self::validateListEntries($data, 'menus', ['key', 'title', 'path'], $errors);
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validatePermissions(array $data, array &$errors): void
    {
        self::validateListEntries($data, 'permissions', ['name', 'title'], $errors);
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateSchemas(array $data, array &$errors): void
    {
        self::validateListEntries($data, 'schemas', ['key', 'title', 'path'], $errors);
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $data
     * @param array<int, string> $requiredFields
     * @param array<string, string> $errors
     */
    private static function validateListEntries(array $data, string $listKey, array $requiredFields, array &$errors): void
    {
        if (!array_key_exists($listKey, $data)) {
            return;
        }

        if (!is_array($data[$listKey])) {
            $errors[$listKey] = "$listKey must be an array.";

            return;
        }

        foreach ($data[$listKey] as $index => $entry) {
            if (!is_array($entry)) {
                $errors["$listKey.$index"] = "$listKey entry must be an object.";
                continue;
            }

            foreach ($requiredFields as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors["$listKey.$index.$field"] = "$field is required.";
                }
            }
        }
    }
}
