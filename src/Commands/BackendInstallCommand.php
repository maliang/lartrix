<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Lartrix\Commands\Concerns\ConfiguresBackendAuth;

/**
 * 重新安装一个已存在的后台模块（迁移 + 数据填充 + guard 配置）。
 *
 * 适用场景：把已有的二级后台（make-backend 生成）迁移到新数据库/新环境时，
 * 一次性补齐 config/auth.php 的 guard/provider、模块迁移与 Seeder 数据。
 * 所有步骤均为幂等操作，可重复执行。
 */
class BackendInstallCommand extends Command
{
    use ConfiguresBackendAuth;

    /**
     * 命令签名
     */
    protected $signature = 'lartrix:backend-install
                            {name : 后台模块名称 (StudlyCase, 如: Merchant)}';

    /**
     * 命令描述
     */
    protected $description = '重新安装一个已存在的后台模块：补 guard 配置、运行迁移与数据填充';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $moduleName = Str::studly($this->argument('name'));
        $lowerName = Str::lower($moduleName);

        // 1. 校验模块存在
        $modulePath = base_path('Modules/' . $moduleName);
        if (!File::isDirectory($modulePath)) {
            $this->error("后台模块 [{$moduleName}] 不存在（未找到 {$modulePath}）。");
            return self::FAILURE;
        }
        $this->info("正在重新安装后台模块 [{$moduleName}]...");

        // 2. 配置 auth.php guard/provider（幂等）
        $this->info('1. 配置 auth guard...');
        if ($this->configureBackendAuth($lowerName, $moduleName)) {
            $this->info('   已补全 auth.php 的 guard/provider。');
        } else {
            $this->line('   guard/provider 已存在或无需修改。');
        }

        // 3. 启用模块（nwidart + modules_statuses.json + modules 表同步）
        $this->info('2. 启用模块...');
        $this->ensureModuleEnabled($moduleName);
        $this->syncModulesTable($moduleName);

        // 4. 运行迁移（幂等：migrations 表无记录才执行）
        $this->info('3. 运行数据库迁移...');
        $migrateResult = Artisan::call('module:migrate', ['module' => $moduleName]);
        if ($migrateResult === 0) {
            $this->info('   迁移完成。');
        } else {
            $this->warn('   迁移未全部执行，请检查上方输出。');
        }

        // 5. 数据填充（优先执行 {Name}BackendSeeder，回退 module:seed）
        $this->info('4. 初始化数据（菜单、权限、角色、管理员）...');
        $this->runBackendSeeder($moduleName);
        $this->info('   数据初始化完成。');

        // 6. 输出摘要
        $this->newLine();
        $this->info('========================================');
        $this->info("  后台模块 [{$moduleName}] 安装完成！");
        $this->info('========================================');
        $this->newLine();
        $config = $this->readModuleBackendConfig($modulePath, $lowerName);
        $this->table(
            ['配置项', '值'],
            [
                ['前端路径', $config['path'] ?? ('/' . $lowerName)],
                ['API 前缀', $config['api_prefix'] ?? ('api/' . $lowerName)],
                ['Guard', $lowerName],
                ['用户表', $config['table'] ?? ($lowerName . 's')],
                ['管理员账号', 'admin / 123456'],
            ]
        );
        $this->newLine();
        $path = $config['path'] ?? ('/' . $lowerName);
        $this->info("前端资源共享 public/admin/，通过 {$path} 访问。");

        return self::SUCCESS;
    }

    /**
     * 确保模块已启用（nwidart 状态 + modules_statuses.json）
     */
    protected function ensureModuleEnabled(string $moduleName): void
    {
        try {
            $module = app('modules')->find($moduleName);
            if ($module && !$module->isEnabled()) {
                Artisan::call('module:enable', ['module' => $moduleName]);
                $this->info("   模块 [{$moduleName}] 已启用。");
            } else {
                $this->line("   模块 [{$moduleName}] 状态正常。");
            }
        } catch (\Exception $e) {
            // 手动写入 modules_statuses.json
            $statusFile = base_path('modules_statuses.json');
            $statuses = [];
            if (File::exists($statusFile)) {
                $statuses = json_decode(File::get($statusFile), true) ?: [];
            }
            $statuses[$moduleName] = true;
            File::put($statusFile, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("   模块 [{$moduleName}] 已启用。");
        }
    }

    /**
     * 同步 modules 表记录（确保 nwidart 发现的模块在 lartrix modules 表中有记录）
     */
    protected function syncModulesTable(string $moduleName): void
    {
        try {
            app(\Lartrix\Services\ModuleService::class)->syncModules();
        } catch (\Throwable $e) {
            $this->warn('   modules 表同步失败（不影响迁移与填充）：' . $e->getMessage());
        }
    }

    /**
     * 执行后台模块 Seeder（{Name}BackendSeeder）。
     * 模块刚创建/刚复制到新环境时 Composer autoloader 可能未更新，需手动加载依赖文件。
     * 若不存在 BackendSeeder，回退到 nwidart 的 module:seed。
     */
    protected function runBackendSeeder(string $moduleName): void
    {
        $modulePath = base_path('Modules/' . $moduleName);

        // 手动加载模型与 Seeder 文件
        $filesToLoad = [
            "app/Models/{$moduleName}.php",
            "database/seeders/{$moduleName}BackendSeeder.php",
        ];
        foreach ($filesToLoad as $file) {
            $fullPath = $modulePath . '/' . $file;
            if (File::exists($fullPath)) {
                require_once $fullPath;
            }
        }

        $seederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}BackendSeeder";

        if (class_exists($seederClass)) {
            $seeder = new $seederClass();
            if (method_exists($seeder, 'setCommand')) {
                $seeder->setCommand($this);
            }
            $seeder->run();
        } else {
            $this->line('   未找到 {Name}BackendSeeder，回退到 module:seed...');
            Artisan::call('module:seed', ['module' => $moduleName]);
        }
    }

    /**
     * 读取模块 config/config.php 的 backend 段（用于摘要输出）
     *
     * @return array<string, mixed>
     */
    protected function readModuleBackendConfig(string $modulePath, string $lowerName): array
    {
        $configFile = $modulePath . '/config/config.php';
        if (!File::exists($configFile)) {
            return [];
        }
        $config = require $configFile;
        return is_array($config['backend'] ?? null) ? $config['backend'] : [];
    }
}
