# Lartrix

Laravel 后台管理包，为 [Trix](https://github.com/maliang/trix) 前端提供 API 接口。

完整文档见 [`docs/`](docs/index.md)（含英文版 `docs/en/`）。

## 特性

- 🔐 用户认证与权限管理（基于 Laravel Sanctum + Spatie Permission）
- 👥 用户、角色、权限管理
- 📋 菜单管理（支持树形结构）
- ⚙️ 系统设置（分组存取、主题配置持久化）
- 🔔 通知中心（分类、发送、实时轮询，支持发送到二级后台）
- 📦 模块化开发支持（基于 nwidart/laravel-modules）
- 🏢 二级后台（多后台）：一条命令生成独立 guard 的后台模块
- 🌐 多语言（前端动态加载语言包，无需重新构建前端）
- 🎨 PHP Schema Builder - 用 PHP 构建前端界面（NaiveUI / CrudPage / OptForm / Actions）

## 环境要求

- PHP >= 8.1
- Laravel >= 10.0
- 依赖：`laravel/sanctum`、`spatie/laravel-permission`、`nwidart/laravel-modules`

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

> **已有数据保护**：`lartrix:install` 检测到已有数据时会二次确认；已存在的 `config/lartrix.php` 默认**不会覆盖**（发布其他三方配置相同），显式覆盖需加 `--force`。

## 访问后台

安装完成后访问 `/admin/`（由 `config/lartrix.php` 的 `path` 决定）进入后台管理系统。

## 配置

配置文件位于 `config/lartrix.php`：

```php
return [
    'path' => env('LARTRIX_PATH', '/admin'),          // 后台入口路径
    'api_prefix' => env('LARTRIX_API_PREFIX', 'api/admin'), // API 路由前缀
    'guard' => env('LARTRIX_GUARD', 'admin'),         // 认证守卫
    'locale' => env('LARTRIX_LOCALE', 'zh-CN'),       // 默认语言
    'super_admin_role' => 'super-admin',              // 超级管理员角色名
    'models' => [...],                                // 模型类映射（可继承默认模型）
    'controllers' => [...],                           // 控制器类映射
    'header' => [...],                                // 头部组件开关（全局搜索/通知/语言等）
    'notification' => [...],                          // 通知分类与模型配置
    'realtime' => [...],                              // 实时消息（轮询/WebSocket）
    'theme' => [...],                                 // 默认主题
    'module_market' => [...],                         // 模块市场
];
```

模块市场只使用 `module_market` 配置：

```php
'module_market' => [
    'enabled' => env('LARTRIX_MODULE_MARKET_ENABLED', true),
    'url' => env('LARTRIX_MODULE_MARKET_URL', ''),
    'auth_key' => env('TRIX_AUTH_KEY', ''),
    'signature_key' => env('LARTRIX_MODULE_MARKET_SIGNATURE_KEY', ''),
    'timeout' => env('LARTRIX_MODULE_MARKET_TIMEOUT', 30),
    'cache_ttl' => env('LARTRIX_MODULE_MARKET_CACHE_TTL', 3600),
],
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

## 二级后台（多后台）

Lartrix 支持在总后台之外创建**独立的二级后台模块**（如商户后台、代理商后台），每个后台拥有独立的 guard、用户表、菜单与权限。

### 创建二级后台

```bash
php artisan lartrix:make-backend Merchant --path=/merchant --api-prefix=api/merchant
```

可选参数：

| 参数 | 说明 | 默认值 |
|------|------|--------|
| `path` | 前端访问路径 | `/{小写名}` |
| `api-prefix` | API 接口前缀 | `api/{小写名}` |
| `table` | 用户表名 | `{小写名}s` |
| `title` | 后台标题 | `{模块名}管理系统` |

命令生成一个完整的 nwidart 后台模块（`Modules/Merchant/`）：

- 独立 `guard = merchant`（自动写入 `config/auth.php`）
- 独立用户表与模型（含 `isSuperAdmin`/`isActive`/SoftDeletes，权限语义与主系统一致）
- 独立路由：`api/merchant/*` 认证、菜单/角色/权限/用户管理 CRUD
- 独立菜单、权限、`super-admin` 角色、`admin / 123456` 管理员（由 Seeder 初始化，`firstOrCreate` 幂等）
- 用户自助接口：个人资料 / 账号设置 / 修改密码
- 主题与语言：`saveThemeConfig` / `setLocale` / `translations`（主题按模块分组 `theme.{模块名}` 独立存储）
- 前端资源共享 `public/admin/`，通过 `/{path}` 访问

### 重新安装（新库/新环境）

把已有二级后台模块迁移到**新数据库**时，迁移与 Seeder 不会自动补跑，且 `module:seed` 不识别 make-backend 生成的 `{Name}BackendSeeder`。使用重新安装命令一键补齐：

```bash
php artisan lartrix:backend-install Merchant
```

命令（全部幂等，可重复执行）：

1. 补 `config/auth.php` 的 guard/provider（已存在则跳过）
2. 启用模块并同步 `modules` 表
3. 运行模块迁移（`module:migrate`，已跑过则跳过）
4. 执行 `{Name}BackendSeeder` 数据填充（菜单/权限/角色/管理员）
5. 输出 guard / 路径 / 管理员账号摘要

### 移除二级后台

```bash
php artisan lartrix:remove-backend Merchant
```

## 命令参考

| 命令 | 说明 |
|------|------|
| `lartrix:install` | 安装 Lartrix（发布资源/配置/迁移，初始化数据） |
| `lartrix:uninstall` | 卸载 Lartrix |
| `lartrix:publish-assets` | 发布/更新前端资源到 `public/admin` |
| `lartrix:make-backend` | 创建二级后台模块 |
| `lartrix:backend-install` | 重新安装二级后台（迁移 + 填充 + guard 配置） |
| `lartrix:remove-backend` | 卸载二级后台模块 |
| `lartrix:module-make` | 创建标准 Lartrix/Trix 模块 |
| `lartrix:module-install` | 安装模块（迁移 + 填充 + 注册菜单权限） |
| `lartrix:module-uninstall` | 卸载模块（回滚迁移 + 删除菜单权限） |
| `lartrix:module-update` | 从模块市场更新已安装模块 |
| `lartrix:project-make` | 创建/同步 `trix-project.json` 项目清单 |
| `lartrix:project-install` | 安装项目计划及依赖模块 |
| `lartrix:project-publish` | 发布项目清单到 Trix 市场 |

## 开发指南

### 模块化开发

推荐使用 `nwidart/laravel-modules` 进行模块化开发：

```bash
php artisan module:make Blog
```

需要参与 Trix 市场、发布或项目组合的模块，使用同一个 `module.json`：Nwidart 拥有根字段，Trix 生态元数据严格放在 `trix` 子节点。旧的扁平 Trix 字段不再兼容。

```json
{
  "name": "Blog",
  "alias": "blog",
  "priority": 0,
  "providers": ["Modules\\Blog\\Providers\\BlogServiceProvider"],
  "files": [],
  "trix": {
    "schema_version": "trix.module.v1",
    "id": "official.blog",
    "version": "1.0.0",
    "title": "博客",
    "description": "博客模块",
    "author": "Trix 官方",
    "adapter": {
      "language": "php",
      "language_version": "^8.2",
      "framework": "laravel",
      "framework_version": "^12.0",
      "package_type": "nwidart"
    }
  }
}
```

项目安装完成后，运行时配置只写入 `config/trix-project.php`；项目配置、模块版本和覆盖配置、契约绑定及安装后引导均从 Laravel 配置仓库读取，不生成并行的派生 JSON。

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

// 错误响应（抛 ApiException，渲染 {code,msg,data}）
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
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction, ScriptAction, EmitAction};

SetAction::make('visible', true);
CallAction::make('$message.success', ['成功']);
FetchAction::make('/api/path')->post()->body($data);
EmitAction::make('submit');  // 触发前端 @submit 事件（弹窗提交）
```

## 测试

```bash
./vendor/bin/phpunit
```

> 测试套件使用 SQLite 内存库，需启用 PHP 的 `pdo_sqlite` 扩展：
> `php -d extension=pdo_sqlite ./vendor/bin/phpunit`

## 文档

完整文档（含 API 参考、Schema 组件、控制器/模型/服务、英文版）见：

- [`docs/`](docs/index.md) — 中文文档
- [`docs/en/`](docs/en/index.md) — English documentation

## 许可证

MIT License
