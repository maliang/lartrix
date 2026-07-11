<?php

namespace Lartrix\Modules\Registry;

/** 根据模块清单生成安装后待办，提示迁移、配置发布等不会自动执行的动作。 */
class RegistryInstalledPackageChecklist
{
    /**
     * 构建当前流程使用的数据结构。
     * @return array<string, mixed>
     */
    public function build(string $modulePath, string $moduleId): array
    {
        $files = $this->relativeFiles($modulePath);
        $composerFiles = $this->matchingFiles($files, static fn (string $file): bool => $file === 'composer.json');
        $providerFiles = $this->matchingFiles($files, static fn (string $file): bool => str_ends_with($file, 'ServiceProvider.php'));
        $migrationFiles = $this->matchingFiles($files, static fn (string $file): bool => str_contains($file, 'database/migrations/') && str_ends_with($file, '.php'));
        $seederFiles = $this->matchingFiles($files, static fn (string $file): bool => str_contains($file, 'database/seeders/') && str_ends_with($file, '.php'));

        $moduleName = $this->studlyModuleName($moduleId);
        $todos = ['Review copied module files before enabling the module.'];
        $commands = [];

        if ($composerFiles !== []) {
            $todos[] = 'Review composer.json and merge provider/autoload settings if needed.';
            $commands[] = 'Run composer dump-autoload after reviewing composer.json.';
        }

        if ($providerFiles !== []) {
            $todos[] = 'Review Laravel service providers before enabling the module.';
        }

        if ($migrationFiles !== []) {
            $todos[] = 'Review database migrations before running them.';
            $commands[] = "Run migrations manually after review, for example: php artisan module:migrate {$moduleName}";
        }

        if ($seederFiles !== []) {
            $todos[] = 'Review database seeders before running them.';
            $commands[] = "Run seeders manually after review, for example: php artisan module:seed {$moduleName}";
        }

        return [
            'module_id' => $moduleId,
            'module_path' => $modulePath,
            'has_composer' => $composerFiles !== [],
            'provider_count' => count($providerFiles),
            'migration_count' => count($migrationFiles),
            'seeder_count' => count($seederFiles),
            'todos' => $todos,
            'commands' => $commands,
        ];
    }

    /**
     * 执行 relativeFiles 方法对应的具体职责。
     * @return array<int, string>
     */
    private function relativeFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $this->collectFiles($root, $root, $files);
        sort($files);

        return $files;
    }

    /**
     * 执行 collectFiles 方法对应的具体职责。
     * @param array<int, string> $files
     */
    private function collectFiles(string $root, string $path, array &$files): void
    {
        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->collectFiles($root, $fullPath, $files);
                continue;
            }

            $files[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($fullPath, strlen($root) + 1));
        }
    }

    /**
     * 执行 matchingFiles 方法对应的具体职责。
     * @param array<int, string> $files
     * @param callable(string): bool $predicate
     * @return array<int, string>
     */
    private function matchingFiles(array $files, callable $predicate): array
    {
        return array_values(array_filter($files, $predicate));
    }

    /** 将模块标识转换为 StudlyCase 模块名称。 */
    private function studlyModuleName(string $moduleId): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $moduleId) ?: [];

        return implode('', array_map(static fn (string $part): string => ucfirst($part), array_filter($parts)));
    }
}
