<?php

namespace Lartrix\Modules\Manifest;

use JsonException;
use InvalidArgumentException;

/** 读取并归一化新旧模块清单，统一交给当前框架的校验与注册流程。 */
final class ModuleManifestLoader
{
        /** 加载当前流程需要的数据。 */
    public function loadFromPath(string $path): ?ModuleManifest
    {
        $moduleManifest = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'module.json';

        if (is_file($moduleManifest)) {
            $data = $this->readJsonFile($moduleManifest);
            if (($data['schema_version'] ?? null) === ModuleManifestValidator::SCHEMA_VERSION) {
                return ModuleManifest::fromArray($data);
            }

            // 老项目原生 module.json 仍可被读取，但会归一化为当前框架 adapter。
            return ModuleManifest::fromArray($this->normalizeLegacyManifest($data));
        }

        return null;
    }

    /**
     * 从指定来源读取数据。
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("Invalid JSON manifest: {$path}", previous: $e);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 将输入值归一化为内部标准格式。
     * @param array<string, mixed> $legacy
     * @return array<string, mixed>
     */
    private function normalizeLegacyManifest(array $legacy): array
    {
        $name = $this->stringValue($legacy, 'name', 'module');
        $title = $this->stringValue($legacy, 'title', $name);

        // Lartrix 侧旧格式默认归一化到 php/laravel，Registry 支持矩阵不写入包内。
        return [
            'schema_version' => ModuleManifestValidator::SCHEMA_VERSION,
            'id' => 'legacy.' . $this->slug($name),
            'name' => $title,
            'version' => $this->stringValue($legacy, 'version', '1.0.0'),
            'type' => 'native',
            'description' => $this->stringValue($legacy, 'description', ''),
            'adapter' => [
                'language' => 'php',
                'framework' => 'laravel',
                'status' => 'compatible',
                'package_type' => 'nwidart',
            ],
            'menus' => $this->normalizeLegacyMenus($legacy['menus'] ?? []),
            'permissions' => $this->normalizeLegacyPermissions($legacy['permissions'] ?? []),
            'security' => [
                'writes_files' => true,
                'runs_commands' => false,
                'external_network' => false,
            ],
            'legacy' => $legacy,
        ];
    }

    /**
     * 将输入值归一化为内部标准格式。
     * @param mixed $menus
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLegacyMenus(mixed $menus): array
    {
        if (!is_array($menus)) {
            return [];
        }

        return array_values(array_filter(array_map(static function (mixed $menu): ?array {
            if (!is_array($menu)) {
                return null;
            }

            if (isset($menu['key'])) {
                return $menu;
            }

            if (isset($menu['name'])) {
                // 旧 Lartrix 菜单使用 name，新协议统一使用 key。
                $menu['key'] = $menu['name'];
            }

            return $menu;
        }, $menus)));
    }

    /**
     * 将输入值归一化为内部标准格式。
     * @param mixed $permissions
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLegacyPermissions(mixed $permissions): array
    {
        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_filter($permissions, static fn (mixed $permission): bool => is_array($permission)));
    }

    /**
     * 执行 stringValue 方法对应的具体职责。
     * @param array<string, mixed> $data
     */
    private function stringValue(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : $default;
    }

    /** 将名称转换为可用于模块标识的短横线格式。 */
    private function slug(string $value): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'module';
    }
}
