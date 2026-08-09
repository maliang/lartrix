# 二级后台

Lartrix 支持创建独立的二级后台系统，如商户后台、代理后台等。每个二级后台拥有**独立的 guard、用户表、菜单与权限**，通过 `guard_name` 与总后台及其他后台完全隔离。

## 创建二级后台

```bash
php artisan lartrix:make-backend Merchant \
    --path=/merchant \
    --api-prefix=api/merchant \
    --title=商户管理系统
```

参数说明：

| 参数 | 说明 | 默认值 |
|------|------|--------|
| name | 模块名称（StudlyCase） | 必填 |
| --path | 前端访问路径 | /{name} |
| --api-prefix | API 前缀 | api/{name} |
| --table | 用户表名 | {name}s |
| --title | 后台标题 | {name}管理系统 |

命令会**自动完成**：创建 nwidart 模块、写入 `config/auth.php` 的 guard/provider、运行模块迁移、执行 Seeder（菜单/权限/角色/管理员）、启用模块。

## 生成的结构

```
Modules/Merchant/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php      # 登录/登出/刷新/当前用户/个人资料/账号设置/修改密码
│   │   ├── UserController.php      # 管理员管理（继承 Lartrix\UserController）
│   │   ├── RoleController.php      # 角色管理（继承 Lartrix\RoleController）
│   │   ├── MenuController.php      # 菜单管理（继承 Lartrix\MenuController）
│   │   ├── PermissionController.php# 权限管理（继承 Lartrix\PermissionController）
│   │   └── SystemController.php    # 登录页/首页/主题/语言/头部
│   ├── Models/
│   │   └── Merchant.php            # 独立用户模型（isSuperAdmin/isActive/SoftDeletes）
│   └── Providers/                  # MerchantServiceProvider + RouteServiceProvider
├── config/config.php               # backend 段（path/api_prefix/guard/model/table）
├── database/
│   ├── migrations/                 # 用户表迁移
│   └── seeders/MerchantBackendSeeder.php
└── routes/api.php                  # api/{name}/* 路由
```

控制器均继承 Lartrix 对应基类并覆盖 `getGuard()`，按模块 guard 过滤菜单/角色/权限/用户。

## 认证配置

`lartrix:make-backend` 已自动在 `config/auth.php` 写入：

```php
'guards' => [
    'merchant' => [
        'driver' => 'sanctum',
        'provider' => 'merchants',
    ],
],
'providers' => [
    'merchants' => [
        'driver' => 'eloquent',
        'model' => \Modules\Merchant\Models\Merchant::class,
    ],
],
```

重新安装（`lartrix:backend-install`）时会自动补全，无需手工编辑。

## 初始化数据

`lartrix:make-backend` 创建时自动执行 `{Name}BackendSeeder`，创建：

- 默认菜单（首页 + 系统管理：菜单/角色/权限/管理员）
- 权限数据（四组权限，含子权限树）
- 通知分类
- `super-admin` 角色（`guard_name = merchant`）
- `admin / 123456` 管理员账号

> 注意：不要使用 `php artisan module:seed Merchant` 初始化——nwidart 的 `module:seed` 只识别 `{Name}DatabaseSeeder`，而 make-backend 生成的是 `{Name}BackendSeeder`，`module:seed` 不会执行它。

## 重新安装（新库 / 新环境）

把已有二级后台模块迁移到**新数据库**时，模块的迁移与 Seeder 不会自动补跑。使用重新安装命令一键补齐：

```bash
php artisan lartrix:backend-install Merchant
```

命令依次执行（全部**幂等**，可重复运行）：

1. 补 `config/auth.php` 的 guard/provider（已存在则跳过）
2. 启用模块并同步 `modules` 表
3. 运行模块迁移（`module:migrate`，已执行过的自动跳过）
4. 执行 `{Name}BackendSeeder` 数据填充
5. 输出 guard / 路径 / 管理员账号摘要

适用于：新库初始化、迁移测试环境、从旧库导入模块代码后重建数据等场景。

## 数据隔离

二级后台通过 `guard_name` 字段隔离数据：

```php
// 菜单隔离
Menu::where('guard_name', 'merchant')->get();

// 角色隔离
Role::where('guard_name', 'merchant')->get();

// 权限隔离
Permission::where('guard_name', 'merchant')->get();
```

`super-admin` 角色（`guard_name = merchant`）在二级后台拥有**本 guard 全部权限**（不跨 guard）。

## 添加业务功能

在二级后台中添加业务控制器：

```php
<?php

namespace Modules\Merchant\Http\Controllers;

use Lartrix\Controllers\CrudController;

class ProductController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\Merchant\Models\Product::class;
    }

    protected function getResourceName(): string
    {
        return '商品';
    }

    // 限制只能访问当前商户的数据
    protected function applyFilters($query, $request): void
    {
        $query->where('merchant_id', $request->user()->id);
    }
}
```

在 `routes/api.php` 的认证组内注册：

```php
Route::middleware('auth:merchant')->get('products', [ProductController::class, 'index']);
```

## 用户自助接口

生成的二级后台自带当前用户自助接口（前端"个人中心 / 账号设置 / 修改密码"弹窗使用）：

| 接口 | 说明 |
|------|------|
| `GET /api/merchant/user/profile/ui` | 个人资料弹窗 UI Schema |
| `POST /api/merchant/user/profile` | 保存个人资料 |
| `GET /api/merchant/user/settings/ui` | 账号设置弹窗 UI Schema |
| `POST /api/merchant/user/settings` | 保存语言偏好等设置 |
| `GET /api/merchant/user/password/ui` | 修改密码弹窗 UI Schema |
| `POST /api/merchant/user/password` | 修改当前用户密码 |

## 主题与语言

- 主题配置按模块独立存储（设置分组 `theme.{模块名}`），`GET/POST /api/merchant/theme-config` 读取/保存
- 语言切换 `POST /api/merchant/locale`、语言包 `GET /api/merchant/translations`，用户语言偏好写入 `locale` 字段

## 访问二级后台

启动服务后访问：

```
http://localhost:8000/merchant
```

前端共享 `public/admin/` 资源，入口注入 `window.__LARTRIX_CONFIG__`（apiPrefix/appTitle/logo）。

## 移除二级后台

```bash
php artisan lartrix:remove-backend Merchant
```

## 从旧版本升级说明

- `lartrix:backend-install` 依赖 Lartrix **v1.0.75+**；若宿主版本过低，先 `composer update lartrix/lartrix`
- 老宿主升级时若 `admin_users` 表缺少 `locale` 列（语言偏好保存报错），需手动补列或使用新迁移
