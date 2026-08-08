<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Lartrix\Modules\Registry\RegistryClient;
use Lartrix\Modules\Registry\RegistryPackagePipeline;
use Lartrix\Modules\Registry\RegistryModuleReplacer;
use Lartrix\Modules\Registry\RegistryPackagePreflightInspector;
use Lartrix\Modules\Registry\RegistrySecurityAdvisory;
use Lartrix\Modules\Registry\RegistryVersionResolver;
use Lartrix\Modules\Registry\RegistryStagedPackageInstaller;
use Lartrix\Modules\Registry\RegistryStagedManifestVerifier;
use Lartrix\Services\ModuleService;
use Lartrix\Models\Module;

/** 安装本地模块或来自模块市场的已审核模块包。 */
class ModuleInstallCommand extends Command
{
    protected $signature = 'lartrix:module-install
        {name?* : 模块名称，留空则安装全部未安装模块}
        {--registry= : 模块市场 API 基础 URL，用于后续适配器解析}
        {--download : 校验通过后将市场适配器包下载到缓存，不进行安装}
        {--signature-key= : 用于校验市场适配器包签名的 HMAC 密钥（可选）}
        {--from-stage= : 将已校验的 staging 目录复制到本地模块目录}
        {--manifest= : --from-stage 时 staging 目录内的 manifest 路径}
        {--version= : --from-stage 校验时预期的模块版本}
        {--target-dir= : --from-stage 或 --replace-from-dir 的最终本地模块目录}
        {--replace-from-dir= : 用该已审核源目录替换现有的本地模块目录}
        {--backup-dir= : --replace-from-dir 的备份目录，不能已存在}
        {--confirm-replace : 显式确认替换目标模块目录}';
    protected $description = '安装模块。不带参数时安装所有未安装的模块。';

    /** 处理命令或请求的主流程。 */
    public function handle(ModuleService $moduleService): int
    {
        $names = $this->argument('name');

        // Registry 包分两步落地：先从已校验 staging 复制，再由人工执行框架安装流程。
        if ($this->option('from-stage')) {
            return $this->installFromStage($names);
        }

        // 替换已有模块属于高风险操作，必须显式传入源目录、备份目录和确认参数。
        if ($this->option('replace-from-dir')) {
            return $this->replaceFromDirectory($names);
        }

        if (empty($names)) {
            $allModules = Module::where('enabled', false)->get();
            foreach ($allModules as $m) {
                $this->installSingle($m->name, $moduleService);
            }
            return 0;
        }

        foreach ($names as $name) {
            $this->installSingle($name, $moduleService);
        }

        return 0;
    }

    /**
     * 备份旧目录并替换为新版本。
     * @param array<int, string> $names
     */
    protected function replaceFromDirectory(array $names): int
    {
        if (count($names) !== 1) {
            $this->error('Exactly one registry module id is required when using --replace-from-dir.');
            return 1;
        }

        $sourceDir = (string) $this->option('replace-from-dir');
        $manifest = (string) $this->option('manifest');
        $version = (string) $this->option('version');
        $targetDir = (string) $this->option('target-dir');
        $backupDir = (string) $this->option('backup-dir');
        $confirmed = (bool) $this->option('confirm-replace');

        if ($manifest === '' || $version === '' || $targetDir === '' || $backupDir === '') {
            $this->error('--manifest, --version, --target-dir, and --backup-dir are required when using --replace-from-dir.');
            return 1;
        }

        $moduleId = (string) $names[0];
        $verify = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify($sourceDir, $manifest, $moduleId, $version);
        if (!$verify['ok']) {
            $this->error((string) $verify['message']);
            return 1;
        }
        $this->printSecurityWarnings(is_array($verify['security'] ?? null) ? $verify['security'] : []);

        // Replacer 会先备份当前目录，再移动新目录；这里不做迁移/Seeder，留给人工复核后执行。
        $moduleSourceDir = dirname($sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest));
        $replace = (new RegistryModuleReplacer())->replace($moduleSourceDir, $targetDir, $backupDir, $confirmed);
        if (!$replace['replaced']) {
            $this->error((string) $replace['message']);
            return 1;
        }

