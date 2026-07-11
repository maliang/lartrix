<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Lartrix\Modules\Project\ProjectInstallPlanStore;
use Lartrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Lartrix\Modules\Registry\RegistryPackageDownloader;
use Lartrix\Modules\Registry\RegistryPackageStager;
use Lartrix\Modules\Registry\RegistryStagedManifestVerifier;
use Lartrix\Modules\Registry\RegistryStagedPackageInstaller;

/** 按项目清单安装依赖模块，并落地项目配置与契约绑定。 */
class ProjectInstallCommand extends Command
{
    protected $signature = 'lartrix:project-install
                            {project? : Project registry id, not required when --plan is used}
                            {--version= : Project version, defaults to registry latest}
                            {--registry= : Registry API base URL}
                            {--auth-key= : Auth Key, defaults to TRIX_AUTH_KEY config}
                            {--language=php : Adapter language}
                            {--framework=laravel : Adapter framework}
                            {--plan= : Existing install-plan.json path}
                            {--target-root=Modules : Directory where modules will be copied when --execute is set}
                            {--audit-log= : Optional JSONL audit log path}
                            {--execute : Download, stage, verify and copy missing modules}
                            {--dry-run : Resolve and save the plan without downloading modules}';

    protected $description = 'Install a Trix project plan by downloading and staging its module dependencies.';

