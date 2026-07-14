<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 路由配置
    |--------------------------------------------------------------------------
    */
    'path' => env('LARTRIX_PATH', '/admin'),
    'api_prefix' => env('LARTRIX_API_PREFIX', 'api/admin'),
    'guard' => env('LARTRIX_GUARD', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | 系统信息
    |--------------------------------------------------------------------------
    */
    'locale' => env('LARTRIX_LOCALE', 'zh-CN'),
    'fallback_locale' => 'en-US',
    // 新增语言只需追加配置，并创建 lang/vendor/lartrix/{file}.php。
    // naive_locale 用于 Naive UI 内置组件语言，目前可选 zh-CN / en-US。
    'languages' => [
        'zh-CN' => ['label' => '中文', 'file' => 'zh-CN', 'naive_locale' => 'zh-CN'],
        'en-US' => ['label' => 'English', 'file' => 'en-US', 'naive_locale' => 'en-US'],
    ],
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
        'module' => \Lartrix\Controllers\ModuleController::class,
        'module_market' => \Lartrix\Controllers\ModuleMarketController::class,
        'module_publish' => \Lartrix\Controllers\ModulePublishController::class,
        'dict' => \Lartrix\Controllers\DictController::class,
        'upload' => \Lartrix\Controllers\UploadController::class,
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

    /*
    |--------------------------------------------------------------------------
    | 导航栏组件显示配置
    | 控制导航栏右侧各功能按钮的显示/隐藏
    |--------------------------------------------------------------------------
    */
    'header' => [
        'global_search' => env('LARTRIX_HEADER_GLOBAL_SEARCH', true),
        'notification' => env('LARTRIX_HEADER_NOTIFICATION', true),
        'full_screen' => env('LARTRIX_HEADER_FULL_SCREEN', true),
        'lang_switch' => env('LARTRIX_HEADER_LANG_SWITCH', true),
        'theme_schema_switch' => env('LARTRIX_HEADER_THEME_SCHEMA_SWITCH', true),
        'theme_button' => env('LARTRIX_HEADER_THEME_BUTTON', true),

        /*
        |----------------------------------------------------------------------
        | 自定义导航项位置
        | left  ：位于默认右侧组件整体的最左侧（默认）
        | right ：位于默认右侧组件整体的最右侧
        |----------------------------------------------------------------------
        */
        'custom_items_position' => env('LARTRIX_HEADER_CUSTOM_ITEMS_POSITION', 'left'),

        /*
        |----------------------------------------------------------------------
        | 自定义导航项
        | 在导航栏右侧添加自定义功能入口。除在此处配置外，
        | 模块也可在其 config/config.php 的 'header_custom_items' 中声明，
        | 模块启用后自动合并到此处。
        |
        | 简单图标按钮（无需自定义组件）：
        |   [
        |       'icon'         => 'carbon:rocket',
        |       'tooltip'      => '消息中心',
        |       'badge'        => ['source' => 'notification', 'types' => ['audit.pending'], 'mode' => 'count', 'max' => 99, 'color' => '#f00'],
        |       'click'        => 'route',                // route | link | modal | drawer
        |       'click_target' => '/audit',
        |       'target'       => '_blank',               // 仅 link 模式使用，默认 _blank
        |   ]
        |
        | 高级自定义（通过 schema_api 返回任意 schema UI）：
        |   ['schema_api' => '/api/admin/header/my-dropdown']
        |----------------------------------------------------------------------
        */
        'custom_items' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | 模块市场配置
    | 连接远程模块市场获取可用模块
    |--------------------------------------------------------------------------
    */
    'module_market' => [
        'enabled' => env('LARTRIX_MODULE_MARKET_ENABLED', true),
        'url' => env('LARTRIX_MODULE_MARKET_URL', ''),
        'auth_key' => env('TRIX_AUTH_KEY', ''),
        'signature_key' => env('LARTRIX_MODULE_MARKET_SIGNATURE_KEY', ''),
        'timeout' => env('LARTRIX_MODULE_MARKET_TIMEOUT', 30),
        'cache_ttl' => env('LARTRIX_MODULE_MARKET_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知配置
    |--------------------------------------------------------------------------
    */
    'notification' => [
        // 通知分类模型
        'category_model' => \Lartrix\Models\NotificationCategory::class,

        // 通知消息模型
        'message_model' => \Lartrix\Models\NotificationMessage::class,

        // 默认分类（用于二级后台初始化）
        'default_categories' => [
            [
                'key' => 'all',
                'name' => '全部',
                'icon' => 'ph:bell',
                'color' => '',
                'message_types' => [],
                'sort' => 0,
            ],
            [
                'key' => 'system',
                'name' => '系统',
                'icon' => 'ph:gear',
                'color' => '',
                'message_types' => ['system'],
                'sort' => 1,
            ],
            [
                'key' => 'notice',
                'name' => '通知',
                'icon' => 'ph:chat',
                'color' => '',
                'message_types' => ['notice'],
                'sort' => 2,
            ],
            [
                'key' => 'message',
                'name' => '消息',
                'icon' => 'ph:chat-circle',
                'color' => '',
                'message_types' => ['message'],
                'sort' => 3,
            ],
            [
                'key' => 'todo',
                'name' => '待办',
                'icon' => 'ph:check-square',
                'color' => '',
                'message_types' => ['todo'],
                'sort' => 4,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 实时消息配置
    |--------------------------------------------------------------------------
    */
    'realtime' => [
        'enabled' => env('LARTRIX_REALTIME_ENABLED', true),
        // 是否在新消息到达时弹出应用内通知 toast（全局开关），默认 true。
        // 如需仅对某些类型关闭弹窗，可在下方 behaviors 中对应 type 设置 'notify' => false。
        'enable_notification' => env('LARTRIX_REALTIME_ENABLE_NOTIFICATION', true),
        'driver' => env('LARTRIX_REALTIME_DRIVER', 'polling'), // polling 或 ws
        'polling' => [
            'interval' => env('LARTRIX_REALTIME_POLLING_INTERVAL', 15000),
            'api' => '/notifications/poll',
        ],
        'websocket' => [
            'url' => env('LARTRIX_REALTIME_WS_URL', ''),
            'protocol' => env('LARTRIX_REALTIME_WS_PROTOCOL', 'ws'),
        ],
        /*
        |----------------------------------------------------------------------
        | 消息行为配置
        | 按通知 type 触发前端动作。actions 支持内置动作 sound / notification，
        | 也支持前端通过 registerBehaviorAction 注册自定义动作。
        | 可选 'notify' => false 关闭该类型新消息的应用内弹窗（声音/角标不受影响）。
        |
        | 示例：
        | 'behaviors' => [
        |     'audit.pending' => [
        |         'notify' => false,   // 不弹窗，但仍可播放声音、更新角标
        |         'actions' => [
        |             ['type' => 'sound', 'src' => '/sounds/audit.mp3', 'times' => 3],
        |             ['type' => 'notification', 'title' => '新的审核任务'],
        |         ],
        |     ],
        | ],
        |----------------------------------------------------------------------
        */
        'behaviors' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认主题配置
    | 当数据库中未保存主题配置时使用此默认值
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'appTitle' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
        'appSubtitle' => env('LARTRIX_APP_SUBTITLE', '基于 Laravel 和 Trix 的后台管理系统'),
        'logo' => env('LARTRIX_LOGO', '/admin/favicon.svg'),
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
            'breadcrumb' => ['visible' => true, 'showIcon' => true],
            'multilingual' => ['visible' => true],
            'globalSearch' => ['visible' => true],
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
            'visible' => false,
            'height' => 48,
            'fixed' => false,
            'right' => true,
        ],
        'watermark' => [
            'visible' => false,
            'text' => env('LARTRIX_APP_TITLE', 'Lartrix Admin'),
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
    ],
];
