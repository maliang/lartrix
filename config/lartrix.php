<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 路由配置
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('LARTRIX_ROUTE_PREFIX', 'api/admin'),
    'guard' => env('LARTRIX_GUARD', 'sanctum'),

    /*
    |--------------------------------------------------------------------------
    | 系统信息
    |--------------------------------------------------------------------------
    */
    'app_title' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
    'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
    'copyright' => env('LARTRIX_COPYRIGHT', '© ' . date('Y') . ' Lartrix Admin. All rights reserved.'),

    /*
    |--------------------------------------------------------------------------
    | 模型映射
    | 用户可继承默认模型并在此配置自定义模型类
    |--------------------------------------------------------------------------
    */
    'models' => [
        'user' => \Lartrix\Models\AdminUser::class,
        'role' => \Lartrix\Models\Role::class,
        'permission' => \Lartrix\Models\Permission::class,
        'menu' => \Lartrix\Models\Menu::class,
        'setting' => \Lartrix\Models\Setting::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 控制器映射
    | 用户可继承默认控制器并在此配置自定义控制器类
    |--------------------------------------------------------------------------
    */
    'controllers' => [
        'auth' => \Lartrix\Controllers\AuthController::class,
        'user' => \Lartrix\Controllers\UserController::class,
        'role' => \Lartrix\Controllers\RoleController::class,
        'permission' => \Lartrix\Controllers\PermissionController::class,
        'menu' => \Lartrix\Controllers\MenuController::class,
        'setting' => \Lartrix\Controllers\SettingController::class,
        'system' => \Lartrix\Controllers\SystemController::class,
        'home' => \Lartrix\Controllers\HomeController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据表映射
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'users' => 'admin_users',
        'menus' => 'admin_menus',
        'settings' => 'admin_settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | 超级管理员角色
    | 拥有此角色的用户将拥有所有权限
    |--------------------------------------------------------------------------
    */
    'super_admin_role' => env('LARTRIX_SUPER_ADMIN_ROLE', 'super-admin'),

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'menu' => [
            'enabled' => env('LARTRIX_MENU_CACHE_ENABLED', true),
            'key' => 'lartrix.menus',
            'ttl' => 3600,
        ],
        'settings' => [
            'enabled' => env('LARTRIX_SETTINGS_CACHE_ENABLED', true),
            'prefix' => 'lartrix.setting.',
            'ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认头像
    |--------------------------------------------------------------------------
    */
    'default_avatar' => env('LARTRIX_DEFAULT_AVATAR', null),
];
