<?php

use Illuminate\Support\Facades\Route;

$prefix = config('lartrix.route_prefix', 'api/admin');

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

Route::prefix($prefix)->group(function () use (
    $authController,
    $userController,
    $roleController,
    $permissionController,
    $menuController,
    $moduleController,
    $settingController,
    $systemController,
    $homeController
) {
    // 公开路由（无需认证）
    Route::post('auth/login', [$authController, 'login']);
    Route::get('login/page', [$systemController, 'loginPage']);
    Route::get('system/theme-config', [$systemController, 'getThemeConfig']);

    // 需要认证的路由
    Route::middleware(['auth:sanctum', \Lartrix\Middleware\Authenticate::class])->group(function () use (
        $authController,
        $userController,
        $roleController,
        $permissionController,
        $menuController,
        $moduleController,
        $settingController,
        $systemController,
        $homeController
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
    });
});
