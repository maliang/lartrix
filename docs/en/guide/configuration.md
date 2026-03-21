# Configuration

Lartrix configuration file is located at `config/lartrix.php`.

## Basic Configuration

```php
return [
    // Route configuration
    'route' => [
        'path' => '/admin',
        'api_prefix' => 'api/admin',
        'guard' => 'admin',
    ],
    
    // System info
    'app_title' => 'Admin System',
    'logo' => '/admin/logo.svg',
    'copyright' => '© 2024 All Rights Reserved',
];
```

## Route Configuration

```php
'route' => [
    // Frontend access path
    'path' => '/admin',
    
    // API prefix
    'api_prefix' => 'api/admin',
    
    // Auth Guard
    'guard' => 'admin',
],
```

## Model Mapping

Customize model classes:

```php
'models' => [
    'user' => \App\Models\AdminUser::class,
    'role' => \App\Models\Role::class,
    'permission' => \App\Models\Permission::class,
],
```

## Controller Mapping

Customize controller classes:

```php
'controllers' => [
    'auth' => \App\Http\Controllers\AuthController::class,
    'user' => \App\Http\Controllers\UserController::class,
],
```

## Table Mapping

```php
'tables' => [
    'users' => 'admin_users',
    'roles' => 'roles',
    'permissions' => 'permissions',
    'menus' => 'admin_menus',
],
```

## Super Admin

```php
'super_admin' => [
    'role_name' => 'super_admin',
    'guard_name' => 'admin',
],
```

## Cache Configuration

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'lartrix:',
],
```

## Full Configuration Example

```php
<?php

return [
    'route' => [
        'path' => '/admin',
        'api_prefix' => 'api/admin',
        'guard' => 'admin',
    ],

    'app_title' => env('LARTRIX_TITLE', 'Admin System'),
    'logo' => env('LARTRIX_LOGO', '/admin/logo.svg'),
    'copyright' => env('LARTRIX_COPYRIGHT', '© 2024 All Rights Reserved'),

    'models' => [
        'user' => \Lartrix\Models\AdminUser::class,
        'role' => \Lartrix\Models\Role::class,
        'permission' => \Lartrix\Models\Permission::class,
        'menu' => \Lartrix\Models\Menu::class,
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
