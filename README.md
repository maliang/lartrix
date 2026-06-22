# Lartrix

Laravel 后台管理包，为 [Trix](https://github.com/maliang/trix) 前端提供 API 接口。

## 特性

- 🔐 用户认证与权限管理（基于 Laravel Sanctum + Spatie Permission）
- 👥 用户、角色、权限管理
- 📋 菜单管理（支持树形结构）
- ⚙️ 系统设置
- 📦 模块化开发支持（基于 nwidart/laravel-modules）
- 🎨 PHP Schema Builder - 用 PHP 构建前端界面

## 环境要求

- PHP >= 8.1
- Laravel >= 10.0

## 安装

```bash
composer require lartrix/lartrix
```

运行安装命令：

```bash
php artisan lartrix:install
```

安装过程会：
1. 发布前端资源到 `public/admin`
2. 发布配置文件和迁移文件
3. 执行数据库迁移
4. 创建超级管理员角色和权限
5. 创建默认菜单
6. 交互式创建管理员账户
7. 创建 AI 开发指南文件（AGENTS.md、CLAUDE.md）

## 访问后台

安装完成后访问 `/admin/` 进入后台管理系统。

## 配置

配置文件位于 `config/lartrix.php`：

```php
return [
    'route_prefix' => 'api/admin',      // API 路由前缀
    'guard' => 'sanctum',               // 认证守卫
    'super_admin_role' => 'super-admin', // 超级管理员角色名
    'models' => [...],                  // 模型类映射
    'controllers' => [...],             // 控制器类映射
];
```

### 多语言

Lartrix 与 Thinkrix 对前端提供相同的语言配置结构，以及 `/translations`、`/locale` 接口。Laravel 版内部使用 Laravel Translator 和 Nwidart 模块语言 namespace。

增加语言时，在 `config/lartrix.php` 声明：

```php
'languages' => [
    'zh-CN' => ['label' => '中文', 'file' => 'zh-CN', 'naive_locale' => 'zh-CN'],
    'en-US' => ['label' => 'English', 'file' => 'en-US', 'naive_locale' => 'en-US'],
    'ja-JP' => ['label' => '日本語', 'file' => 'ja-JP', 'naive_locale' => 'en-US'],
],
```

项目覆盖或新增的完整界面语言包放在 `lang/vendor/lartrix/ja-JP.php`。前端会动态加载，无需增加 Trix 语言模块或重新构建前端。

Nwidart 模块继续使用原生目录和 namespace，例如：

```text
Modules/Blog/Resources/lang/ja-JP/messages.php
```

模块 PHP 中使用 `__('blog::messages.title')`。Lartrix 的前端翻译目录会同时暴露为 `blog.messages.title`。通过 Nwidart 发布到 `resources/lang/modules/blog` 的项目覆盖优先级更高。旧版模块的 `Resources/lang/ja-JP.php` 汇总文件仍受兼容支持。

## 开发指南

### 模块化开发

推荐使用 `nwidart/laravel-modules` 进行模块化开发：

```bash
php artisan module:make Blog
```

### 控制器开发

继承 `CrudController` 快速实现 CRUD：

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Button, Space};
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\Blog\Models\Post::class;
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
            ])
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索', 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()->type('primary')->on('click', [SetAction::make('formVisible', true)])->text('新增'),
            ]);

        return success($schema->build());
    }
}
```

### API 响应

```php
// 成功响应
return success('操作成功', $data);
return success($data);

// 错误响应
error('错误信息', null, 40004);
```

## Schema 组件

### NaiveUI 组件

类名无 N 前缀，输出保留 N 前缀：

```php
use Lartrix\Schema\Components\NaiveUI\{Button, Input, Select, SwitchC, Card, Modal};

Button::make()->type('primary')->text('提交');
Input::make()->props(['placeholder' => '请输入']);
SwitchC::make();  // Switch 是 PHP 保留字
```

### 业务组件

```php
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};

CrudPage::make('标题')
    ->apiPrefix('/api/path')
    ->columns([...])
    ->search([...])
    ->build();
```

### Action 类型

```php
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction, ScriptAction};

SetAction::make('visible', true);
CallAction::make('$message.success', ['成功']);
FetchAction::make('/api/path')->post()->body($data);
```

## 命令

```bash
php artisan lartrix:install          # 安装
php artisan lartrix:publish-assets   # 发布前端资源
php artisan lartrix:uninstall        # 卸载
```

## 测试

```bash
./vendor/bin/phpunit
```

## 许可证

MIT License
