<?php

namespace Lartrix\Modules\Project;

/** 统一校验 Trix 项目清单。 */
final class ProjectManifestValidator
{
    public const SCHEMA_VERSION = 'trix.project.v1';

    /** 返回按字段索引的项目清单错误。 */
    public static function validate(array $data): array
    {
        $errors = [];
        foreach (['schema_version', 'id', 'name', 'version', 'author'] as $field) {
            if (!is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                $errors[$field] = "{$field} is required.";
            }
        }
        if (isset($data['schema_version']) && $data['schema_version'] !== self::SCHEMA_VERSION) {
            $errors['schema_version'] = 'schema_version must be trix.project.v1.';
        }
        if (isset($data['modules']) && !is_array($data['modules'])) {
            $errors['modules'] = 'modules must be an array.';
        }
        foreach (['config', 'contract_bindings', 'setup'] as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                $errors[$field] = "{$field} must be an object or array.";
            }
        }

        return $errors;
    }
}