        $this->info('Module directory replaced: ' . $replace['target_path']);
        $this->info('Previous version backed up at: ' . $replace['backup_path']);
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $replace['target_path'], $moduleId));
        $this->warn('Module files were replaced only. Run the Laravel module install/enable/migration flow manually after review.');

        return 0;
    }

    /**
     * 执行模块或项目安装流程。
     * @param array<int, string> $names
     */
    protected function installFromStage(array $names): int
    {
        if (count($names) !== 1) {
            $this->error('Exactly one registry module id is required when using --from-stage.');
            return 1;
        }

        $stagePath = (string) $this->option('from-stage');
        $manifest = (string) $this->option('manifest');
        $version = (string) $this->option('version');
        $targetDir = (string) $this->option('target-dir');

        if ($manifest === '' || $version === '' || $targetDir === '') {
            $this->error('--manifest, --version, and --target-dir are required when using --from-stage.');
            return 1;
        }

        $moduleId = (string) $names[0];
        $verify = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify($stagePath, $manifest, $moduleId, $version);
        if (!$verify['ok']) {
            $this->error((string) $verify['message']);
            return 1;
        }
        $this->printSecurityWarnings(is_array($verify['security'] ?? null) ? $verify['security'] : []);

        $install = (new RegistryStagedPackageInstaller())->install($stagePath, $manifest, $targetDir);
        if (!$install['installed']) {
            $this->error((string) $install['message']);
            return 1;
        }

        $this->info('Staged package copied to: ' . $install['path']);
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $install['path'], $moduleId));
        $this->warn('Module files were copied only. Run the Laravel module install/enable flow manually after review.');

        return 0;
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $checklist
     */
    protected function printPostCopyChecklist(array $checklist): void
    {
        $this->line('Review checklist:');
        foreach ($checklist['todos'] ?? [] as $todo) {
            $this->line('- ' . $todo);
        }

        if (!empty($checklist['commands'])) {
            $this->line('Suggested commands:');
            foreach ($checklist['commands'] as $command) {
                $this->line('- ' . $command);
            }
        }
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $security
     */
    protected function printSecurityWarnings(array $security): void
    {
        $warnings = (new RegistrySecurityAdvisory())->warnings($security);
        if ($warnings === []) {
            return;
        }

        $this->warn('Security review required:');
        foreach ($warnings as $warning) {
            $this->warn('- ' . $warning);
        }
    }

        /** 执行模块或项目安装流程。 */
    protected function installSingle(string $name, ModuleService $moduleService): void
    {
        $registry = $this->registryUrl();
        if ($registry !== '') {
            $this->previewRegistryInstall($name, $registry);
            return;
        }

        $this->info("Installing module: {$name}...");

        if ($moduleService->install($name)) {
            $this->info("Module [{$name}] installed successfully.");
        } else {
            $this->error("Module [{$name}] installation failed.");
        }
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryUrl(): string
    {
        $option = trim((string) ($this->option('registry') ?? ''));
        if ($option !== '') {
            return $option;
        }

        return trim((string) config('lartrix.module_market.url', ''));
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registrySignatureKey(): string
    {
        $option = trim((string) ($this->option('signature-key') ?? ''));
        if ($option !== '') {
            return $option;
        }

        return (string) config('lartrix.module_market.signature_key', '');
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryAuthKey(): string
    {
        return trim((string) config('lartrix.module_market.auth_key', ''));
    }

    /** 查询 Registry 模块版本，并按需完成下载、预检和暂存。 */
    protected function previewRegistryInstall(string $moduleId, string $registry): void
    {
        $url = rtrim($registry, '/') . '/registry/modules/' . rawurlencode($moduleId) . '/versions';
        $response = (new RegistryClient($registry, $this->registryAuthKey()))->request()->get($url, [
            'page_size' => 1,
            'language' => 'php',
            'framework' => 'laravel',
        ]);

        if (!$response->successful()) {
            $this->error("Registry module [{$moduleId}] lookup failed: HTTP {$response->status()}.");
            return;
        }

        // Registry 返回的是“模块版本 + 当前 adapter”，安装器只接受 php/laravel。
        $payload = $response->json();
        if (!is_array($payload)) {
            $this->error("Registry module [{$moduleId}] returned an invalid response.");
            return;
        }

        $result = (new RegistryVersionResolver('php', 'laravel'))->resolveLatest($payload);
        if (!$result['installable']) {
            $this->error((string) $result['message']);
            return;
        }

        $adapter = $result['adapter'];
        $version = $result['version'];
        $this->info("Registry module [{$moduleId}] version [{$version['version']}] has an installable PHP/Laravel adapter.");
        $manifest = is_array($version['manifest'] ?? null) ? $version['manifest'] : [];
        $this->printSecurityWarnings(is_array($manifest['security'] ?? null) ? $manifest['security'] : []);
        $this->line('Package type: ' . ($adapter['package_type'] ?? 'unknown'));
        if (!empty($adapter['package_url'])) {
            $this->line('Package URL: ' . $adapter['package_url']);
        }

        if (!$this->option('download')) {
            $this->warn('Registry adapter download was not requested. Re-run with --download to cache the package after checksum validation.');
            return;
        }

        $prepared = (new RegistryPackagePipeline(new RegistryClient($registry, $this->registryAuthKey())))
            ->prepare($adapter, $moduleId, (string) ($version['version'] ?? 'latest'));
        if (!$prepared['ok']) {
            $this->error((string) $prepared['message']);
            return;
        }

        $this->info('Package staged at: ' . $prepared['path']);
        $this->printSecurityWarnings(is_array($prepared['security'] ?? null) ? $prepared['security'] : []);
        $this->warn('Registry package is staged only. Install or enable the Laravel adapter through the local module flow.');
    }
}
