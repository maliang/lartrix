# 配置

Lartrix 的配置文件位于 `config/lartrix.php`。安装后可以通过发布配置文件覆盖默认值。

## 基础配置

```php
return [
    'path' => env('LARTRIX_PATH', '/admin'),
    'api_prefix' => env('LARTRIX_API_PREFIX', 'api/admin'),
    'guard' => env('LARTRIX_GUARD', 'admin'),

    'locale' => env('LARTRIX_LOCALE', 'zh-CN'),
    'fallback_locale' => 'en-US',
    'languages' => [
        'zh-CN' => ['label' => '中文', 'file' => 'zh-CN', 'naive_locale' => 'zh-CN'],
        'en-US' => ['label' => 'English', 'file' => 'en-US', 'naive_locale' => 'en-US'],
    ],

    'theme' => [
        'appTitle' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
        'appSubtitle' => env('LARTRIX_APP_SUBTITLE', '基于 Laravel 和 Trix 的后台管理系统'),
        'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
    ],
];
```

`path` 是后台前端访问路径，`api_prefix` 是后台 API 前缀，`guard` 是后台认证 guard。安装命令会向 `config/auth.php` 写入 `admin` guard 和 `admins` provider：

```php
'guards' => [
    'admin' => [
        'driver' => 'sanctum',
        'provider' => 'admins',
    ],
],

'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => \Lartrix\Models\AdminUser::class,
    ],
],
```

## 映射配置

Lartrix 允许宿主项目替换模型、控制器和表名：

```php
'models' => [
    'user' => \App\Models\AdminUser::class,
    'role' => \Lartrix\Models\Role::class,
    'permission' => \Lartrix\Models\Permission::class,
    'menu' => \Lartrix\Models\Menu::class,
    'setting' => \Lartrix\Models\Setting::class,
],

'controllers' => [
    'auth' => \Lartrix\Controllers\AuthController::class,
    'setting' => \Lartrix\Controllers\SettingController::class,
],

'tables' => [
    'users' => 'admin_users',
    'menus' => 'admin_menus',
    'settings' => 'admin_settings',
],
```

## 权限与缓存

```php
'super_admin_role' => env('LARTRIX_SUPER_ADMIN_ROLE', 'super-admin'),

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
```

超级管理员通过角色名判断，拥有该角色的用户拥有所有后台权限。

## 多语言

新增语言时，在项目配置中追加 `languages` 项，并创建 `lang/vendor/lartrix/{file}.php`。模块推荐使用 Laravel 和 `nwidart/laravel-modules` 的语言目录能力；旧版模块单文件语言包仍可兼容。

多语言切换使用 `/api/admin/translations` 与 `/api/admin/locale` 统一协议。菜单标题、页面 schema、按钮文案都应尽量使用语言 key，而不是固定中文。

## 导航栏

`header` 控制右侧默认按钮和自定义导航项：

```php
'header' => [
    'global_search' => true,
    'notification' => true,
    'full_screen' => true,
    'lang_switch' => true,
    'theme_schema_switch' => true,
    'theme_button' => true,
    'custom_items_position' => 'left',
    'custom_items' => [
        [
            'icon' => 'carbon:task',
            'tooltip' => '待审核',
            'badge' => [
                'source' => 'notification',
                'types' => ['audit.pending'],
                'mode' => 'count',
                'max' => 99,
                'color' => '#f5222d',
            ],
            'click' => 'route',
            'click_target' => '/audit',
        ],
    ],
],
```

`click` 支持 `route`、`link`、`modal`、`drawer`。`route` 是后台内部跳转；`link` 可配 `target`，如 `_blank` 或 `_self`。角标统一使用 `badge`，不再使用旧式 `badge_api` / `badge_color`。

模块也可以在模块配置中声明 `header_custom_items`，模块启用后会自动合并到全局导航项。

## 实时消息

```php
'realtime' => [
    'enabled' => true,
    'enable_notification' => true,
    'driver' => 'polling',
    'polling' => [
        'interval' => 15000,
        'api' => '/notifications/poll',
    ],
    'behaviors' => [
        'audit.pending' => [
            'notify' => false,
            'actions' => [
                ['type' => 'sound', 'src' => '/sounds/audit.mp3', 'times' => 3],
            ],
        ],
    ],
],
```

`behaviors` 按通知 `type` 触发前端行为，内置 `sound` 和 `notification`，也支持前端注册自定义动作。角标数量来自轮询接口返回的未读总数和按类型未读数，不依赖通知列表分页。

## 主题

```php
'theme' => [
    'appTitle' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
    'appSubtitle' => env('LARTRIX_APP_SUBTITLE', '基于 Laravel 和 Trix 的后台管理系统'),
    'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
    'footer' => [
        'visible' => false,
    ],
],
```

统一使用 `appTitle`，不要再新增 `app_title`。系统设置页保存后会调用 Trix 的 `$methods.$theme.updateSite`，菜单顶部标题、Logo 和浏览器标题会立即更新。Logo 地址按原样输出，不会自动拼接 `/admin`。
