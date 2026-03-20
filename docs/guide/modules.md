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
