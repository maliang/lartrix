<?php

use Illuminate\Support\Facades\Route;

$prefix = config('lartrix.api_prefix', 'api/admin');
$path = config('lartrix.path', '/admin');
$guard = config('lartrix.guard', 'admin');

// 从配置获取控制器类
$authController = config('lartrix.controllers.auth', \Lartrix\Controllers\AuthController::class);
$userController = config('lartrix.controllers.user', \Lartrix\Controllers\UserController::class);
$roleController = config('lartrix.controllers.role', \Lartrix\Controllers\RoleController::class);
$permissionController = config('lartrix.controllers.permission', \Lartrix\Controllers\PermissionController::class);
$menuController = config('lartrix.controllers.menu', \Lartrix\Controllers\MenuController::class);
$moduleController = config('lartrix.controllers.module', \Lartrix\Controllers\ModuleController::class);
$settingController = config('lartrix.controllers.setting', \Lartrix\Controllers\SettingController::class);
$systemController = config('lartrix.controllers.system', \Lartrix\Controllers\SystemController::class);
$homeController = config('lartrix.controllers.home', \Lartrix\Controllers\HomeController::class);
$dictController = config('lartrix.controllers.dict', \Lartrix\Controllers\DictController::class);
$notificationCategoryController = \Lartrix\Controllers\NotificationCategoryController::class;
$notificationController = \Lartrix\Controllers\NotificationController::class;
$adminNotificationController = \Lartrix\Controllers\AdminNotificationController::class;

// 前端入口路由（处理 SPA 路由）
Route::get($path . '/{any?}', [$systemController, 'entry'])->where('any', '.*');