    /** 处理命令或请求的主流程。 */
    public function handle(): int
    {
        $plan = $this->option('plan') ? $this->readPlan((string) $this->option('plan')) : $this->fetchPlan();
        if ($plan === null) {
            return self::FAILURE;
        }

        $projectId = (string) ($plan['project'] ?? $this->argument('project') ?? 'project');
        $version = (string) ($plan['version'] ?? $this->option('version') ?? 'version');
        $paths = (new ProjectInstallPlanStore())->save($projectId, $version, $plan);

        // install-plan、项目覆盖配置、契约绑定分别落地，运行时可按需读取其中一部分。
        $this->info('Project install plan saved: ' . $paths['install_plan']);
        $this->line('Project config: ' . $paths['project_config']);
        $this->line('Contract bindings: ' . $paths['contract_bindings']);

        if (($plan['install']['allowed'] ?? false) !== true) {
            $message = (string) ($plan['install']['reason'] ?? 'Project install plan is not allowed.');
            $this->error($message);
            $this->writeAudit($projectId, $version, 'blocked', ['message' => $message]);
            return self::FAILURE;
        }

        $tasks = $this->buildTasks($plan);
        $this->printTasks($tasks);

        if (!$this->option('execute') || $this->option('dry-run')) {
            $this->warn('Dry run only. Re-run with --execute to download and copy missing modules.');
            return self::SUCCESS;
        }

        $failed = false;
        foreach ($tasks as $task) {
            $result = $this->executeTask($task);
            $this->writeAudit($projectId, $version, 'module', array_merge($task, $result));
            $failed = $failed || !($result['ok'] ?? false);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * 从指定来源读取数据。
     * @return array<string, mixed>|null
     */
    private function readPlan(string $path): ?array
    {
        if (!File::exists($path)) {
            $this->error('Install plan not found: ' . $path);
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            $this->error('Install plan is not valid JSON.');
            return null;
        }

        return $decoded;
    }

    /**
     * 从远端服务获取并解析数据。
     * @return array<string, mixed>|null
     */
    private function fetchPlan(): ?array
    {
        $projectId = (string) ($this->argument('project') ?? '');
        if ($projectId === '') {
            $this->error('Project registry id is required when --plan is not used.');
            return null;
        }

        $registry = $this->registryUrl();
        if ($registry === '') {
            $this->error('Please configure registry URL.');
            return null;
        }

        $version = (string) ($this->option('version') ?? '');
        if ($version === '') {
            $response = $this->registryRequest()->get($registry . '/registry/projects/' . rawurlencode($projectId) . '/versions', [
                'page_size' => 1,
                'language' => $this->option('language'),
                'framework' => $this->option('framework'),
            ]);

            if (!$response->successful()) {
                $this->error('Project version lookup failed: HTTP ' . $response->status());
                return null;
            }

            $version = data_get($response->json(), 'data.items.0.version') ?: '';
        }

        if ($version === '') {
            $this->error('Project has no installable version.');
            return null;
        }

        $response = $this->registryRequest()->get(
            $registry . '/registry/projects/' . rawurlencode($projectId) . '/versions/' . rawurlencode($version) . '/install-plan',
            [
                'language' => $this->option('language'),
                'framework' => $this->option('framework'),
            ]
        );

        if (!$response->successful()) {
            $this->error('Project install plan lookup failed: HTTP ' . $response->status());
            return null;
        }

        $plan = $response->json('data');
        if (!is_array($plan)) {
            $this->error('Registry returned an invalid install plan.');
            return null;
        }

        return $plan;
    }

    /**
     * 构建当前流程使用的数据结构。
     * @param array<string, mixed> $plan
     * @return array<int, array<string, mixed>>
     */
    private function buildTasks(array $plan): array
    {
        $tasks = [];

        foreach (($plan['modules'] ?? []) as $module) {
            if (!is_array($module) || !is_string($module['id'] ?? null)) {
                continue;
            }

            // 项目安装只处理当前 language/framework 的 adapter，避免把其他框架包复制进来。
            $adapter = is_array($module['adapter'] ?? null) ? $module['adapter'] : [];
            $moduleId = $module['id'];
            $tasks[] = [
                'id' => $moduleId,
                'version' => (string) ($module['selected_version'] ?? 'latest'),
                'required' => (bool) ($module['required'] ?? true),
                'allowed' => ($module['install']['allowed'] ?? false) === true,
                'reason' => (string) ($module['install']['reason'] ?? $module['constraint_reason'] ?? ''),
                'adapter' => $adapter,
                'target' => base_path(trim((string) $this->option('target-root'), '/\\') . DIRECTORY_SEPARATOR . $this->moduleDirectoryName($moduleId)),
            ];
        }

        return $tasks;
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<int, array<string, mixed>> $tasks
     */
    private function printTasks(array $tasks): void
    {
        $this->line('Module tasks:');
        foreach ($tasks as $task) {
            $status = $task['allowed'] ? 'installable' : 'blocked';
            $this->line("- {$task['id']} {$task['version']} [{$status}] -> {$task['target']}");
            if (!$task['allowed'] || $task['reason'] !== '') {
                $this->line('  ' . $task['reason']);
            }
        }
    }

    /**
     * 执行 executeTask 方法对应的具体职责。
     * @param array<string, mixed> $task
     * @return array<string, mixed>
     */
    private function executeTask(array $task): array
    {
        if (!$task['allowed']) {
            $this->error("Skipped blocked module: {$task['id']}");
            return ['ok' => false, 'reason' => 'blocked'];
        }

        if (File::exists((string) $task['target'])) {
            // 一键项目安装默认不覆盖旧模块，升级必须走 module-update 的备份/审计流程。
            $this->warn("Target exists, skipped without overwrite: {$task['target']}");
            return ['ok' => true, 'reason' => 'target_exists'];
        }

        $download = (new RegistryPackageDownloader(fetcher: fn (string $url): ?string => $this->fetchPackage($url)))
            ->download($task['adapter'], (string) $task['id'], (string) $task['version']);

        if (!($download['downloaded'] ?? false)) {
            $this->error((string) ($download['message'] ?? 'Package download failed.'));
            return ['ok' => false, 'reason' => $download['reason'] ?? 'download_failed'];
        }

        $stage = (new RegistryPackageStager())->stage((string) $download['path'], (string) $task['id'], (string) $task['version']);
        if (!($stage['staged'] ?? false)) {
            $this->error((string) ($stage['message'] ?? 'Package staging failed.'));
            return ['ok' => false, 'reason' => $stage['reason'] ?? 'stage_failed'];
        }

        $verify = (new RegistryStagedManifestVerifier((string) $this->option('language'), (string) $this->option('framework')))
            ->verify((string) $stage['path'], (string) $stage['manifest'], (string) $task['id'], (string) $task['version']);
        if (!($verify['ok'] ?? false)) {
            $this->error((string) ($verify['message'] ?? 'Staged manifest verification failed.'));
            return ['ok' => false, 'reason' => $verify['reason'] ?? 'verify_failed'];
        }

        $install = (new RegistryStagedPackageInstaller())->install((string) $stage['path'], (string) $stage['manifest'], (string) $task['target']);
        if (!($install['installed'] ?? false)) {
            $this->error((string) ($install['message'] ?? 'Staged package copy failed.'));
            return ['ok' => false, 'reason' => $install['reason'] ?? 'install_failed'];
        }

        $this->info("Installed module files: {$task['target']}");
        foreach (((new RegistryInstalledPackageChecklist())->build((string) $task['target'], (string) $task['id'])['todos'] ?? []) as $todo) {
            $this->line('- ' . $todo);
        }

        return ['ok' => true, 'reason' => null];
    }

        /** 从远端服务获取并解析数据。 */
    private function fetchPackage(string $url): ?string
    {
        $response = $this->registryRequest()->get($url);

        return $response->successful() ? $response->body() : null;
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryUrl(): string
    {
        $option = trim((string) ($this->option('registry') ?? ''));

        return rtrim($option !== '' ? $option : (string) config('lartrix.module_registry.url', ''), '/');
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryAuthKey(): string
    {
        $option = trim((string) ($this->option('auth-key') ?? ''));

        return $option !== '' ? $option : trim((string) config('lartrix.module_registry.auth_key', ''));
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::acceptJson()->timeout(60);
        $authKey = $this->registryAuthKey();

        return $authKey === '' ? $request : $request->withToken($authKey);
    }

    /**
     * 将数据写入指定存储位置。
     * @param array<string, mixed> $payload
     */
    private function writeAudit(string $projectId, string $version, string $event, array $payload): void
    {
        $path = trim((string) ($this->option('audit-log') ?? ''));
        if ($path === '') {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::append($path, json_encode([
            'time' => now()->toJSON(),
            'project' => $projectId,
            'version' => $version,
            'event' => $event,
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

        /** 执行 moduleDirectoryName 方法对应的具体职责。 */
    private function moduleDirectoryName(string $moduleId): string
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '-', '_'], ' ', $moduleId)));
    }
}
