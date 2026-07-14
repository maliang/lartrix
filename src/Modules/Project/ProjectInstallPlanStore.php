<?php

namespace Lartrix\Modules\Project;

use RuntimeException;

/** 将项目安装计划应用为 Laravel 唯一运行时配置。 */
final class ProjectInstallPlanStore
{
    /** 初始化项目配置存储。 */
    public function __construct(private readonly ?string $configPath = null)
    {
    }

    /** 原子写入 config/trix-project.php，并返回目标路径。 */
    public function apply(array $plan): string
    {
        $path = $this->path();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("无法创建项目配置目录：{$directory}");
        }

        $config = $this->normalize($plan);
        $contents = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException("无法写入项目临时配置：{$temporary}");
        }
        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException("无法应用项目配置：{$path}");
        }

        // 同步当前 Laravel 进程，模块可立即通过 config('trix-project.*') 读取覆盖配置。
        if (function_exists('config')
            && \Illuminate\Container\Container::getInstance()->bound('config')) {
            config(['trix-project' => $config]);
        }

        return $path;
    }

    /** 读取当前项目运行时配置。 */
    public function read(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    /** 返回唯一项目配置路径。 */
    public function path(): string
    {
        return $this->configPath ?? (function_exists('config_path')
            ? config_path('trix-project.php')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trix-project.php');
    }

    /** 将 Registry 安装计划投影为稳定的 Laravel 配置结构。 */
    private function normalize(array $plan): array
    {
        $modules = [];
        foreach ($plan['modules'] ?? [] as $module) {
            if (!is_array($module) || !is_string($module['id'] ?? null) || trim($module['id']) === '') {
                continue;
            }
            $modules[$module['id']] = [
                'version' => (string) ($module['selected_version'] ?? $module['version'] ?? ''),
                'required' => (bool) ($module['required'] ?? true),
                'config' => is_array($module['config'] ?? null) ? $module['config'] : [],
            ];
        }

        return [
            'schema_version' => 'trix.project.v1',
            'id' => (string) ($plan['project'] ?? $plan['id'] ?? ''),
            'version' => (string) ($plan['version'] ?? ''),
            'project_config' => is_array($plan['project_config'] ?? null) ? $plan['project_config'] : [],
            'modules' => $modules,
            'contract_bindings' => is_array($plan['contract_bindings'] ?? null) ? $plan['contract_bindings'] : [],
            'setup' => is_array($plan['setup'] ?? null) ? $plan['setup'] : [],
            'installed_at' => function_exists('now') ? now()->toIso8601String() : date(DATE_ATOM),
        ];
    }
}
