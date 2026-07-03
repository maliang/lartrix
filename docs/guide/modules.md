# 模块化开发

Lartrix 基于 nwidart/laravel-modules 支持模块化开发。

## 创建模块

```bash
php artisan module:make Blog
```

生成的模块结构：

```
Modules/Blog/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
├── routes/
│   └── api.php
├── tests/
├── module.json
└── composer.json
```

## 模块配置

### module.json

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "博客模块",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

`nwidart/laravel-modules` 以 `module.json` 和模块 ServiceProvider 为核心。Lartrix 会优先利用 Laravel 原生能力：路由注册、配置合并、语言加载、迁移、Seeder 都应放在模块自身目录中维护。

### 路由

在 `routes/api.php` 中定义：

```php
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\Blog\\Http\\Controllers\\PostController;

Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

后台 API 建议使用模块前缀，例如 `/blog/posts`。菜单地址应填写最终后台路由路径，不要重复拼接模块名；如果菜单路径已经是 `/finance/recharges`，前端点击菜单时就应直接进入该路径。

## 控制器开发

```php
<?php

namespace Modules\\Blog\\Http\\Controllers;

use Lartrix\\Controllers\\CrudController;
use Modules\\Blog\\Models\\Post;

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('文章管理')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'title', 'title' => '标题'],
            ]);

        return success($schema->build());
    }
}
```

## 数据库迁移

```bash
php artisan module:make-migration create_posts_table Blog
```

迁移文件位于 `Modules/Blog/database/migrations/`。

## 菜单和权限填充

模块菜单和权限建议写在模块的 Seeder 中，随模块安装或初始化命令执行。这样模块被迁移到其他项目时，代码、迁移、菜单、权限可以一起交付。

```php
use Illuminate\Database\Seeder;
use Lartrix\Models\Menu;
use Spatie\Permission\Models\Permission;

class BlogDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $parent = Menu::query()->firstOrCreate(
            ['path' => '/blog'],
            [
                'title' => 'module.blog.menu.root',
                'icon' => 'ph:article',
                'sort' => 50,
            ]
        );

        Menu::query()->firstOrCreate(
            ['path' => '/blog/posts'],
            [
                'parent_id' => $parent->id,
                'title' => 'module.blog.menu.posts',
                'component' => 'schema',
                'schema_api' => '/blog/posts?action_type=list_ui',
                'permission' => 'blog.posts.view',
                'sort' => 10,
            ]
        );

        foreach (['view', 'create', 'update', 'delete'] as $action) {
            Permission::findOrCreate("blog.posts.{$action}", 'admin');
        }
    }
}
```

菜单标题推荐使用语言 key。模块语言文件放在 `Modules/Blog/lang/zh-CN.php`、`Modules/Blog/lang/en-US.php` 或 Laravel Modules 当前项目约定目录中，由模块 ServiceProvider 加载。

## 自定义导航项

模块可在配置中声明导航栏入口：

```php
// Modules/Blog/config/config.php
return [
    'header_custom_items' => [
        [
            'icon' => 'ph:article',
            'tooltip' => '待审核文章',
            'badge' => [
                'source' => 'notification',
                'types' => ['blog.post.pending'],
                'mode' => 'count',
                'color' => '#f5222d',
            ],
            'click' => 'route',
            'click_target' => '/blog/review',
        ],
    ],
];
```

模块启用后，Lartrix 会把模块 `header_custom_items` 合并到全局 `lartrix.header.custom_items`。

## 模块管理

### 启用/禁用模块

```bash
php artisan module:enable Blog
php artisan module:disable Blog
```

### 模块列表

```bash
php artisan module:list
```

## 模块间依赖

在 `module.json` 中声明依赖：

```json
{
    "requires": ["User", "Media"]
}
```

## 最佳实践

1. **独立命名空间**：使用模块名作为命名空间前缀
2. **数据库前缀**：表名使用模块名前缀，如 `blog_posts`
3. **配置隔离**：配置项使用模块名前缀，如 `blog.per_page`
4. **路由前缀**：API 路由使用模块名前缀，如 `/blog/posts`
5. **数据填充**：菜单、权限、默认配置写入模块 Seeder
6. **语言文件**：模块文案使用语言 key，语言文件随模块发布
