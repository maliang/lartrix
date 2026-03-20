# 二级后台

Lartrix 支持创建独立的二级后台系统，如商户后台、代理后台等。

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
| name | 模块名称 | 必填 |
| --path | 前端访问路径 | /{name} |
| --api-prefix | API 前缀 | api/{name} |
| --table | 用户表名 | {name}s |
| --title | 后台标题 | {name}管理系统 |

## 生成的结构

```
Modules/Merchant/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── MenuController.php
│   │   └── PermissionController.php
│   ├── Models/
│   │   └── Merchant.php
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/api.php
```

## 配置认证

在 `config/auth.php` 中添加：

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
        'model' => \Modules\\Merchant\\Models\\Merchant::class,
    ],
],
```

## 初始化数据

```bash
php artisan migrate
php artisan module:seed Merchant
```

这会创建：
- 超级管理员角色
- 初始权限数据
- 默认菜单

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

## 添加业务功能

在二级后台中添加业务控制器：

```php
<?php

namespace Modules\\Merchant\\Http\\Controllers;

use Lartrix\\Controllers\\CrudController;

class ProductController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\\Merchant\\Models\\Product::class;
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

## 访问二级后台

启动服务后访问：

```
http://localhost:8000/merchant
```

## 移除二级后台

```bash
php artisan lartrix:remove-backend Merchant
```
