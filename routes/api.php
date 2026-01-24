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
    
    // 错误页面（无需认证）
    // Route::get('system/403', [$systemController, 'forbidden']);
    // Route::get('system/404', [$systemController, 'notFound']);
    // Route::get('system/500', [$systemController, 'serverError']);

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

        // 用户管理
        Route::prefix('users')->group(function () use ($userController) {
            Route::get('ui/list', [$userController, 'listUi']);
            Route::get('ui/form', [$userController, 'formUi']);
            Route::get('/', [$userController, 'index']);
            Route::post('/', [$userController, 'store']);
            Route::get('export', [$userController, 'export']);
            Route::delete('batch', [$userController, 'batchDestroy']);
            Route::get('{id}', [$userController, 'show']);
            Route::put('{id}', [$userController, 'update']);
            Route::delete('{id}', [$userController, 'destroy']);
            Route::put('{id}/status', [$userController, 'updateStatus']);
            Route::put('{id}/password', [$userController, 'resetPassword']);
        });

        // 角色管理
        Route::prefix('roles')->group(function () use ($roleController) {
            Route::get('ui/list', [$roleController, 'listUi']);
            Route::get('ui/form', [$roleController, 'formUi']);
            Route::get('/', [$roleController, 'index']);
            Route::post('/', [$roleController, 'store']);
            Route::get('{id}', [$roleController, 'show']);
            Route::put('{id}', [$roleController, 'update']);
            Route::delete('{id}', [$roleController, 'destroy']);
            Route::put('{id}/permissions', [$roleController, 'updatePermissions']);
        });

        // 权限管理
        Route::prefix('permissions')->group(function () use ($permissionController) {
            Route::get('ui/list', [$permissionController, 'listUi']);
            Route::get('ui/form', [$permissionController, 'formUi']);
            Route::get('/', [$permissionController, 'index']);
            Route::get('all', [$permissionController, 'all']);
            Route::get('tree', [$permissionController, 'tree']);
            Route::post('/', [$permissionController, 'store']);
            Route::get('{id}', [$permissionController, 'show']);
            Route::put('{id}', [$permissionController, 'update']);
            Route::delete('{id}', [$permissionController, 'destroy']);
        });

        // 菜单管理
        Route::prefix('menus')->group(function () use ($menuController) {
            Route::get('ui/list', [$menuController, 'listUi']);
            Route::get('ui/form', [$menuController, 'formUi']);
            Route::get('/', [$menuController, 'index']);
            Route::get('all', [$menuController, 'all']);
            Route::post('/', [$menuController, 'store']);
            Route::get('{id}', [$menuController, 'show']);
            Route::put('{id}', [$menuController, 'update']);
            Route::delete('{id}', [$menuController, 'destroy']);
            Route::put('sort', [$menuController, 'sort']);
        });

        // 模块管理
        Route::prefix('modules')->group(function () use ($moduleController) {
            Route::get('/', [$moduleController, 'index']);
            Route::put('{name}/enable', [$moduleController, 'enable']);
            Route::put('{name}/disable', [$moduleController, 'disable']);
        });

        // 设置管理
        Route::prefix('settings')->group(function () use ($settingController) {
            Route::get('ui/form', [$settingController, 'formUi']);
            Route::get('/', [$settingController, 'index']);
            Route::get('{group}', [$settingController, 'group']);
            Route::put('/', [$settingController, 'update']);
        });
    });
});
