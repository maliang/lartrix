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
    $dictController
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
        $dictController
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
    });
});
