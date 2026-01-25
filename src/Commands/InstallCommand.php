<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Menu;
use Lartrix\Models\Permission;
use Lartrix\Models\Role;

class InstallCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'lartrix:install {--force : 强制覆盖已有数据}';

    /**
     * 命令描述
     */
    protected $description = '安装 Lartrix 后台管理系统';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $this->info('开始安装 Lartrix...');
        $this->newLine();

        // 检测已有数据
        if (!$this->option('force') && $this->hasExistingData()) {
            if (!$this->confirm('检测到已有数据，是否继续安装？这可能会覆盖现有数据。')) {
                $this->warn('安装已取消。');
                return self::FAILURE;
            }
        }

        // 发布前端资源
        $this->info('1. 发布前端资源到 public/admin...');
        $this->publishAssets();
        $this->info('   前端资源发布完成。');

        // 发布依赖包的迁移文件
        $this->info('2. 发布依赖包配置和迁移...');
        $this->publishDependencies();
        $this->info('   依赖包发布完成。');

        // 配置 composer merge-plugin（用于模块化开发）
        $this->info('3. 配置 composer.json...');
        $this->configureComposerMergePlugin();
        $this->info('   composer.json 配置完成。');

        // 执行数据库迁移
        $this->info('4. 执行数据库迁移...');
        $migrateResult = Artisan::call('migrate', ['--force' => true]);
        if ($migrateResult === 0) {
            $this->info('   迁移完成。');
        } else {
            $this->warn('   迁移可能已执行过，继续安装...');
        }

        // 创建超级管理员角色
        $this->info('5. 创建超级管理员角色...');
        $superAdminRole = $this->createSuperAdminRole();
        $this->info('   角色创建完成。');

        // 创建基础权限
        $this->info('6. 创建基础权限...');
        $this->createBasePermissions();
        $this->info('   权限创建完成。');

        // 为超级管理员分配所有权限
        $superAdminRole->syncPermissions(Permission::all());

        // 初始化系统设置
        $this->info('7. 初始化系统设置...');
        $this->initializeSettings();
        $this->info('   系统设置初始化完成。');

        // 创建默认菜单
        $this->info('8. 创建默认菜单...');
        $this->createDefaultMenus();
        $this->info('   默认菜单创建完成。');

        // 交互式创建超级管理员账户
        $this->info('9. 创建超级管理员账户...');
        $admin = $this->createSuperAdmin($superAdminRole);

        // 创建 AI 开发指南文件
        $this->info('10. 创建 AI 开发指南文件...');
        $this->createAiGuideFiles();
        $this->info('   AI 开发指南文件创建完成。');

        // 输出安装摘要
        $this->newLine();
        $this->info('========================================');
        $this->info('       Lartrix 安装完成！');
        $this->info('========================================');
        $this->newLine();
        $this->table(
            ['项目', '值'],
            [
                ['前端资源目录', public_path('admin')],
                ['超级管理员角色', $superAdminRole->name],
                ['管理员用户名', $admin->name],
                ['管理员邮箱', $admin->email],
                ['权限数量', Permission::count()],
            ]
        );
        $this->newLine();
        $this->info('请访问 /admin/ 进入后台管理系统。');
        $this->info('AI 开发指南：AGENTS.md 和 CLAUDE.md 已创建在项目根目录。');

        return self::SUCCESS;
    }

    /**
     * 检测是否有已有数据
     */
    protected function hasExistingData(): bool
    {
        try {
            return AdminUser::exists() || Role::exists() || Permission::exists();
        } catch (\Exception $e) {
            // 表不存在时返回 false
            return false;
        }
    }

    /**
     * 发布前端资源
     */
    protected function publishAssets(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'lartrix-assets',
            '--force' => true,
        ]);
    }

    /**
     * 发布依赖包的配置和迁移文件
     */
    protected function publishDependencies(): void
    {
        // 发布 spatie/laravel-permission 配置和迁移
        $permissionConfig = config_path('permission.php');
        if (!file_exists($permissionConfig)) {
            Artisan::call('vendor:publish', [
                '--provider' => \Spatie\Permission\PermissionServiceProvider::class,
                '--tag' => 'permission-config',
            ]);
            $this->line('   发布 permission.php 配置文件。');
        }
        
        $permissionMigrations = glob(database_path('migrations/*_create_permission_tables.php'));
        if (empty($permissionMigrations)) {
            Artisan::call('vendor:publish', [
                '--provider' => \Spatie\Permission\PermissionServiceProvider::class,
                '--tag' => 'permission-migrations',
            ]);
            $this->line('   发布 permission 迁移文件。');
        }

        // 发布 laravel/sanctum 配置和迁移
        $sanctumConfig = config_path('sanctum.php');
        if (!file_exists($sanctumConfig)) {
            Artisan::call('vendor:publish', [
                '--provider' => \Laravel\Sanctum\SanctumServiceProvider::class,
                '--tag' => 'sanctum-config',
            ]);
            $this->line('   发布 sanctum.php 配置文件。');
        }
        
        $sanctumMigrations = glob(database_path('migrations/*_create_personal_access_tokens_table.php'));
        if (empty($sanctumMigrations)) {
            Artisan::call('vendor:publish', [
                '--provider' => \Laravel\Sanctum\SanctumServiceProvider::class,
                '--tag' => 'sanctum-migrations',
            ]);
            $this->line('   发布 sanctum 迁移文件。');
        }

        // 发布 nwidart/laravel-modules 配置（如果已安装）
        if (class_exists(\Nwidart\Modules\LaravelModulesServiceProvider::class)) {
            $modulesConfig = config_path('modules.php');
            if (!file_exists($modulesConfig)) {
                Artisan::call('vendor:publish', [
                    '--provider' => \Nwidart\Modules\LaravelModulesServiceProvider::class,
                    '--tag' => 'config',
                ]);
                $this->line('   发布 modules.php 配置文件。');
            }
        }

        // 生成 lartrix 迁移文件（时间戳在依赖包迁移之后）
        $this->generateLartrixMigrations();

        // 发布 lartrix 配置文件
        Artisan::call('vendor:publish', [
            '--tag' => 'lartrix-config',
            '--force' => true,
        ]);
    }

    /**
     * 配置 composer merge-plugin
     * 用于自动合并模块的 composer.json
     */
    protected function configureComposerMergePlugin(): void
    {
        $composerPath = base_path('composer.json');
        
        if (!file_exists($composerPath)) {
            $this->warn('   composer.json 不存在，跳过配置。');
            return;
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('   composer.json 解析失败，跳过配置。');
            return;
        }

        // 检查是否已配置
        $mergePlugin = $composer['extra']['merge-plugin'] ?? null;
        $hasModulesInclude = false;
        
        if ($mergePlugin && isset($mergePlugin['include'])) {
            foreach ($mergePlugin['include'] as $include) {
                if (str_contains($include, 'Modules/*/composer.json')) {
                    $hasModulesInclude = true;
                    break;
                }
            }
        }

        if ($hasModulesInclude) {
            $this->line('   merge-plugin 已配置，跳过。');
            return;
        }

        // 添加配置
        if (!isset($composer['extra'])) {
            $composer['extra'] = [];
        }
        
        if (!isset($composer['extra']['merge-plugin'])) {
            $composer['extra']['merge-plugin'] = [];
        }
        
        if (!isset($composer['extra']['merge-plugin']['include'])) {
            $composer['extra']['merge-plugin']['include'] = [];
        }

        $composer['extra']['merge-plugin']['include'][] = 'Modules/*/composer.json';

        // 写回文件
        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($composerPath, $json . "\n");

        $this->line('   已添加 merge-plugin 配置。');
        $this->warn('   请运行 composer update 使配置生效。');
    }

    /**
     * 生成 lartrix 迁移文件
     */
    protected function generateLartrixMigrations(): void
    {
        $stubsPath = __DIR__ . '/../../stubs/migrations';
        
        if (!is_dir($stubsPath)) {
            // 使用旧的迁移目录
            $stubsPath = __DIR__ . '/../../database/migrations';
        }

        $migrations = [
            'create_admin_users_table',
            'add_fields_to_permission_tables',
            'create_admin_menus_table',
            'create_admin_settings_table',
            'create_modules_table',
        ];

        $baseTime = time() + 2; // 确保在 spatie/sanctum 迁移之后

        foreach ($migrations as $index => $migration) {
            $timestamp = date('Y_m_d_His', $baseTime + $index);
            $filename = "{$timestamp}_{$migration}.php";
            $targetPath = database_path("migrations/{$filename}");

            // 检查是否已存在类似迁移
            $existingMigrations = glob(database_path("migrations/*_{$migration}.php"));
            if (!empty($existingMigrations)) {
                continue; // 已存在，跳过
            }

            // 查找存根文件
            $stubFile = "{$stubsPath}/{$migration}.php.stub";
            if (!file_exists($stubFile)) {
                // 尝试旧格式
                $oldFiles = glob("{$stubsPath}/*_{$migration}.php");
                if (!empty($oldFiles)) {
                    $stubFile = $oldFiles[0];
                } else {
                    continue;
                }
            }

            copy($stubFile, $targetPath);
        }
    }

    /**
     * 创建超级管理员角色
     */
    protected function createSuperAdminRole(): Role
    {
        $roleName = config('lartrix.permission.super_admin_role', 'super-admin');

        return Role::updateOrCreate(
            ['name' => $roleName, 'guard_name' => 'sanctum'],
            [
                'title' => '超级管理员',
                'description' => '拥有所有权限的超级管理员',
                'status' => true,
                'is_system' => true,
            ]
        );
    }

    /**
     * 创建基础权限（带层级结构）
     */
    protected function createBasePermissions(): void
    {
        // 定义权限树结构
        $permissionTree = [
            // 用户管理
            [
                'name' => 'user',
                'title' => '用户管理',
                'module' => 'system',
                'sort' => 1,
                'children' => [
                    ['name' => 'user.list', 'title' => '用户列表', 'sort' => 1],
                    ['name' => 'user.create', 'title' => '创建用户', 'sort' => 2],
                    ['name' => 'user.update', 'title' => '编辑用户', 'sort' => 3],
                    ['name' => 'user.delete', 'title' => '删除用户', 'sort' => 4],
                    ['name' => 'user.status', 'title' => '修改状态', 'sort' => 5],
                    ['name' => 'user.password', 'title' => '重置密码', 'sort' => 6],
                ],
            ],
            // 角色管理
            [
                'name' => 'role',
                'title' => '角色管理',
                'module' => 'system',
                'sort' => 2,
                'children' => [
                    ['name' => 'role.list', 'title' => '角色列表', 'sort' => 1],
                    ['name' => 'role.create', 'title' => '创建角色', 'sort' => 2],
                    ['name' => 'role.update', 'title' => '编辑角色', 'sort' => 3],
                    ['name' => 'role.delete', 'title' => '删除角色', 'sort' => 4],
                    ['name' => 'role.permissions', 'title' => '分配权限', 'sort' => 5],
                ],
            ],
            // 权限管理
            [
                'name' => 'permission',
                'title' => '权限管理',
                'module' => 'system',
                'sort' => 3,
                'children' => [
                    ['name' => 'permission.list', 'title' => '权限列表', 'sort' => 1],
                    ['name' => 'permission.create', 'title' => '创建权限', 'sort' => 2],
                    ['name' => 'permission.update', 'title' => '编辑权限', 'sort' => 3],
                    ['name' => 'permission.delete', 'title' => '删除权限', 'sort' => 4],
                ],
            ],
            // 菜单管理
            [
                'name' => 'menu',
                'title' => '菜单管理',
                'module' => 'system',
                'sort' => 4,
                'children' => [
                    ['name' => 'menu.list', 'title' => '菜单列表', 'sort' => 1],
                    ['name' => 'menu.create', 'title' => '创建菜单', 'sort' => 2],
                    ['name' => 'menu.update', 'title' => '编辑菜单', 'sort' => 3],
                    ['name' => 'menu.delete', 'title' => '删除菜单', 'sort' => 4],
                    ['name' => 'menu.sort', 'title' => '菜单排序', 'sort' => 5],
                ],
            ],
            // 模块管理
            [
                'name' => 'module',
                'title' => '模块管理',
                'module' => 'system',
                'sort' => 5,
                'children' => [
                    ['name' => 'module.list', 'title' => '模块列表', 'sort' => 1],
                    ['name' => 'module.enable', 'title' => '启用模块', 'sort' => 2],
                    ['name' => 'module.disable', 'title' => '禁用模块', 'sort' => 3],
                ],
            ],
            // 设置管理
            [
                'name' => 'setting',
                'title' => '系统设置',
                'module' => 'system',
                'sort' => 6,
                'children' => [
                    ['name' => 'setting.list', 'title' => '设置列表', 'sort' => 1],
                    ['name' => 'setting.update', 'title' => '更新设置', 'sort' => 2],
                ],
            ],
        ];

        // 递归创建权限
        $this->createPermissionsRecursive($permissionTree);
    }

    /**
     * 递归创建权限
     */
    protected function createPermissionsRecursive(array $permissions, ?int $parentId = null, ?string $module = null): void
    {
        foreach ($permissions as $permission) {
            $children = $permission['children'] ?? [];
            unset($permission['children']);

            // 继承父级的 module
            $permission['module'] = $permission['module'] ?? $module;
            $permission['parent_id'] = $parentId;
            $permission['guard_name'] = 'sanctum';

            $created = Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'sanctum'],
                $permission
            );

            // 递归创建子权限
            if (!empty($children)) {
                $this->createPermissionsRecursive($children, $created->id, $permission['module']);
            }
        }
    }

    /**
     * 交互式创建超级管理员账户
     */
    protected function createSuperAdmin(Role $role): AdminUser
    {
        $name = $this->ask('请输入管理员用户名', 'admin');
        $email = $this->ask('请输入管理员邮箱', 'admin@example.com');
        $password = $this->secret('请输入管理员密码（至少6位）') ?: 'password';

        while (strlen($password) < 6) {
            $this->error('密码长度至少6位，请重新输入。');
            $password = $this->secret('请输入管理员密码（至少6位）') ?: 'password';
        }

        $admin = AdminUser::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'status' => true,
            ]
        );

        $admin->syncRoles([$role->name]);

        return $admin;
    }

    /**
     * 初始化系统设置
     */
    protected function initializeSettings(): void
    {
        $settingModel = config('lartrix.models.setting', \Lartrix\Models\Setting::class);

        // 主题配置（与 trix 前端 themeSettings 保持一致）
        $themeConfig = [
            'appTitle' => config('lartrix.app_title', 'Lartrix Admin'),
            'logo' => config('lartrix.logo', '/favicon.svg'),
            'themeScheme' => 'light',
            'grayscale' => false,
            'colourWeakness' => false,
            'recommendColor' => false,
            'themeColor' => '#646cff',
            'themeRadius' => 6,
            'otherColor' => [
                'info' => '#2080f0',
                'success' => '#52c41a',
                'warning' => '#faad14',
                'error' => '#f5222d',
            ],
            'isInfoFollowPrimary' => true,
            'layout' => [
                'mode' => 'vertical',
                'scrollMode' => 'content',
            ],
            'page' => [
                'animate' => true,
                'animateMode' => 'fade-slide',
            ],
            'header' => [
                'height' => 56,
                'inverted' => false,
                'breadcrumb' => [
                    'visible' => true,
                    'showIcon' => true,
                ],
                'multilingual' => [
                    'visible' => true,
                ],
                'globalSearch' => [
                    'visible' => true,
                ],
            ],
            'tab' => [
                'visible' => true,
                'cache' => true,
                'height' => 44,
                'mode' => 'chrome',
                'closeTabByMiddleClick' => false,
            ],
            'fixedHeaderAndTab' => true,
            'sider' => [
                'inverted' => false,
                'width' => 220,
                'collapsedWidth' => 64,
                'mixWidth' => 90,
                'mixCollapsedWidth' => 64,
                'mixChildMenuWidth' => 200,
                'mixChildMenuBgColor' => '#ffffff',
                'autoSelectFirstMenu' => false,
            ],
            'footer' => [
                'visible' => true,
                'fixed' => false,
                'height' => 48,
                'right' => true,
            ],
            'watermark' => [
                'visible' => false,
                'text' => config('lartrix.app_title', 'Lartrix Admin'),
                'enableUserName' => false,
                'enableTime' => false,
                'timeFormat' => 'YYYY-MM-DD HH:mm',
            ],
            'tokens' => [
                'light' => [
                    'colors' => [
                        'container' => 'rgb(255, 255, 255)',
                        'layout' => 'rgb(247, 250, 252)',
                        'inverted' => 'rgb(0, 20, 40)',
                        'base-text' => 'rgb(31, 31, 31)',
                    ],
                    'boxShadow' => [
                        'header' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                        'sider' => '2px 0 8px 0 rgb(29, 35, 41, 0.05)',
                        'tab' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                    ],
                ],
                'dark' => [
                    'colors' => [
                        'container' => 'rgb(28, 28, 28)',
                        'layout' => 'rgb(18, 18, 18)',
                        'base-text' => 'rgb(224, 224, 224)',
                    ],
                ],
            ],
        ];

        // 保存主题配置（以 JSON 字符串形式存储）
        $settingModel::set('theme', $themeConfig);
    }

    /**
     * 创建默认菜单
     */
    protected function createDefaultMenus(): void
    {
        $menus = $this->getDefaultMenus();
        
        foreach ($menus as $menu) {
            $this->createMenu($menu);
        }
    }

    /**
     * 递归创建菜单
     */
    protected function createMenu(array $menuData, ?int $parentId = null): void
    {
        $children = $menuData['children'] ?? [];
        unset($menuData['children']);

        // 提取 meta 数据
        $meta = $menuData['meta'] ?? [];
        unset($menuData['meta']);

        // 构建菜单数据
        $data = [
            'parent_id' => $parentId,
            'name' => $menuData['name'],
            'path' => $menuData['path'],
            'component' => $menuData['component'] ?? null,
            'redirect' => $menuData['redirect'] ?? null,
            'title' => $meta['title'] ?? null,
            'icon' => $meta['icon'] ?? null,
            'order' => $meta['order'] ?? 0,
            'hide_in_menu' => $meta['hideInMenu'] ?? false,
            'keep_alive' => $meta['keepAlive'] ?? false,
            'requires_auth' => $meta['requiresAuth'] ?? true,
            'use_json_renderer' => $meta['useJsonRenderer'] ?? false,
            'schema_source' => $meta['schemaSource'] ?? null,
            'layout_type' => $meta['layoutType'] ?? null,
            'open_type' => $meta['openType'] ?? null,
            'href' => $meta['href'] ?? null,
            'active_menu' => $meta['activeMenu'] ?? null,
            'is_default_after_login' => $meta['isDefaultAfterLogin'] ?? false,
            'fixed_index_in_tab' => $meta['fixedIndexInTab'] ?? null,
            'permissions' => $meta['permissions'] ?? null,
        ];

        $menu = Menu::updateOrCreate(
            ['name' => $data['name']],
            $data
        );

        // 递归创建子菜单
        foreach ($children as $child) {
            $this->createMenu($child, $menu->id);
        }
    }

    /**
     * 创建 AI 开发指南文件
     */
    protected function createAiGuideFiles(): void
    {
        $stubsPath = __DIR__ . '/../../stubs';
        
        // 创建 AGENTS.md
        $agentsStub = "{$stubsPath}/AGENTS.md.stub";
        $agentsTarget = base_path('AGENTS.md');
        
        if (file_exists($agentsStub)) {
            if (!file_exists($agentsTarget) || $this->option('force')) {
                copy($agentsStub, $agentsTarget);
                $this->line('   创建 AGENTS.md');
            } else {
                $this->line('   AGENTS.md 已存在，跳过');
            }
        }
        
        // 创建 CLAUDE.md
        $claudeStub = "{$stubsPath}/CLAUDE.md.stub";
        $claudeTarget = base_path('CLAUDE.md');
        
        if (file_exists($claudeStub)) {
            if (!file_exists($claudeTarget) || $this->option('force')) {
                copy($claudeStub, $claudeTarget);
                $this->line('   创建 CLAUDE.md');
            } else {
                $this->line('   CLAUDE.md 已存在，跳过');
            }
        }
    }

    /**
     * 获取默认菜单数据
     */
    protected function getDefaultMenus(): array
    {
        return [
            // 首页
            [
                'name' => 'home',
                'path' => '/home',
                'meta' => [
                    'title' => '首页',
                    'icon' => 'mdi:home',
                    'order' => 1,
                    'useJsonRenderer' => true,
                    'schemaSource' => '/dashboard',
                ],
            ],
            // 系统管理
            [
                'name' => 'system',
                'path' => '/system',
                'redirect' => '/system/user',
                'meta' => [
                    'title' => '系统管理',
                    'icon' => 'mdi:cog',
                    'order' => 9999,
                ],
                'children' => [
                    [
                        'name' => 'system-user',
                        'path' => 'user',
                        'meta' => [
                            'title' => '成员管理',
                            'icon' => 'mdi:account-group',
                            'order' => 1,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/users?action_type=list_ui',
                        ],
                    ],
                    [
                        'name' => 'system-role',
                        'path' => 'role',
                        'meta' => [
                            'title' => '角色管理',
                            'icon' => 'mdi:account-key',
                            'order' => 2,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/roles?action_type=list_ui',
                        ],
                    ],
                    [
                        'name' => 'system-menu',
                        'path' => 'menu',
                        'meta' => [
                            'title' => '菜单管理',
                            'icon' => 'mdi:menu',
                            'order' => 3,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/menus?action_type=list_ui',
                        ],
                    ],
                    [
                        'name' => 'system-permission',
                        'path' => 'permission',
                        'meta' => [
                            'title' => '权限管理',
                            'icon' => 'mdi:shield-key',
                            'order' => 4,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/permissions?action_type=list_ui',
                        ],
                    ],
                    [
                        'name' => 'system-setting',
                        'path' => 'setting',
                        'meta' => [
                            'title' => '系统设置',
                            'icon' => 'mdi:cog-outline',
                            'order' => 5,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/settings?action_type=form_ui',
                        ],
                    ],
                ],
            ],
            // 模块管理
            [
                'name' => 'module',
                'path' => '/module',
                'redirect' => '/module/market',
                'meta' => [
                    'title' => '模块管理',
                    'icon' => 'mdi:puzzle',
                    'order' => 9998,
                ],
                'children' => [
                    [
                        'name' => 'module-market',
                        'path' => 'market',
                        'meta' => [
                            'title' => '模块市场',
                            'icon' => 'mdi:store',
                            'order' => 1,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/modules?action_type=market_ui',
                        ],
                    ],
                    [
                        'name' => 'module-installed',
                        'path' => 'installed',
                        'meta' => [
                            'title' => '已装模块',
                            'icon' => 'mdi:puzzle-check',
                            'order' => 2,
                            'useJsonRenderer' => true,
                            'schemaSource' => '/modules?action_type=installed_ui',
                        ],
                    ],
                ],
            ],
        ];
    }
}
