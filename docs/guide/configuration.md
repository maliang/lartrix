# 配置

Lartrix 的配置文件位于 `config/lartrix.php`。

## 基础配置

```php
return [
    // 路由配置
    'route' => [
        'path' => '/admin',
        'api_prefix' => 'api/admin',
        'guard' => 'admin',
    ],
    
    // 系统信息
    'theme' => [
        'appTitle' => '管理系统',
        'logo' => '/admin/logo.svg',
    ],
    'copyright' => '© 2024 All Rights Reserved',
];
```

## 路由配置

```php
'route' => [
    // 前端访问路径
    'path' => '/admin',
    
    // API 前缀
    'api_prefix' => 'api/admin',
    
    // 认证 Guard
    'guard' => 'admin',
],
```

## 模型映射

可以自定义使用的模型类：

```php
'models' => [
    'user' => \App\\Models\\AdminUser::class,
    'role' => \App\\Models\\Role::class,
    'permission' => \App\\Models\\Permission::class,
],
```

## 控制器映射

可以自定义使用的控制器类：

```php
'controllers' => [
    'auth' => \App\\Http\\Controllers\\AuthController::class,
    'user' => \App\\Http\\Controllers\\UserController::class,
],
```

## 数据表映射

```php
'tables' => [
    'users' => 'admin_users',
    'roles' => 'roles',
    'permissions' => 'permissions',
    'menus' => 'admin_menus',
],
```

## 超级管理员

```php
'super_admin' => [
    'role_name' => 'super_admin',
    'guard_name' => 'admin',
],
```

## 缓存配置

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'lartrix:',
],
```

## 完整配置示例

```php
<?php

return [
    'route' => [
        'path' => '/admin',
        'api_prefix' => 'api/admin',
        'guard' => 'admin',
    ],
    
    'theme' => [
        'appTitle' => env('LARTRIX_APP_TITLE', '管理系统'),
        'logo' => env('LARTRIX_LOGO', '/admin/logo.svg'),
    ],
    'copyright' => env('LARTRIX_COPYRIGHT', '© 2024 All Rights Reserved'),
    
    'models' => [
        'user' => \Lartrix\\Models\\AdminUser::class,
        'role' => \Lartrix\\Models\\Role::class,
        'permission' => \Lartrix\\Models\\Permission::class,
        'menu' => \Lartrix\\Models\\Menu::class,
    ],
    
    'tables' => [
        'users' => 'admin_users',
        'roles' => 'roles',
        'permissions' => 'permissions',
        'menus' => 'admin_menus',
    ],
    
    'super_admin' => [
        'role_name' => 'super_admin',
        'guard_name' => 'admin',
    ],
    
    'cache' => [
        'enabled' => env('LARTRIX_CACHE_ENABLED', true),
        'ttl' => env('LARTRIX_CACHE_TTL', 3600),
        'prefix' => 'lartrix:',
    ],
];
```
