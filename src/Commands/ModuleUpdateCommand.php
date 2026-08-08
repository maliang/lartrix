<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Lartrix\Modules\Registry\RegistryModuleUpdateAuditLogger;
use Lartrix\Modules\Registry\RegistryModuleUpdateExecutor;
use Lartrix\Modules\Registry\RegistrySecurityAdvisory;

/** 预览或执行已安装模块更新，并保留安全检查、备份和审计记录。 */
class ModuleUpdateCommand extends Command
{
    protected $signature = 'lartrix:module-update
        {module : 市场模块 id}
        {--current-dir= : 已安装模块所在目录（包含 module.json）}
        {--source-dir= : 已审核的目标模块目录或 staging 目录}
        {--manifest=module.json : --source-dir 内的 manifest 路径}
        {--version= : 预期的目标模块版本}
        {--backup-dir= : 当前模块的备份目录，不能已存在}
        {--dry-run : 仅打印更新计划，不替换目录}
        {--strict-security : manifest 存在需人工审核的安全标记时直接失败}
        {--audit-log= : 更新审计记录的 JSONL 文件路径（可选）}
        {--allow-downgrade : 显式允许用旧版本替换当前模块}
        {--confirm-replace : 显式确认替换当前模块目录}';

    protected $description = '从已审核的市场包目录更新已安装的 Lartrix 模块。';

    /** 处理命令或请求的主流程。 */
    public function handle(): int
    {
        $moduleId = (string) $this->argument('module');
        $currentDir = (string) $this->option('current-dir');
        $sourceDir = (string) $this->option('source-dir');
        $manifest = (string) $this->option('manifest');
        $version = (string) $this->option('version');
        $backupDir = (string) $this->option('backup-dir');
        $dryRun = (bool) $this->option('dry-run');
        $strictSecurity = (bool) $this->option('strict-security');
        $auditLog = (string) $this->option('audit-log');
        $allowDowngrade = (bool) $this->option('allow-downgrade');
        $confirmed = (bool) $this->option('confirm-replace');

        if ($currentDir === '' || $sourceDir === '' || $manifest === '' || $version === '' || (!$dryRun && $backupDir === '')) {
            $this->error('--current-dir, --source-dir, --manifest, --version, and --backup-dir are required. --backup-dir is optional only with --dry-run.');
            return 1;
        }

        $executor = new RegistryModuleUpdateExecutor('php', 'laravel');

        if ($dryRun) {
            // dry-run 输出完整计划并写审计，便于在 CI 或后台 UI 中先做人工确认。
            $preview = $executor->preview($currentDir, $sourceDir, $manifest, $moduleId, $version, $allowDowngrade);
            $this->printUpdatePlan($preview);
            $this->writeAudit($auditLog, $moduleId, 'dry_run', $preview, null, $currentDir, $sourceDir, $backupDir);
            if ($strictSecurity && $this->securityBlocks(is_array($preview['security'] ?? null) ? $preview['security'] : [])) {
                $this->writeAudit($auditLog, $moduleId, 'strict_security_blocked', $preview, null, $currentDir, $sourceDir, $backupDir);
                $this->error('Strict security blocked this update plan.');
                return 1;
            }

            return $preview['allowed'] ? 0 : 1;
        }

        if ($strictSecurity) {
            // strict-security 用于生产环境：manifest 声明写文件、命令或外部网络时直接阻断。
            $preview = $executor->preview($currentDir, $sourceDir, $manifest, $moduleId, $version, $allowDowngrade);
            if (!$preview['allowed']) {
                $this->printUpdatePlan($preview);
                $this->writeAudit($auditLog, $moduleId, 'blocked', $preview, null, $currentDir, $sourceDir, $backupDir);
                return 1;
            }

            if ($this->securityBlocks(is_array($preview['security'] ?? null) ? $preview['security'] : [])) {
                $this->printUpdatePlan($preview);
                $this->writeAudit($auditLog, $moduleId, 'strict_security_blocked', $preview, null, $currentDir, $sourceDir, $backupDir);
                $this->error('Strict security blocked this update.');
                return 1;
            }
        }

        $result = $executor->execute(
            $currentDir,
            $sourceDir,
            $manifest,
            $moduleId,
            $version,
            $backupDir,
            $confirmed,
            $allowDowngrade
        );

        if (!$result['updated']) {
            $method = $result['action'] === 'already_current' ? 'warn' : 'error';
            $this->{$method}((string) $result['message']);
            $this->writeAudit($auditLog, $moduleId, 'not_updated', null, $result, $currentDir, $sourceDir, $backupDir);
            return $result['action'] === 'already_current' ? 0 : 1;
        }

        $this->info('Module updated from ' . $result['current_version'] . ' to ' . $result['target_version'] . '.');
        $this->info('Current module directory: ' . $result['target_path']);
        $this->info('Backup directory: ' . $result['backup_path']);
        $this->printSecurityWarnings(is_array($result['security'] ?? null) ? $result['security'] : []);
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $result['target_path'], $moduleId));
        $this->warn('Module files were replaced only. Run Laravel migrations, seeders, and cache/autoload refresh manually after review.');
        $this->writeAudit($auditLog, $moduleId, 'updated', null, $result, $currentDir, $sourceDir, $backupDir);

        return 0;
    }

    /**
     * 将数据写入指定存储位置。
     * @param array<string, mixed>|null $preview
     * @param array<string, mixed>|null $result
     */
    protected function writeAudit(
        string $auditLog,
        string $moduleId,
        string $event,
        ?array $preview,
        ?array $result,
        string $currentDir,
        string $sourceDir,
        string $backupDir
    ): void {
        if ($auditLog === '') {
            return;
        }

        // 审计日志采用 JSONL，方便追加写入，也方便后续按行导入后台或日志系统。
        $payload = $result ?? $preview ?? [];
        $write = (new RegistryModuleUpdateAuditLogger())->append($auditLog, [
            'event' => $event,
            'module_id' => $moduleId,
            'language' => 'php',
            'framework' => 'laravel',
            'action' => $payload['action'] ?? null,
            'message' => $payload['message'] ?? null,
            'current_version' => $payload['current_version'] ?? null,
            'target_version' => $payload['target_version'] ?? null,
            'current_dir' => $currentDir,
            'source_dir' => $sourceDir,
            'backup_dir' => $backupDir !== '' ? $backupDir : null,
            'target_path' => $payload['target_path'] ?? null,
            'backup_path' => $payload['backup_path'] ?? null,
            'security' => is_array($payload['security'] ?? null) ? $payload['security'] : [],
            'plan' => is_array($payload['plan'] ?? null) ? $payload['plan'] : null,
        ]);

        if (!$write['written']) {
            $this->warn('Audit log was not written: ' . $write['message']);
        }
    }

    /**
     * 执行 securityBlocks 方法对应的具体职责。
     * @param array<string, mixed> $security
     */
    protected function securityBlocks(array $security): bool
    {
        return (new RegistrySecurityAdvisory())->blocksStrict($security);
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $preview
     */
    protected function printUpdatePlan(array $preview): void
    {
        $this->line('Update plan: ' . $preview['action']);
        $this->line('Current version: ' . ($preview['current_version'] ?? 'unknown'));
        $this->line('Target version: ' . ($preview['target_version'] ?? 'unknown'));
        $this->line('Allowed: ' . ($preview['allowed'] ? 'yes' : 'no'));
        $this->line('Message: ' . $preview['message']);
        $this->printSecurityWarnings(is_array($preview['security'] ?? null) ? $preview['security'] : []);
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
}
