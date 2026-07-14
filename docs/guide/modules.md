# 模块化开发

Lartrix 基于 nwidart/laravel-modules 支持模块化开发。

## Trix 模块协议

Lartrix 模块继续基于 `nwidart/laravel-modules`，并在同一个 `module.json` 中使用独立的 `trix` 子节点承载生态元数据。

1. 根节点 `name`、`alias`、`providers`、`priority` 等字段由 Nwidart 负责模块运行。
2. `trix.schema_version=trix.module.v1` 时，该模块才参与 Trix 市场、发布和项目组合流程。
3. Lartrix 不再归一化旧的扁平 Trix 清单；这是一次明确的破坏性协议升级。
4. 包内 `trix.adapter` 只声明当前发布包的技术适配器；完整支持矩阵和发布状态由 Registry 数据库维护。

模块市场方向是 Trix Module Registry：市场数据库保存模块在不同语言/框架上的整体支持矩阵；包内 `module.json` 只声明当前包自己的 `adapter`，例如 `language=php`、`framework=laravel`、最低语言/框架版本和安装入口。

### 从 Registry 解析模块

Lartrix 安装命令支持通过 `--registry` 查询 Trix Module Registry：

```bash
php artisan lartrix:module-install official.cms --registry=https://registry.example.com
```

也可以在项目环境变量中配置默认模块市场。命令行未传 `--registry` 时读取 `module_market.url`，未传 `--signature-key` 时读取 `module_market.signature_key`：

```env
LARTRIX_MODULE_MARKET_URL=https://registry.example.com
LARTRIX_MODULE_MARKET_SIGNATURE_KEY=your-signature-key
TRIX_AUTH_KEY=your-auth-key
```

完整配置键为 `enabled/url/auth_key/signature_key/timeout/cache_ttl`。Lartrix 不再读取 `module_registry` 或 `module_market.api_url`。

默认行为只解析模块版本和 Laravel adapter，不下载、不解压、不安装远端包。如果确认要把 adapter 包缓存到本机，可以显式增加 `--download`：

```bash
php artisan lartrix:module-install official.cms --registry=https://registry.example.com --download
```

如果 Registry 返回 `signature`，并且本地传入 `--signature-key`，命令还会校验 `hmac-sha256:{base64}` 签名，签名载荷为 adapter checksum 字符串：

```bash
php artisan lartrix:module-install official.cms \
  --registry=https://registry.example.com \
  --download \
  --signature-key="${TRIX_REGISTRY_SIGNATURE_KEY}"
```

下载会先校验 `sha256:` checksum，成功后只写入本地缓存目录。缓存包仍需再通过 Laravel 原生模块流程安装或启用，避免远端包直接修改项目。

缓存完成后 Lartrix 会进行 zip 预检：检查包内路径是否包含 `../`、绝对路径或 Windows 盘符路径，并确认存在 `module.json`。预检通过后，包会解压到隔离 staging 目录，供后续人工审阅或本地模块流程使用；不会直接移动到正式模块目录，也不会执行包内脚本。

staging 完成后，Lartrix 会重新读取包内 `module.json.trix`，核对模块 `id`、`version` 和 `php/laravel` 技术适配器是否与 Registry 返回一致。若不一致，流程会停止。

确认 staging 内容后，可以显式复制到本地模块目录：

```bash
php artisan lartrix:module-install official.cms \
  --from-stage=/tmp/lartrix-registry-staging/official.cms-1.0.0 \
  --manifest=official.cms/module.json \
  --version=1.0.0 \
  --target-dir=/app/Modules/OfficialCms
```

该命令只复制文件。目标目录已存在时会拒绝，不会启用模块、不会执行迁移或 Seeder。

复制完成后，命令会扫描模块中的 `composer.json`、ServiceProvider、migration、Seeder，并输出人工待办清单和建议命令。这些命令只作为提示，不会自动执行。

如果 manifest 的 `security` 声明包含 `writes_files`、`runs_commands`、`external_network` 或 `requires_secrets`，命令会输出安全审阅提示。第一阶段只提示风险，不自动执行包内脚本，也不自动批准高风险操作。

## 模块版本更新

模块更新必须先生成更新计划，再复用 Registry 安装器的下载、校验、staging 和 manifest 复核链路。第一阶段默认只允许升级，不允许降级，也不会自动覆盖正式模块目录。

确认新版本目录后，推荐先使用专用更新命令做 dry-run：

```bash
php artisan lartrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsNext \
  --manifest=module.json \
  --version=1.1.0 \
  --dry-run \
  --strict-security \
  --audit-log=/app/storage/trix-module-updates.jsonl
```

`--dry-run` 只输出更新计划，不要求备份目录，也不会移动任何文件。`--strict-security` 会在 manifest 声明写文件、运行命令、访问外网、需要密钥或使用 eval 时直接拒绝。`--audit-log` 会追加写入 JSON Lines 审计记录。确认计划无误后，再显式替换正式目录：

```bash
php artisan lartrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsNext \
  --manifest=module.json \
  --version=1.1.0 \
  --backup-dir=/app/Modules/.backup/OfficialCms-1.0.0 \
  --strict-security \
  --audit-log=/app/storage/trix-module-updates.jsonl \
  --confirm-replace
```

更新命令会先读取当前目录的 manifest 判断当前版本，默认只允许目标版本更高时继续；随后把旧目录移动到备份目录，再把新目录移动到正式目录。它仍不会自动执行 migration、Seeder、autoload 或包内脚本。

如果确实需要降级，必须显式增加 `--allow-downgrade`。降级前先 dry-run，正式替换时仍必须提供备份目录和确认参数：

```bash
php artisan lartrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsPrev \
  --manifest=module.json \
  --version=1.0.0 \
  --dry-run \
  --allow-downgrade \
  --strict-security \
  --audit-log=/app/storage/trix-module-updates.jsonl
```

更新流程说明见工作区文档：

- `docs/ecosystem/module-version-update-flow.md`

详细协议见工作区文档：

- `docs/protocol/trix-module-manifest-v1.md`
- `docs/ecosystem/runtime-support-matrix.md`
- `docs/ecosystem/migrating-legacy-module-json.md`

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
    "requires": [],
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

`nwidart/laravel-modules` 以根字段和模块 ServiceProvider 驱动模块运行；Lartrix 只读取已经完整校验的 `trix` 子节点。路由注册、配置合并、语言加载、迁移、Seeder 都应放在模块自身目录中维护。

项目安装计划执行成功后只写入 `config/trix-project.php`。模块通过 `config('trix-project.modules.<模块 ID>.config')` 读取覆盖配置，通过 `config('trix-project.contract_bindings')` 读取契约绑定；运行时不读取 `install-plan.json` 或模块级派生配置 JSON。

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
