<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'lartrix:uninstall 
                            {--tables : 仅删除数据表}
                            {--migrations : 仅删除迁移文件}
                            {--config : 仅删除配置文件}
                            {--assets : 仅删除前端资源}
                            {--force : 跳过确认提示}';

    /**
     * 命令描述
     */
    protected $description = '卸载 Lartrix 后台管理系统';

    /**
     * Lartrix 相关的数据表
     */
    protected array $tables = [
        'admin_users',
        'admin_menus',
        'admin_settings',
        'modules',
    ];

    /**
     * Spatie Permission 相关的数据表
     */
    protected array $permissionTables = [
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permissions',
        'roles',
    ];

    /**
     * Sanctum 相关的数据表
     */
    protected array $sanctumTables = [
        'personal_access_tokens',
    ];

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $this->info('Lartrix 卸载程序');
        $this->newLine();

        // 检查是否指定了特定选项
        $hasSpecificOption = $this->option('tables') 
            || $this->option('migrations') 
            || $this->option('config') 
            || $this->option('assets');

        // 如果没有指定特定选项，执行完整卸载
        if (!$hasSpecificOption) {
            return $this->fullUninstall();
        }

        // 执行指定的卸载操作
        if ($this->option('tables')) {
            $this->dropTables();
        }

        if ($this->option('migrations')) {
            $this->deleteMigrations();
        }

        if ($this->option('config')) {
            $this->deleteConfig();
        }

        if ($this->option('assets')) {
            $this->deleteAssets();
        }

        $this->newLine();
        $this->info('指定的卸载操作已完成。');

        return self::SUCCESS;
    }

    /**
     * 完整卸载
     */
    protected function fullUninstall(): int
    {
        $this->warn('警告：此操作将删除以下内容：');
        $this->line('  - Lartrix 相关的数据表（admin_users, admin_menus, admin_settings, modules）');
        $this->line('  - Spatie Permission 相关的数据表（permissions, roles 等）');
        $this->line('  - Sanctum 相关的数据表（personal_access_tokens）');
        $this->line('  - 迁移文件');
        $this->line('  - 配置文件（config/lartrix.php）');
        $this->line('  - 前端资源（public/admin）');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('确定要继续卸载吗？此操作不可逆！', false)) {
                $this->info('卸载已取消。');
                return self::SUCCESS;
            }

            // 二次确认
            if (!$this->confirm('请再次确认：所有数据将被永久删除，是否继续？', false)) {
                $this->info('卸载已取消。');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // 1. 删除数据表
        $this->info('1. 删除数据表...');
        $this->dropTables();

        // 2. 删除迁移文件
        $this->info('2. 删除迁移文件...');
        $this->deleteMigrations();

        // 3. 删除配置文件
        $this->info('3. 删除配置文件...');
        $this->deleteConfig();

        // 4. 删除前端资源
        $this->info('4. 删除前端资源...');
        $this->deleteAssets();

        $this->newLine();
        $this->info('========================================');
        $this->info('       Lartrix 卸载完成！');
        $this->info('========================================');
        $this->newLine();
        $this->line('如需重新安装，请运行: php artisan lartrix:install');

        return self::SUCCESS;
    }

    /**
     * 删除数据表
     */
    protected function dropTables(): void
    {
        // 禁用外键检查
        Schema::disableForeignKeyConstraints();

        $droppedTables = [];
        $skippedTables = [];

        // 删除 Lartrix 表
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $droppedTables[] = $table;
            } else {
                $skippedTables[] = $table;
            }
        }

        // 询问是否删除 Permission 表
        $dropPermissionTables = $this->option('force') 
            || $this->confirm('是否同时删除 Spatie Permission 相关表？', true);

        if ($dropPermissionTables) {
            foreach ($this->permissionTables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::drop($table);
                    $droppedTables[] = $table;
                } else {
                    $skippedTables[] = $table;
                }
            }
        }

        // 询问是否删除 Sanctum 表
        $dropSanctumTables = $this->option('force') 
            || $this->confirm('是否同时删除 Sanctum 相关表？', true);

        if ($dropSanctumTables) {
            foreach ($this->sanctumTables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::drop($table);
                    $droppedTables[] = $table;
                } else {
                    $skippedTables[] = $table;
                }
            }
        }

        // 清理迁移记录
        $this->cleanMigrationRecords();

        // 启用外键检查
        Schema::enableForeignKeyConstraints();

        if (!empty($droppedTables)) {
            $this->line('   已删除表: ' . implode(', ', $droppedTables));
        }
        if (!empty($skippedTables)) {
            $this->line('   跳过（不存在）: ' . implode(', ', $skippedTables));
        }
    }

    /**
     * 清理迁移记录
     */
    protected function cleanMigrationRecords(): void
    {
        if (!Schema::hasTable('migrations')) {
            return;
        }

        $patterns = [
            '%create_admin_users_table%',
            '%add_fields_to_permission_tables%',
            '%create_admin_menus_table%',
            '%create_admin_settings_table%',
            '%create_modules_table%',
            '%create_permission_tables%',
            '%create_personal_access_tokens_table%',
        ];

        foreach ($patterns as $pattern) {
            DB::table('migrations')->where('migration', 'like', $pattern)->delete();
        }
    }

    /**
     * 删除迁移文件
     */
    protected function deleteMigrations(): void
    {
        $migrationsPath = database_path('migrations');
        $deletedFiles = [];
        $patterns = [
            '*_create_admin_users_table.php',
            '*_add_fields_to_permission_tables.php',
            '*_create_admin_menus_table.php',
            '*_create_admin_settings_table.php',
            '*_create_modules_table.php',
            '*_create_permission_tables.php',
            '*_create_personal_access_tokens_table.php',
        ];

        foreach ($patterns as $pattern) {
            $files = glob("{$migrationsPath}/{$pattern}");
            foreach ($files as $file) {
                if (File::delete($file)) {
                    $deletedFiles[] = basename($file);
                }
            }
        }

        if (!empty($deletedFiles)) {
            $this->line('   已删除迁移文件:');
            foreach ($deletedFiles as $file) {
                $this->line("     - {$file}");
            }
        } else {
            $this->line('   未找到需要删除的迁移文件。');
        }
    }

    /**
     * 删除配置文件
     */
    protected function deleteConfig(): void
    {
        $configFile = config_path('lartrix.php');
        
        if (File::exists($configFile)) {
            File::delete($configFile);
            $this->line('   已删除: config/lartrix.php');
        } else {
            $this->line('   配置文件不存在，跳过。');
        }
    }

    /**
     * 删除前端资源
     */
    protected function deleteAssets(): void
    {
        $assetsPath = public_path('admin');
        
        if (File::isDirectory($assetsPath)) {
            File::deleteDirectory($assetsPath);
            $this->line('   已删除: public/admin/');
        } else {
            $this->line('   前端资源目录不存在，跳过。');
        }
    }
}
