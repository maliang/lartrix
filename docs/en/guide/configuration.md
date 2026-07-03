# Configuration

Lartrix configuration lives in `config/lartrix.php`. Publish the config file in the host Laravel application when you need to override defaults.

## Basic Configuration

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
        'appSubtitle' => env('LARTRIX_APP_SUBTITLE', 'Laravel and Trix based admin system'),
        'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
    ],
];
```

`path` is the admin frontend path, `api_prefix` is the backend API prefix, and `guard` is the admin authentication guard. The installer writes the `admin` guard and `admins` provider to `config/auth.php`:

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

## Mappings

Host applications may replace models, controllers, and table names:

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

## Permissions and Cache

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

Users with the configured super-admin role have all admin permissions.

## Internationalization

Add a new language to `languages`, then create `lang/vendor/lartrix/{file}.php`. Modules should prefer Laravel and `nwidart/laravel-modules` language loading. The legacy single-file module language package remains compatible.

The frontend uses `/api/admin/translations` and `/api/admin/locale`. Menu titles, schema labels, buttons, and messages should use translation keys instead of hard-coded Chinese text.

## Header

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
            'tooltip' => 'Pending Reviews',
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

`click` supports `route`, `link`, `modal`, and `drawer`. Use `badge` for counts and colors; do not use the old `badge_api` / `badge_color` shape.

Modules may declare `header_custom_items` in module config. Lartrix merges enabled module items into `lartrix.header.custom_items`.

## Realtime

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

`behaviors` trigger frontend actions by notification `type`. Built-in actions are `sound` and `notification`; custom frontend actions can also be registered. Badge counts come from realtime unread totals and unread counts by type, not from the current notification list page.

## Theme

```php
'theme' => [
    'appTitle' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
    'appSubtitle' => env('LARTRIX_APP_SUBTITLE', 'Laravel and Trix based admin system'),
    'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
    'footer' => [
        'visible' => false,
    ],
],
```

Use `appTitle` consistently. Do not add new `app_title` configuration. After the system settings page saves, it calls Trix `$methods.$theme.updateSite`, so the menu title, logo, and browser title update immediately. Logo URLs are rendered as-is and are not prefixed with `/admin`.
