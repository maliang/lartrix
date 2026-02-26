<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RemoveBackendCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'lartrix:remove-backend
                            {name : 模块名称 (StudlyCase, 如: Merchant)}
                            {--force : 跳过确认提示}';

    /**
     * 命令描述
     */
    protected $description = '卸载一个后台模块';

    /**
     * 模块名称
     */
    protected string $moduleName;

    /**
     * 模块名称（小写）
     */
    protected string $lowerName;

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->lowerName = Str::lower($this->moduleName);
        $modulePath = base_path('Modules/' . $this->moduleName);

        if (!File::isDirectory($modulePath)) {
            $this->error("模块 [{$this->moduleName}] 不存在！");
            return self::FAILURE;
        }

        // 读取模块配置
        $config = [];
        $configFile = $modulePath . '/config/config.php';
        if (File::exists($configFile)) {
            $config = require $configFile;
        }

        $guard = $config['backend']['guard'] ?? $this->lowerName;
        $table = $config['backend']['table'] ?? $this->lowerName . 's';

        // 显示将要删除的内容
        $this->newLine();
        $this->warn('即将卸载后台模块：');
        $this->table(
            ['项目', '值'],
            [
                ['模块名称', $this->moduleName],
                ['Guard', $guard],
                ['用户表', $table],
                ['模块目录', $modulePath],
            ]
        );
        $this->newLine();
        $this->warn('此操作将删除：');
        $this->line('  - 模块目录及所有文件');
        $this->line("  - 数据库表: {$table}");
        $this->line("  - 该模块的菜单、角色、权限数据 (guard_name = '{$guard}')");
        $this->line("  - auth.php 中的 {$guard} guard 和 provider 配置");
        $this->line("  - 相关的 token 数据");

        if (!$this->option('force')) {
            if (!$this->confirm('确定要卸载此模块吗？此操作不可逆！', false)) {
                $this->info('已取消。');
                return self::SUCCESS;
            }

            if (!$this->confirm('请再次确认：所有数据将被永久删除，是否继续？', false)) {
                $this->info('已取消。');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // 1. 清理数据库数据
        $this->info('1. 清理数据库数据...');
        $this->cleanupDatabase($guard, $table);

        // 2. 清理 auth.php
        $this->info('2. 清理 auth.php...');
        $this->cleanupAuthConfig($guard);

        // 3. 清理迁移记录
        $this->info('3. 清理迁移记录...');
        $this->cleanupMigrationRecords($table);

        // 4. 禁用并删除模块
        $this->info('4. 删除模块...');
        $this->deleteModule($modulePath);

        // 输出摘要
        $this->newLine();
        $this->info('========================================');
        $this->info("  后台模块 [{$this->moduleName}] 卸载完成！");
        $this->info('========================================');

        return self::SUCCESS;
    }

    /**
     * 清理数据库数据
     */
    protected function cleanupDatabase(string $guard, string $table): void
    {
        Schema::disableForeignKeyConstraints();

        // 删除该 guard 的菜单
        if (Schema::hasTable('admin_menus')) {
            $count = DB::table('admin_menus')->where('guard_name', $guard)->count();
            DB::table('admin_menus')->where('guard_name', $guard)->delete();
            $this->line("   删除菜单: {$count} 条");
        }

        // 获取该 guard 的角色和权限 ID
        $roleIds = [];
        $permissionIds = [];

        if (Schema::hasTable('roles')) {
            $roleIds = DB::table('roles')->where('guard_name', $guard)->pluck('id')->toArray();
            $this->line("   找到角色: " . count($roleIds) . " 个");
        }

        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->where('guard_name', $guard)->pluck('id')->toArray();
            $this->line("   找到权限: " . count($permissionIds) . " 个");
        }

        // 清理关联表
        if (!empty($roleIds)) {
            if (Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->whereIn('role_id', $roleIds)->delete();
            }
            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
            }
        }

        if (!empty($permissionIds)) {
            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            }
        }

        // 删除角色和权限
        if (Schema::hasTable('roles') && !empty($roleIds)) {
            DB::table('roles')->whereIn('id', $roleIds)->delete();
            $this->line("   删除角色: " . count($roleIds) . " 个");
        }

        if (Schema::hasTable('permissions') && !empty($permissionIds)) {
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            $this->line("   删除权限: " . count($permissionIds) . " 个");
        }

        // 清理 token（通过 tokenable_type 匹配模块 Model）
        if (Schema::hasTable('personal_access_tokens')) {
            $modelClass = "Modules\\{$this->moduleName}\\Models\\{$this->moduleName}";
            $count = DB::table('personal_access_tokens')
                ->where('tokenable_type', $modelClass)
                ->count();
            DB::table('personal_access_tokens')
                ->where('tokenable_type', $modelClass)
                ->delete();
            if ($count > 0) {
                $this->line("   删除 token: {$count} 条");
            }
        }

        // 删除模块用户表
        if (Schema::hasTable($table)) {
            Schema::drop($table);
            $this->line("   删除数据表: {$table}");
        }

        Schema::enableForeignKeyConstraints();
        $this->info('   数据库清理完成。');
    }

    /**
     * 清理 auth.php 配置
     */
    protected function cleanupAuthConfig(string $guard): void
    {
        $authPath = config_path('auth.php');

        if (!File::exists($authPath)) {
            $this->line('   auth.php 不存在，跳过。');
            return;
        }

        $content = File::get($authPath);
        $changed = false;

        // 移除 guard 配置
        $guardPattern = "/\n\s*'{$guard}'\s*=>\s*\[.*?\],/s";
        if (preg_match($guardPattern, $content)) {
            $content = preg_replace($guardPattern, '', $content);
            $this->line("   移除 {$guard} guard。");
            $changed = true;
        }

        // 移除 guard 上方的注释行（如果有）
        $commentPattern = "/\n\s*\/\/.*{$this->moduleName}.*\n/i";
        $content = preg_replace($commentPattern, "\n", $content);

        // 移除 provider 配置
        $providerName = $guard . 's';
        $providerPattern = "/\n\s*'{$providerName}'\s*=>\s*\[.*?\],/s";
        if (preg_match($providerPattern, $content)) {
            $content = preg_replace($providerPattern, '', $content);
            $this->line("   移除 {$providerName} provider。");
            $changed = true;
        }

        if ($changed) {
            // 清理多余空行
            $content = preg_replace("/\n{3,}/", "\n\n", $content);
            File::put($authPath, $content);
            $this->info('   auth.php 清理完成。');
        } else {
            $this->line('   auth.php 中未找到相关配置。');
        }
    }

    /**
     * 清理迁移记录
     */
    protected function cleanupMigrationRecords(string $table): void
    {
        if (!Schema::hasTable('migrations')) {
            return;
        }

        $pattern = "%create_{$table}_table%";
        $count = DB::table('migrations')->where('migration', 'like', $pattern)->count();
        DB::table('migrations')->where('migration', 'like', $pattern)->delete();

        if ($count > 0) {
            $this->line("   清理迁移记录: {$count} 条");
        }
        $this->info('   迁移记录清理完成。');
    }

    /**
     * 删除模块
     */
    protected function deleteModule(string $modulePath): void
    {
        // 先尝试禁用模块
        try {
            $module = app('modules')->find($this->moduleName);
            if ($module && $module->isEnabled()) {
                Artisan::call('module:disable', ['module' => $this->moduleName]);
            }
        } catch (\Exception $e) {
            // 忽略
        }

        // 从 modules_statuses.json 中移除
        $statusFile = base_path('modules_statuses.json');
        if (File::exists($statusFile)) {
            $statuses = json_decode(File::get($statusFile), true) ?: [];
            unset($statuses[$this->moduleName]);
            File::put($statusFile, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // 删除模块目录
        if (File::isDirectory($modulePath)) {
            File::deleteDirectory($modulePath);
            $this->info("   模块目录已删除: {$modulePath}");
        }
    }
}
