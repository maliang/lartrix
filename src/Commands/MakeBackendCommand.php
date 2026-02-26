<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakeBackendCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'lartrix:make-backend
                            {name : 模块名称 (StudlyCase, 如: Merchant)}
                            {--path= : 前端访问路径}
                            {--api-prefix= : API 接口前缀}
                            {--table= : 用户表名}
                            {--title= : 后台标题}';

    /**
     * 命令描述
     */
    protected $description = '创建一个新的后台模块（基于 nwidart/laravel-modules）';

    /**
     * 模块名称
     */
    protected string $moduleName;

    /**
     * 模块名称（小写）
     */
    protected string $lowerName;

    /**
     * 替换变量
     */
    protected array $replacements = [];

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->lowerName = Str::lower($this->moduleName);

        // 检查模块是否已存在
        if (File::isDirectory($this->getModulePath())) {
            $this->error("模块 [{$this->moduleName}] 已存在！");
            return self::FAILURE;
        }

        // 准备替换变量
        $this->prepareReplacements();

        // 显示配置信息
        $this->displayConfig();

        if (!$this->confirm('确认创建此后台模块？', true)) {
            $this->info('已取消。');
            return self::SUCCESS;
        }

        // 1. 使用 nwidart/laravel-modules 创建基础模块
        $this->info('1. 创建基础模块...');
        $result = Artisan::call('module:make', ['name' => [$this->moduleName]]);
        if ($result !== 0) {
            $this->error('模块创建失败！');
            return self::FAILURE;
        }
        $this->info('   基础模块创建完成。');

        // 2. 清理多余文件
        $this->info('2. 清理多余文件...');
        $this->cleanupDefaultFiles();
        $this->info('   清理完成。');

        // 3. 生成后台专用文件
        $this->info('3. 生成后台文件...');
        $this->generateBackendFiles();
        $this->info('   后台文件生成完成。');

        // 4. 配置 auth.php
        $this->info('4. 配置 auth.php...');
        $this->configureAuth();

        // 5. 运行迁移（显式指定模块迁移路径，因为模块 ServiceProvider 在当前进程中尚未注册）
        $this->info('5. 运行数据库迁移...');
        $migrationPath = 'Modules/' . $this->moduleName . '/database/migrations';
        $migrateResult = Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--force' => true,
        ]);
        if ($migrateResult === 0) {
            $this->info('   迁移完成。');
        } else {
            $this->warn('   迁移可能已执行过，继续...');
        }

        // 6. 运行 Seeder
        $this->info('6. 初始化数据（菜单、权限、角色、管理员）...');
        $this->runSeeder();
        $this->info('   数据初始化完成。');

        // 7. 确保模块已启用
        $this->info('7. 确认模块状态...');
        $this->ensureModuleEnabled();

        // 输出摘要
        $this->newLine();
        $this->info('========================================');
        $this->info("  后台模块 [{$this->moduleName}] 创建成功！");
        $this->info('========================================');
        $this->newLine();
        $this->table(
            ['配置项', '值'],
            [
                ['前端路径', $this->replacements['{{PATH}}']],
                ['API 前缀', $this->replacements['{{API_PREFIX}}']],
                ['Guard', $this->replacements['{{GUARD}}']],
                ['用户表', $this->replacements['{{TABLE}}']],
                ['管理员账号', 'admin / 123456'],
            ]
        );
        $this->newLine();
        $this->info("前端资源共享 public/admin/，通过 {$this->replacements['{{PATH}}']} 访问。");

        return self::SUCCESS;
    }

    /**
     * 获取模块路径
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = base_path('Modules/' . $this->moduleName);
        return $path ? $basePath . '/' . $path : $basePath;
    }

    /**
     * 准备替换变量
     */
    protected function prepareReplacements(): void
    {
        $path = $this->option('path') ?: '/' . $this->lowerName;
        $apiPrefix = $this->option('api-prefix') ?: 'api/' . $this->lowerName;
        $table = $this->option('table') ?: $this->lowerName . 's';
        $title = $this->option('title') ?: $this->moduleName . '管理系统';
        $guard = $this->lowerName;

        $this->replacements = [
            '{{MODULE_NAME}}' => $this->moduleName,
            '{{LOWER_NAME}}' => $this->lowerName,
            '{{MODEL_NAME}}' => $this->moduleName,
            '{{PATH}}' => $path,
            '{{API_PREFIX}}' => $apiPrefix,
            '{{TABLE}}' => $table,
            '{{APP_TITLE}}' => $title,
            '{{GUARD}}' => $guard,
        ];
    }

    /**
     * 显示配置信息
     */
    protected function displayConfig(): void
    {
        $this->newLine();
        $this->info('即将创建后台模块：');
        $this->table(
            ['配置项', '值'],
            [
                ['模块名称', $this->moduleName],
                ['前端路径', $this->replacements['{{PATH}}']],
                ['API 前缀', $this->replacements['{{API_PREFIX}}']],
                ['用户表', $this->replacements['{{TABLE}}']],
                ['后台标题', $this->replacements['{{APP_TITLE}}']],
                ['Guard', $this->replacements['{{GUARD}}']],
            ]
        );
    }

    /**
     * 清理 module:make 生成的多余文件
     */
    protected function cleanupDefaultFiles(): void
    {
        $toDelete = [
            // 默认控制器
            'app/Http/Controllers/' . $this->moduleName . 'Controller.php',
            // 前端相关
            'vite.config.js',
            'package.json',
            // 路由
            'routes/web.php',
        ];

        foreach ($toDelete as $file) {
            $fullPath = $this->getModulePath($file);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }

        // 删除多余目录
        $dirsToDelete = [
            'resources/views',
            'resources/assets',
            'resources',
            'database/factories',
            'tests',
        ];

        foreach ($dirsToDelete as $dir) {
            $fullPath = $this->getModulePath($dir);
            if (File::isDirectory($fullPath)) {
                File::deleteDirectory($fullPath);
            }
        }
    }

    /**
     * 生成后台专用文件
     */
    protected function generateBackendFiles(): void
    {
        $stubPath = __DIR__ . '/../../stubs/backend/';

        $files = [
            'config.stub' => 'config/config.php',
            'model.stub' => "app/Models/{$this->moduleName}.php",
            'migration.stub' => 'database/migrations/' . date('Y_m_d_His') . "_create_{$this->replacements['{{TABLE}}']}_table.php",
            'seeder.stub' => "database/seeders/{$this->moduleName}BackendSeeder.php",
            'routes.stub' => 'routes/api.php',
            'auth_controller.stub' => 'app/Http/Controllers/AuthController.php',
            'system_controller.stub' => 'app/Http/Controllers/SystemController.php',
            'menu_controller.stub' => 'app/Http/Controllers/MenuController.php',
            'role_controller.stub' => 'app/Http/Controllers/RoleController.php',
            'permission_controller.stub' => 'app/Http/Controllers/PermissionController.php',
            'user_controller.stub' => 'app/Http/Controllers/UserController.php',
            'service_provider.stub' => "app/Providers/{$this->moduleName}ServiceProvider.php",
            'route_service_provider.stub' => 'app/Providers/RouteServiceProvider.php',
            'module.json.stub' => 'module.json',
        ];

        foreach ($files as $stub => $target) {
            $stubFile = $stubPath . $stub;
            if (!File::exists($stubFile)) {
                $this->warn("   存根文件不存在: {$stub}，跳过。");
                continue;
            }

            $content = File::get($stubFile);
            $content = str_replace(
                array_keys($this->replacements),
                array_values($this->replacements),
                $content
            );

            $targetPath = $this->getModulePath($target);
            $targetDir = dirname($targetPath);
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }

            File::put($targetPath, $content);
        }

        // 删除 module:make 生成的默认 composer.json（后台模块不需要）
        $composerJson = $this->getModulePath('composer.json');
        if (File::exists($composerJson)) {
            File::delete($composerJson);
        }
    }

    /**
     * 配置 auth.php
     */
    protected function configureAuth(): void
    {
        $authPath = config_path('auth.php');

        if (!File::exists($authPath)) {
            $this->warn('   auth.php 不存在，跳过。');
            return;
        }

        $content = File::get($authPath);
        $guard = $this->replacements['{{GUARD}}'];
        $modelClass = "Modules\\{$this->moduleName}\\Models\\{$this->moduleName}";
        $changed = false;

        // 添加 guard
        if (!str_contains($content, "'{$guard}' =>")) {
            $guardBlock = "\n        '{$guard}' => [\n            'driver' => 'sanctum',\n            'provider' => '{$guard}s',\n        ],";
            $content = $this->insertIntoArraySection($content, 'guards', $guardBlock);
            if ($content !== false) {
                $this->info("   已添加 {$guard} guard。");
                $changed = true;
            }
        } else {
            $this->line("   {$guard} guard 已存在，跳过。");
        }

        // 添加 provider
        $providerName = $guard . 's';
        if ($content !== false && !str_contains($content, "'{$providerName}' =>")) {
            $providerBlock = "\n        '{$providerName}' => [\n            'driver' => 'eloquent',\n            'model' => \\{$modelClass}::class,\n        ],";
            $content = $this->insertIntoArraySection($content, 'providers', $providerBlock);
            if ($content !== false) {
                $this->info("   已添加 {$providerName} provider。");
                $changed = true;
            }
        } else if ($content !== false) {
            $this->line("   {$providerName} provider 已存在，跳过。");
        }

        if ($changed && $content !== false) {
            File::put($authPath, $content);
            $this->info('   auth.php 配置完成。');
        }
    }

    /**
     * 在 auth.php 的指定数组段落末尾插入内容
     * 通过逐字符解析括号匹配，找到 'key' => [ ... ] 中最后一个子项 ], 的位置
     */
    protected function insertIntoArraySection(string $content, string $sectionKey, string $insertBlock): string|false
    {
        // 找到 'guards' => [ 或 'providers' => [ 的位置
        $pattern = "/'{$sectionKey}'\s*=>\s*\[/";
        if (!preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        // 找到开头 [ 的位置
        $openBracketPos = strpos($content, '[', $match[0][1]);
        if ($openBracketPos === false) {
            return false;
        }

        // 从 [ 开始，用括号计数找到匹配的 ]
        $depth = 0;
        $closeBracketPos = null;
        $len = strlen($content);

        for ($i = $openBracketPos; $i < $len; $i++) {
            if ($content[$i] === '[') {
                $depth++;
            } elseif ($content[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    $closeBracketPos = $i;
                    break;
                }
            }
        }

        if ($closeBracketPos === null) {
            return false;
        }

        // 在闭合 ] 前插入新内容
        $before = substr($content, 0, $closeBracketPos);
        $after = substr($content, $closeBracketPos);

        return $before . $insertBlock . "\n    " . $after;
    }

    /**
     * 运行 Seeder
     */
    protected function runSeeder(): void
    {
        // 模块刚创建，Composer autoloader 尚未更新，需手动加载依赖文件
        $filesToLoad = [
            "app/Models/{$this->moduleName}.php",
            "database/seeders/{$this->moduleName}BackendSeeder.php",
        ];

        foreach ($filesToLoad as $file) {
            $fullPath = $this->getModulePath($file);
            if (File::exists($fullPath)) {
                require_once $fullPath;
            }
        }

        $seederClass = "Modules\\{$this->moduleName}\\Database\\Seeders\\{$this->moduleName}BackendSeeder";

        if (class_exists($seederClass)) {
            $seeder = new $seederClass();
            $seeder->run();
        } else {
            $this->warn("   Seeder 类 [{$seederClass}] 未找到，跳过数据初始化。");
            $this->line("   请手动运行: php artisan module:seed {$this->moduleName}");
        }
    }

    /**
     * 确保模块已启用
     */
    protected function ensureModuleEnabled(): void
    {
        try {
            $module = app('modules')->find($this->moduleName);
            if ($module && !$module->isEnabled()) {
                Artisan::call('module:enable', ['module' => $this->moduleName]);
                $this->info("   模块 [{$this->moduleName}] 已启用。");
            } else {
                $this->info("   模块 [{$this->moduleName}] 状态正常。");
            }
        } catch (\Exception $e) {
            // 手动写入 modules_statuses.json
            $statusFile = base_path('modules_statuses.json');
            $statuses = [];
            if (File::exists($statusFile)) {
                $statuses = json_decode(File::get($statusFile), true) ?: [];
            }
            $statuses[$this->moduleName] = true;
            File::put($statusFile, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("   模块 [{$this->moduleName}] 已启用。");
        }
    }
}
