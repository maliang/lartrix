# Models

## AdminUser

管理员用户模型。

### 命名空间

```php
Lartrix\\Models\\AdminUser
```

### Traits

- `HasApiTokens` - Laravel Sanctum
- `HasRoles` - Spatie Permission

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| name | string | 姓名 |
| email | string | 邮箱 |
| phone | string | 电话 |
| avatar | string | 头像 |
| status | bool | 状态 |
| password | string | 密码 |

### 方法

| 方法 | 说明 |
|------|------|
| `roles()` | 角色关联 |
| `permissions()` | 权限关联 |
| `hasRole($role)` | 检查角色 |
| `hasPermission($permission)` | 检查权限 |

## Role

角色模型。

### 命名空间

```php
Lartrix\\Models\\Role
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| name | string | 角色标识 |
| guard_name | string | Guard |

### 方法

| 方法 | 说明 |
|------|------|
| `permissions()` | 权限关联 |
| `users()` | 用户关联 |
| `givePermissionTo($permissions)` | 赋予权限 |
| `revokePermissionTo($permissions)` | 撤销权限 |

## Permission

权限模型。

### 命名空间

```php
Lartrix\\Models\\Permission
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| name | string | 权限标识 |
| guard_name | string | Guard |

## Menu

菜单模型。

### 命名空间

```php
Lartrix\\Models\\Menu
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| parent_id | int | 父菜单ID |
| title | string | 标题 |
| icon | string | 图标 |
| path | string | 路径 |
| component | string | 组件 |
| permission | string | 权限 |
| sort | int | 排序 |
| guard_name | string | Guard |
| hidden | bool | 是否隐藏 |

### 方法

| 方法 | 说明 |
|------|------|
| `parent()` | 父菜单 |
| `children()` | 子菜单 |
| `getTree($guard)` | 获取菜单树 |

## Module

模块模型。

### 命名空间

```php
Lartrix\\Models\\Module
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| name | string | 名称 |
| alias | string | 别名 |
| description | string | 描述 |
| version | string | 版本 |
| enabled | bool | 是否启用 |

## Setting

设置模型。

### 命名空间

```php
Lartrix\\Models\\Setting
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| key | string | 键 |
| value | mixed | 值 |
| group | string | 分组 |
| type | string | 类型 |

### 方法

| 方法 | 说明 |
|------|------|
| `get($key, $default)` | 获取设置 |
| `set($key, $value)` | 设置值 |
| `group($name)` | 按分组获取 |

## DictGroup

字典分组模型。

### 命名空间

```php
Lartrix\\Models\\DictGroup
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| code | string | 编码 |
| name | string | 名称 |
| description | string | 描述 |

### 方法

| 方法 | 说明 |
|------|------|
| `items()` | 字典项关联 |

## DictItem

字典项模型。

### 命名空间

```php
Lartrix\\Models\\DictItem
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| group_id | int | 分组ID |
| label | string | 标签 |
| value | mixed | 值 |
| sort | int | 排序 |

## NotificationCategory

通知分类模型。

### 命名空间

```php
Lartrix\\Models\\NotificationCategory
```

## NotificationMessage

通知消息模型。

### 命名空间

```php
Lartrix\\Models\\NotificationMessage
```

### 属性

| 属性 | 类型 | 说明 |
|------|------|------|
| id | int | ID |
| user_id | int | 用户ID |
| category_id | int | 分类ID |
| title | string | 标题 |
| content | string | 内容 |
| is_read | bool | 是否已读 |
| read_at | datetime | 阅读时间 |
