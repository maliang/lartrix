# Services

## BaseService

服务基类。

### 命名空间

```php
Lartrix\\Services\\BaseService
```

### 方法

| 方法 | 说明 |
|------|------|
| `success($data, $message)` | 成功响应 |
| `error($message, $code)` | 错误响应 |

## AuthService

认证服务。

### 命名空间

```php
Lartrix\\Services\\AuthService
```

### 方法

| 方法 | 说明 |
|------|------|
| `login($credentials)` | 用户登录 |
| `logout()` | 用户登出 |
| `refresh()` | 刷新 Token |
| `getUser()` | 获取当前用户 |

## PermissionService

权限服务。

### 命名空间

```php
Lartrix\\Services\\PermissionService
```

### 方法

| 方法 | 说明 |
|------|------|
| `getUserPermissions($user)` | 获取用户权限 |
| `hasPermission($user, $permission)` | 检查权限 |
| `syncPermissions($role, $permissions)` | 同步权限 |
| `getPermissionGroups()` | 获取权限分组 |

## ModuleService

模块服务。

### 命名空间

```php
Lartrix\\Services\\ModuleService
```

### 方法

| 方法 | 说明 |
|------|------|
| `getAllModules()` | 获取所有模块 |
| `enable($name)` | 启用模块 |
| `disable($name)` | 禁用模块 |
| `getModuleInfo($name)` | 获取模块信息 |

## DataDictService

数据字典服务。

### 命名空间

```php
Lartrix\\Services\\DataDictService
```

### 方法

| 方法 | 说明 |
|------|------|
| `getOptions($code)` | 获取选项列表 |
| `getGroups()` | 获取分组列表 |
| `getItems($groupId)` | 获取字典项 |
| `refreshCache()` | 刷新缓存 |
