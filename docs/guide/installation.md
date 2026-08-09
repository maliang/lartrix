# 安装

::: info 版本信息
当前文档对应 Lartrix v1.x 版本。
:::

## 环境要求

| 依赖 | 版本要求 |
|------|----------|
| PHP | ^8.1 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 |
| Composer | 2.x |

依赖包：`laravel/sanctum`、`spatie/laravel-permission`、`nwidart/laravel-modules`。

## 安装步骤

### 1. 安装 Lartrix

```bash
composer require lartrix/lartrix
```

### 2. 运行安装命令

```bash
php artisan lartrix:install
```

安装命令会按序执行：

1. 发布前端资源到 `public/admin`
2. 发布依赖包配置与迁移（spatie/sanctum/modules）
3. 发布 Lartrix 配置文件（`config/lartrix.php`）
4. 配置 `config/auth.php`（admin guard/provider）
5. 配置 composer merge-plugin（模块化开发）
6. 运行数据库迁移
7. 创建超级管理员角色与基础权限
8. 初始化系统设置与默认菜单
9. 创建超级管理员账户（交互式设置密码）
10. 创建 AI 开发指南文件（AGENTS.md、CLAUDE.md）

### 3. 登录后台

默认管理员账号由安装过程交互式创建，用户名 `admin`，密码在安装时设置（默认示例 `123456`）。

```bash
php artisan serve
```

访问：`http://localhost:8000/admin`（路径由 `config/lartrix.php` 的 `path` 决定）

## 已有数据保护

- `lartrix:install` 检测到已有数据时会**二次确认**，取消则中止
- 已存在的 `config/lartrix.php` 默认**不会被覆盖**（保持用户自定义）
- 需要显式覆盖配置时使用 `--force`：

```bash
php artisan lartrix:install --force
```

> 从旧版本升级时若需同步最新配置结构（如新增的 `notification`/`realtime` 键），可执行
> `php artisan vendor:publish --tag=lartrix-config --force` 后手动合并自定义项。

## 发布前端资源

前端资源更新后（或 `public/admin` 缺失时）重新发布：

```bash
php artisan lartrix:publish-assets
```

## 测试

```bash
php -d extension=pdo_sqlite ./vendor/bin/phpunit
```

测试套件使用 SQLite 内存库，需要 PHP 的 `pdo_sqlite` 扩展。

## 下一步

- [快速开始](/guide/quickstart) - 创建你的第一个 CRUD 页面
- [二级后台](/guide/sub-admin) - 创建独立的商户/代理后台