Route::prefix($prefix)->group(function () use (
    $guard,
    $authController,
    $userController,
    $roleController,
    $permissionController,
    $menuController,
    $moduleController,
    $settingController,
    $systemController,
    $homeController,
    $dictController,
    $notificationCategoryController,
    $notificationController,
    $adminNotificationController
) {
    // 公开路由（无需认证）
    Route::post('auth/login', [$authController, 'login']);
    Route::get('auth/config', [$authController, 'config']);
    Route::get('login/page', [$systemController, 'loginPage']);
    Route::get('system/theme-config', [$systemController, 'getThemeConfig']);
    Route::get('modules/{name}/logo', [$moduleController, 'logo'])->where('name', '[a-zA-Z0-9_-]+');

    // 需要认证的路由
    Route::middleware(["auth:{$guard}", \Lartrix\Middleware\Authenticate::class])->group(function () use (
        $authController,
        $userController,
        $roleController,
        $permissionController,
        $menuController,
        $moduleController,
        $settingController,
        $systemController,
        $homeController,
        $dictController,
        $notificationCategoryController,
        $notificationController,
        $adminNotificationController
    ) {
        // 认证相关
        Route::prefix('auth')->group(function () use ($authController) {
            Route::post('logout', [$authController, 'logout']);
            Route::post('refresh', [$authController, 'refresh']);
            Route::get('user', [$authController, 'user']);
            Route::get('tokens', [$authController, 'tokens']);
            Route::delete('tokens/{id}', [$authController, 'revokeToken']);
        });

        // 系统配置
        Route::prefix('system')->group(function () use ($systemController) {
            Route::post('theme-config', [$systemController, 'saveThemeConfig']);
        });

        // 布局相关
        Route::prefix('layout')->group(function () use ($systemController) {
            Route::get('header-right', [$systemController, 'headerRight']);
        });

        // 首页仪表盘
        Route::get('dashboard', [$homeController, 'dashboard']);

        // 用户管理 - 使用 resource 路由
        Route::resource('users', $userController)->parameters(['users' => 'id'])->except(['create', 'edit']);

        // 角色管理 - 使用 resource 路由
        Route::resource('roles', $roleController)->parameters(['roles' => 'id'])->except(['create', 'edit']);

        // 权限管理 - 使用 resource 路由
        Route::resource('permissions', $permissionController)->parameters(['permissions' => 'id'])->except(['create', 'edit']);

        // 菜单管理 - 使用 resource 路由
        Route::resource('menus', $menuController)->parameters(['menus' => 'id'])->except(['create', 'edit']);

        // 模块管理
        Route::prefix('modules')->group(function () use ($moduleController) {
            Route::get('/', [$moduleController, 'index']);
            Route::put('{name}/enable', [$moduleController, 'enable']);
            Route::put('{name}/disable', [$moduleController, 'disable']);
            Route::put('{name}/install', [$moduleController, 'install']);
            Route::put('{name}/uninstall', [$moduleController, 'uninstall']);
        });

        // 设置管理
        Route::prefix('settings')->group(function () use ($settingController) {
            Route::get('/', [$settingController, 'index']);
            Route::get('{group}', [$settingController, 'group'])->where('group', '[a-zA-Z_]+');
            Route::put('/', [$settingController, 'update']);
        });

        // 字典管理
        Route::prefix('dicts')->group(function () use ($dictController) {
            // 字典选项（供前端 select 使用）
            Route::get('options/{code}', [$dictController, 'options'])->where('code', '[a-zA-Z_]+');
            Route::post('options/batch', [$dictController, 'batchOptions']);

            // 字典分组管理
            Route::get('groups', [$dictController, 'groups']);
            Route::post('groups', [$dictController, 'createGroup']);
            Route::get('groups/{id}', [$dictController, 'showGroup'])->where('id', '[0-9]+');
            Route::put('groups/{id}', [$dictController, 'updateGroup'])->where('id', '[0-9]+');
            Route::delete('groups/{id}', [$dictController, 'deleteGroup'])->where('id', '[0-9]+');

            // 字典项管理
            Route::get('groups/{groupId}/items', [$dictController, 'items'])->where('groupId', '[0-9]+');
            Route::post('groups/{groupId}/items', [$dictController, 'createItem'])->where('groupId', '[0-9]+');
            Route::get('groups/{groupId}/items/{id}', [$dictController, 'showItem'])->where(['groupId' => '[0-9]+', 'id' => '[0-9]+']);
            Route::put('groups/{groupId}/items/{id}', [$dictController, 'updateItem'])->where(['groupId' => '[0-9]+', 'id' => '[0-9]+']);
            Route::delete('groups/{groupId}/items/{id}', [$dictController, 'deleteItem'])->where(['groupId' => '[0-9]+', 'id' => '[0-9]+']);
            Route::post('groups/{groupId}/items/sort', [$dictController, 'sortItems'])->where('groupId', '[0-9]+');
        });

        // 通知分类管理
        Route::resource('notification-categories', $notificationCategoryController)
            ->parameters(['notification-categories' => 'id'])
            ->except(['create', 'edit']);

        // 通知消息管理
        Route::resource('notifications', $notificationController)
            ->parameters(['notifications' => 'id'])
            ->except(['create', 'edit']);

        // 通知操作
        Route::post('notifications/{id}/mark-read', [$notificationController, 'markAsRead']);
        Route::post('notifications/mark-all-read', [$notificationController, 'markAllAsRead']);
        Route::get('notifications/poll', [$notificationController, 'poll']);

        // 主后台发送通知给二级后台
        Route::prefix('admin')->group(function () use ($adminNotificationController) {
            Route::post('notifications/send-to-backend', [$adminNotificationController, 'sendToBackend']);
            Route::get('notifications/sent', [$adminNotificationController, 'sentNotifications']);
            Route::get('notifications/available-guards', [$adminNotificationController, 'availableGuards']);
            Route::get('notifications/categories', [$adminNotificationController, 'categories']);
        });

        // 获取当前用户的通知分类配置（前端 HeaderNotification 使用）
        Route::get('notification/tabs', function () {
            $guard = config('lartrix.guard', 'admin');
            $categories = \Lartrix\Models\NotificationCategory::query()
                ->where('guard_name', $guard)
                ->where('enabled', true)
                ->orderBy('sort')
                ->get()
                ->map(fn($c) => [
                    'key' => $c->key,
                    'label' => $c->name,
                    'icon' => $c->icon,
                    'color' => $c->color,
                    'types' => $c->message_types ?? [],
                ]);

            // 添加"全部"选项
            $allTab = ['key' => 'all', 'label' => '全部', 'icon' => 'ph:bell', 'color' => null, 'types' => []];
            $categories->prepend($allTab);

            return success($categories->toArray());
        });
    });
});
