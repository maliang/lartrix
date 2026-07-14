<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Lartrix\Modules\Project\ProjectManifestValidator;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/** 根据当前应用中的模块与配置生成可编辑的 Trix 项目清单。 */
class ProjectMakeCommand extends Command
{
    protected $signature = 'lartrix:project-make
                            {--sync : Sync installed modules into an existing trix-project.json}
                            {--force : Overwrite an existing trix-project.json}
                            {--id= : Project registry id}
                            {--name= : Project display name}
                            {--version=1.0.0 : Project version}
                            {--type=starter : Project type}
                            {--description= : Project description}
                            {--author= : Project author, usually the Auth Key user name or email}
                            {--author-url= : Project author URL}';

    protected $description = 'Create or sync the root trix-project.json manifest';

    /** 处理命令或请求的主流程。 */
    public function handle(): int
    {
        $path = base_path('trix-project.json');
        $exists = File::exists($path);

        if ($exists && !$this->option('sync') && !$this->option('force')) {
            $this->error('trix-project.json already exists. Use --sync to merge modules or --force to overwrite.');

            return self::FAILURE;
        }

        $existing = $exists ? $this->readManifest($path) : [];
        if ($exists && $existing === null) {
            $this->error('trix-project.json is not valid JSON.');

            return self::FAILURE;
        }

        $manifest = $this->option('sync')
            ? $this->syncManifest($existing ?: [])
            : $this->newManifest($existing ?: []);

        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $this->info(($exists ? 'Updated' : 'Created') . ' trix-project.json');
        $this->line($path);

        return self::SUCCESS;
    }

    /**
     * 从指定来源读取数据。
     * @return array<string, mixed>|null
     */
    private function readManifest(string $path): ?array
    {
        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * 执行 newManifest 方法对应的具体职责。
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function newManifest(array $existing): array
    {
        return [
            'schema_version' => ProjectManifestValidator::SCHEMA_VERSION,
            'id' => $this->value('id', $existing['id'] ?? 'local.project'),
            'name' => $this->value('name', $existing['name'] ?? 'Local Project'),
            'version' => $this->value('version', $existing['version'] ?? '1.0.0'),
            'type' => $this->value('type', $existing['type'] ?? 'starter'),
            'description' => $this->value('description', $existing['description'] ?? ''),
            'logo' => $existing['logo'] ?? '',
            'thumbnail' => $existing['thumbnail'] ?? '',
            'author' => $this->value('author', $existing['author'] ?? ''),
            'author_url' => $this->value('author-url', $existing['author_url'] ?? ''),
            'license' => $existing['license'] ?? 'MIT',
            'adapter' => [
                'language' => 'php',
                'language_version' => '^8.2',
                'framework' => 'laravel',
                'framework_version' => '^12.0',
            ],
            'modules' => $this->mergeModules($existing['modules'] ?? []),
            'bindings' => $existing['bindings'] ?? [],
            'contract_bindings' => $existing['contract_bindings'] ?? [],
            'config' => $existing['config'] ?? [],
            'setup' => $existing['setup'] ?? ['seeders' => [], 'commands' => []],
        ];
    }

    /**
     * 同步本地模块信息与持久化状态。
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function syncManifest(array $existing): array
    {
        $manifest = $this->newManifest($existing);
        $manifest['modules'] = $this->mergeModules($existing['modules'] ?? []);

        return $manifest;
    }

    /** 读取命令选项值，并在为空时使用回退值。 */
    private function value(string $option, mixed $fallback): string
    {
        $value = $this->option($option);

        return is_string($value) && trim($value) !== '' ? trim($value) : (string) $fallback;
    }

    /**
     * 合并模块提供的配置和扩展。
     * @param mixed $existingModules
     * @return array<int, array<string, mixed>>
     */
    private function mergeModules(mixed $existingModules): array
    {
        $byId = [];
        if (is_array($existingModules)) {
            foreach ($existingModules as $module) {
                if (is_array($module) && is_string($module['id'] ?? null) && trim($module['id']) !== '') {
                    $byId[$module['id']] = $module;
                }
            }
        }

        foreach (ModuleFacade::all() as $name => $module) {
            $manifest = $this->moduleManifest($module);
            if (!$manifest) {
                continue;
            }
            $id = $manifest->id();

            $version = $manifest->version() ?? '1.0.0';

            $byId[$id] = array_merge([
                'id' => $id,
                'version_constraint' => $this->caretConstraint($version),
                'required' => true,
                'config' => [],
            ], $byId[$id] ?? []);
        }

        return array_values($byId);
    }

    /**
     * 执行 moduleManifest 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function moduleManifest(object $module): ?ModuleManifest
    {
        if (!method_exists($module, 'getPath')) {
            return null;
        }

        try {
            return (new ModuleManifestLoader())->loadFromPath($module->getPath());
        } catch (\InvalidArgumentException $e) {
            $this->warn($e->getMessage());

            return null;
        }
    }

    /** 将具体版本号转换为兼容的插入符版本约束。 */
    private function caretConstraint(string $version): string
    {
        $parts = explode('.', $version);

        return '^' . ($parts[0] ?? '1') . '.' . ($parts[1] ?? '0');
    }
}
