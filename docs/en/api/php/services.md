# Services

## BaseService

Base service class.

### Namespace

```php
Lartrix\Services\BaseService
```

### Methods

| Method | Description |
|--------|-------------|
| `success($data, $message)` | Success response |
| `error($message, $code)` | Error response |

## AuthService

Authentication service.

### Namespace

```php
Lartrix\Services\AuthService
```

### Methods

| Method | Description |
|--------|-------------|
| `login($credentials)` | User login |
| `logout()` | User logout |
| `refresh()` | Refresh token |
| `getUser()` | Get current user |

## PermissionService

Permission service.

### Namespace

```php
Lartrix\Services\PermissionService
```

### Methods

| Method | Description |
|--------|-------------|
| `getUserPermissions($user)` | Get user permissions |
| `hasPermission($user, $permission)` | Check permission |
| `syncPermissions($role, $permissions)` | Sync permissions |
| `getPermissionGroups()` | Get permission groups |

## ModuleService

Module service.

### Namespace

```php
Lartrix\Services\ModuleService
```

### Methods

| Method | Description |
|--------|-------------|
| `getAllModules()` | Get all modules |
| `enable($name)` | Enable module |
| `disable($name)` | Disable module |
| `getModuleInfo($name)` | Get module info |

## DataDictService

Data dictionary service.

### Namespace

```php
Lartrix\Services\DataDictService
```

### Methods

| Method | Description |
|--------|-------------|
| `getOptions($code)` | Get options list |
| `getGroups()` | Get group list |
| `getItems($groupId)` | Get dictionary items |
| `refreshCache()` | Refresh cache |
