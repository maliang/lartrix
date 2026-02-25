<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

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
    protected $description = '创建一个新的后台模块';

    /**
     * 文件系统实例
     */
    protected Filesystem $files;

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
     * 创建命令实例
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->lowerName = Str::lower($this->moduleName);

        // 检查模块是否已存在
        if ($this->moduleExists()) {
            $this->error("模块 [{$this->moduleName}] 已存在！");
            return 1;
        }

        // 准备替换变量
        $this->prepareReplacements();

        // 显示配置信息
        $this->displayConfig();

        if (!$this->confirm('确认创建此后台模块？', true)) {
            $this->info('已取消。');
            return 0;
        }

        // 创建模块目录结构
        $this->createModuleStructure();

        // 生成文件
        $this->generateFiles();

        // 更新 auth.php 配置
        $this->updateAuthConfig();

        $this->newLine();
        $this->info("后台模块 [{$this->moduleName}] 创建成功！");
        $this->newLine();
        $this->line('后续步骤：');
        $this->line("  1. 运行迁移: <comment>php artisan migrate</comment>");
        $this->line("  2. 运行 Seeder: <comment>php artisan module:seed {$this->moduleName}</comment>");
        $this->line("  3. 发布前端资源到 <comment>public{$this->replacements['{{PATH}}']}</comment>");
        $this->newLine();

        return 0;
    }

    /**
     * 检查模块是否存在
     */
    protected function moduleExists(): bool
    {
        return $this->files->isDirectory($this->getModulePath());
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
     * 创建模块目录结构
     */
    protected function createModuleStructure(): void
    {
        $directories = [
            'app/Http/Controllers',
            'app/Models',
            'app/Providers',
            'config',
            'database/migrations',
            'database/seeders',
            'routes',
        ];

        foreach ($directories as $dir) {
            $this->files->makeDirectory($this->getModulePath($dir), 0755, true, true);
        }

        $this->info('目录结构创建完成。');
    }

    /**
     * 生成文件
     */
    protected function generateFiles(): void
    {
        $stubPath = __DIR__ . '/../../stubs/backend/';

        $files = [
            'config.stub' => 'config/config.php',
            'model.stub' => "app/Models/{$this->moduleName}.php",
            'migration.stub' => 'database/migrations/' . date('Y_m_d_His') . "_create_{$this->replacements['{{TABLE}}']}_table.php",
            'seeder.stub' => "database/seeders/{$this->moduleName}BackendSeeder.php",
            'routes.stub' => 'routes/api.php',
            'auth_controller.stub' => 'app/Http/Controllers/AuthController.php',
            'menu_controller.stub' => 'app/Http/Controllers/MenuController.php',
            'role_controller.stub' => 'app/Http/Controllers/RoleController.php',
            'permission_controller.stub' => 'app/Http/Controllers/PermissionController.php',
            'user_controller.stub' => 'app/Http/Controllers/UserController.php',
            'service_provider.stub' => "app/Providers/{$this->moduleName}ServiceProvider.php",
            'route_service_provider.stub' => 'app/Providers/RouteServiceProvider.php',
            'module.json.stub' => 'module.json',
        ];

        foreach ($files as $stub => $target) {
            $content = $this->files->get($stubPath . $stub);
            $content = str_replace(
                array_keys($this->replacements),
                array_values($this->replacements),
                $content
            );
            $this->files->put($this->getModulePath($target), $content);
        }

        $this->info('模块文件生成完成。');
    }

    /**
     * 更新 auth.php 配置
     */
    protected function updateAuthConfig(): void
    {
        $authConfigPath = config_path('auth.php');

        if (!$this->files->exists($authConfigPath)) {
            $this->warn('auth.php 配置文件不存在，请手动配置 guard 和 provider。');
            return;
        }

        $guard = $this->replacements['{{GUARD}}'];
        $table = $this->replacements['{{TABLE}}'];
        $modelClass = "Modules\\{$this->moduleName}\\Models\\{$this->moduleName}";

        $this->newLine();
        $this->warn('请手动在 config/auth.php 中添加以下配置：');
        $this->newLine();
        $this->line("// guards 数组中添加：");
        $this->line("'{$guard}' => [");
        $this->line("    'driver' => 'sanctum',");
        $this->line("    'provider' => '{$guard}s',");
        $this->line("],");
        $this->newLine();
        $this->line("// providers 数组中添加：");
        $this->line("'{$guard}s' => [");
        $this->line("    'driver' => 'eloquent',");
        $this->line("    'model' => \\{$modelClass}::class,");
        $this->line("],");
    }
}
