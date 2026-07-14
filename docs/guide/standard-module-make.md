# Lartrix 标准模块生成

Lartrix 保留 `nwidart/laravel-modules` 的原生命令：

```bash
php artisan module:make Blog
```

这个命令只负责生成 Laravel 模块骨架，不保证模块满足 Trix 生态协议。

如果要创建可以进入 Lartrix / Trixmore 模块市场流程的标准模块，应使用：

```bash
php artisan lartrix:module-make Blog
```

该命令会先调用原生 `module:make`，然后补齐 Lartrix 标准文件：

```text
Modules/Blog/module.json
Modules/Blog/resources/module/logo.svg
Modules/Blog/resources/module/thumbnail.svg
```

生成的 `module.json` 保留 Nwidart 根字段，并在 `trix.schema_version=trix.module.v1` 下声明生态元数据。默认 `trix.adapter` 为：

```json
{
  "language": "php",
  "language_version": "^8.2",
  "framework": "laravel",
  "framework_version": "^12.0",
  "package_type": "nwidart"
}
```

可选参数：

```bash
php artisan lartrix:module-make Blog \
  --id=official.blog \
  --title="Blog" \
  --description="Blog module" \
  --author="Trix 官方" \
  --author-url="https://www.trixmore.lav"
```

如果模块后续要发布到 Trixmore，`author` 必须填写为 Auth Key 对应用户的名称或邮箱。

如果模块目录已经存在，并且只想刷新 Lartrix 标准文件：

```bash
php artisan lartrix:module-make Blog --force
```

`--force` 会覆盖 `module.json`、默认 logo 和默认缩略图，不会清理或重建业务代码。

业务代码仍然继续使用 Laravel / nwidart 原生命令追加：

```bash
php artisan module:make-model Post Blog
php artisan module:make-controller PostController Blog
php artisan module:make-migration create_posts_table Blog
```

## 项目清单与发布

项目是多个模块的组合方案，使用项目根目录的 `trix-project.json` 描述，不放在 `Modules` 目录中。

生成项目清单：

```bash
php artisan lartrix:project-make --id=official.mall-starter --name="Mall Starter" --author="Trix 官方"
```

同步当前已安装模块到已有清单：

```bash
php artisan lartrix:project-make --sync
```

`trix-project.json` 使用 `schema_version=trix.project.v1`，关键字段包括：

```json
{
  "schema_version": "trix.project.v1",
  "id": "official.mall-starter",
  "version": "1.0.0",
  "author": "Trix 官方",
  "adapter": {
    "language": "php",
    "framework": "laravel"
  },
  "modules": [
    {
      "id": "official.user",
      "version_constraint": "^1.0",
      "required": true,
      "config": {}
    }
  ],
  "contract_bindings": {},
  "config": {},
  "setup": {
    "seeders": [],
    "commands": []
  }
}
```

发布项目前需要配置模块市场地址和 Auth Key。`author` 必须匹配 Auth Key 对应用户的名称或邮箱，同一个项目再次发布时版本号必须高于市场最新版本。

```bash
php artisan lartrix:project-publish
```

仅校验不提交：

```bash
php artisan lartrix:project-publish --dry-run
```

项目发布只要求项目本身属于当前作者。项目依赖的模块可以来自官方或其他作者，因为项目表达的是组合、版本约束、契约绑定和配置覆盖，不代表重新发布这些模块。

## 项目安装计划

执行项目安装命令时，Lartrix 会从 Trixmore Registry 获取并执行项目安装计划。所有模块成功安装后，项目运行时配置统一写入：

```text
config/trix-project.php
```

该文件是项目配置、模块版本与覆盖配置、契约绑定和安装后引导的唯一运行时事实来源，不再生成平行 JSON 副本。市场弹窗只负责获取安装计划，不会在尚未安装模块时提前应用配置。

模块读取项目覆盖配置时统一使用 Laravel 配置仓库，例如：

```php
$moduleConfig = config('trix-project.modules.official.user.config', []);
$binding = config('trix-project.contract_bindings.user.account');
```

模块市场连接统一配置在 `config/lartrix.php` 的 `module_market` 下，包含 `enabled`、`url`、`auth_key`、`signature_key`、`timeout`、`cache_ttl`。对应环境变量使用 `LARTRIX_MODULE_MARKET_*`，认证密钥使用 `TRIX_AUTH_KEY`；不再使用 `module_registry` 或 `module_market.api_url`。
