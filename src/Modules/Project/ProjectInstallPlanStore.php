<?php

namespace Lartrix\Modules\Project;

/** 负责持久化项目安装计划、项目覆盖配置与契约绑定，供安装器和运行时分别读取。 */
class ProjectInstallPlanStore
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly ?string $rootPath = null)
    {
    }

    /**
     * 保存当前业务数据。
     * @param array<string, mixed> $plan
     * @return array<string, string>
     */
    public function save(string $projectId, string $version, array $plan): array
    {
        $directory = $this->directory($projectId, $version);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $paths = [
            'directory' => $directory,
            'install_plan' => $this->writeJson($directory . DIRECTORY_SEPARATOR . 'install-plan.json', $plan),
            'project_config' => $this->writeJson($directory . DIRECTORY_SEPARATOR . 'project-config.json', $this->arrayValue($plan['project_config'] ?? [])),
            'contract_bindings' => $this->writeJson($directory . DIRECTORY_SEPARATOR . 'contract-bindings.json', $this->arrayValue($plan['contract_bindings'] ?? [])),
            'setup' => $this->writeJson($directory . DIRECTORY_SEPARATOR . 'setup.json', $this->arrayValue($plan['setup'] ?? [])),
        ];

        foreach (($plan['modules'] ?? []) as $module) {
            if (!is_array($module) || !is_string($module['id'] ?? null)) {
                continue;
            }

            $config = $module['config'] ?? [];
            if (!is_array($config)) {
                continue;
            }

            $paths['module_config:' . $module['id']] = $this->writeJson(
                $directory . DIRECTORY_SEPARATOR . $this->safeName($module['id']) . '.config.json',
                $config
            );
        }

        return $paths;
    }

    /**
     * 执行 projectConfig 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function projectConfig(string $projectId, string $version): array
    {
        return $this->readJson($this->directory($projectId, $version) . DIRECTORY_SEPARATOR . 'project-config.json');
    }

    /**
     * 执行 moduleConfig 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function moduleConfig(string $projectId, string $version, string $moduleId): array
    {
        return $this->readJson($this->directory($projectId, $version) . DIRECTORY_SEPARATOR . $this->safeName($moduleId) . '.config.json');
    }

    /**
     * 执行 contractBindings 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function contractBindings(string $projectId, string $version): array
    {
        return $this->readJson($this->directory($projectId, $version) . DIRECTORY_SEPARATOR . 'contract-bindings.json');
    }

    /** 解析指定项目版本的存储目录。 */
    public function directory(string $projectId, string $version): string
    {
        return $this->root() . DIRECTORY_SEPARATOR . $this->safeName($projectId) . DIRECTORY_SEPARATOR . $this->safeName($version);
    }

    /** 解析数据存储根目录。 */
    private function root(): string
    {
        if ($this->rootPath !== null) {
            return $this->rootPath;
        }

        return function_exists('storage_path')
            ? storage_path('trix/projects')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trix-projects';
    }

    /**
     * 将数据写入指定存储位置。
     * @param array<string, mixed>|array<int, mixed> $payload
     */
    private function writeJson(string $path, array $payload): string
    {
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return $path;
    }

    /**
     * 从指定来源读取数据。
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 执行 arrayValue 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

        /** 生成可安全用于文件系统的名称。 */
    private function safeName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'item';
    }
}
