# 快速开始

本指南将帮助你在 5 分钟内创建第一个 CRUD 页面。

## 创建模块

```bash
php artisan module:make Blog
```

## 创建迁移

```bash
php artisan module:make-migration create_posts_table Blog
```

编辑迁移文件：

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
});
```

运行迁移：

```bash
php artisan migrate
```

## 创建模型

```bash
php artisan module:make-model Post Blog
```

编辑模型：

```php
<?php

namespace Modules\\Blog\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'status'];
}
```

## 创建控制器

```bash
php artisan module:make-controller PostController Blog
```

编辑控制器：

```php
<?php

namespace Modules\\Blog\\Http\\Controllers;

use Lartrix\\Controllers\\CrudController;
use Lartrix\\Schema\\Components\\NaiveUI\\{Input, SwitchC};
use Lartrix\\Schema\\Components\\Business\\CrudPage;
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
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => '标题'],
                ['key' => 'status', 'title' => '状态'],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()]
            ]);

        return success($schema->build());
    }
}
```

## 添加路由

在 `Modules/Blog/Routes/api.php` 中添加：

```php
use Illuminate\\Support\\Facades\\Route;
use Modules\\Blog\\Http\\Controllers\\PostController;

Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

## 添加菜单

登录后台，进入"系统管理" -> "菜单管理"，添加新菜单：

- 标题：文章管理
- 路径：/blog/posts
- 图标：document-text
- 权限：posts.list

## 完成

刷新后台页面，你现在可以在侧边栏看到"文章管理"菜单了！

## 下一步

- [Schema 系统](/guide/schema) - 深入了解 Schema 构建
- [CrudController](/guide/crud-controller) - 学习更多 CRUD 功能
