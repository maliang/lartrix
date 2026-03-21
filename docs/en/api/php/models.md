# Models

## AdminUser

Admin user model.

### Namespace

```php
Lartrix\Models\AdminUser
```

### Traits

- `HasApiTokens` - Laravel Sanctum
- `HasRoles` - Spatie Permission

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| name | string | Name |
| email | string | Email |
| phone | string | Phone |
| avatar | string | Avatar |
| status | bool | Status |
| password | string | Password |

### Methods

| Method | Description |
|--------|-------------|
| `roles()` | Role relation |
| `permissions()` | Permission relation |
| `hasRole($role)` | Check role |
| `hasPermission($permission)` | Check permission |

## Role

Role model.

### Namespace

```php
Lartrix\Models\Role
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| name | string | Role identifier |
| guard_name | string | Guard |

### Methods

| Method | Description |
|--------|-------------|
| `permissions()` | Permission relation |
| `users()` | User relation |
| `givePermissionTo($permissions)` | Grant permissions |
| `revokePermissionTo($permissions)` | Revoke permissions |

## Permission

Permission model.

### Namespace

```php
Lartrix\Models\Permission
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| name | string | Permission identifier |
| guard_name | string | Guard |

## Menu

Menu model.

### Namespace

```php
Lartrix\Models\Menu
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| parent_id | int | Parent menu ID |
| title | string | Title |
| icon | string | Icon |
| path | string | Path |
| component | string | Component |
| permission | string | Permission |
| sort | int | Sort order |
| guard_name | string | Guard |
| hidden | bool | Hidden |

### Methods

| Method | Description |
|--------|-------------|
| `parent()` | Parent menu |
| `children()` | Child menus |
| `getTree($guard)` | Get menu tree |

## Module

Module model.

### Namespace

```php
Lartrix\Models\Module
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| name | string | Name |
| alias | string | Alias |
| description | string | Description |
| version | string | Version |
| enabled | bool | Enabled |

## Setting

Setting model.

### Namespace

```php
Lartrix\Models\Setting
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| key | string | Key |
| value | mixed | Value |
| group | string | Group |
| type | string | Type |

### Methods

| Method | Description |
|--------|-------------|
| `get($key, $default)` | Get setting |
| `set($key, $value)` | Set value |
| `group($name)` | Get by group |

## DictGroup

Data dictionary group model.

### Namespace

```php
Lartrix\Models\DictGroup
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| code | string | Code |
| name | string | Name |
| description | string | Description |

### Methods

| Method | Description |
|--------|-------------|
| `items()` | Dictionary items relation |

## DictItem

Data dictionary item model.

### Namespace

```php
Lartrix\Models\DictItem
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| group_id | int | Group ID |
| label | string | Label |
| value | mixed | Value |
| sort | int | Sort order |

## NotificationCategory

Notification category model.

### Namespace

```php
Lartrix\Models\NotificationCategory
```

## NotificationMessage

Notification message model.

### Namespace

```php
Lartrix\Models\NotificationMessage
```

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| id | int | ID |
| user_id | int | User ID |
| category_id | int | Category ID |
| title | string | Title |
| content | string | Content |
| is_read | bool | Read status |
| read_at | datetime | Read time |
