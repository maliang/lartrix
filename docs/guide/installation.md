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

## 安装步骤

### 1. 安装 Lartrix

```bash
composer require lartrix/lartrix
```

### 2. 运行安装命令

```bash
php artisan lartrix:install
```

安装命令会执行以下操作：

- 发布配置文件
- 发布数据库迁移文件
- 运行数据库迁移
- 创建初始权限数据
- 发布前端资源

### 3. 创建超级管理员

```bash
php artisan db:seed --class=Lartrix\\Database\\Seeders\\AdminUserSeeder
```

默认账号：
- 用户名: admin
- 密码: admin123

### 4. 访问后台

```bash
php artisan serve
```

访问：`http://localhost:8000/admin`

## 下一步

- [快速开始](/guide/quickstart) - 创建你的第一个 CRUD 页面
