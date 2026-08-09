# System Settings API

## Get Settings List

```http
GET /api/admin/settings
Authorization: Bearer {token}
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| group | string | Group name |

### Response Example

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "site": {
            "title": "Admin System",
            "logo": "/logo.png",
            "copyright": "© 2024"
        },
        "email": {
            "smtp_host": "smtp.example.com",
            "smtp_port": 587
        }
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```

## Get Group Settings

```http
GET /api/admin/settings/{group}
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "title": "Admin System",
        "logo": "/logo.png"
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```

## Create Setting

```http
POST /api/admin/settings
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| key | string | Yes | Setting key |
| value | mixed | Yes | Setting value |
| group | string | No | Group, default: default |
| type | string | No | Type: string, number, boolean, json |

### Response Example

```json
{
    "code": 0,
    "msg": "Created successfully",
    "data": {
        "id": 1,
        "key": "site.title",
        "value": "New Title"
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```

**422 Validation Failed**
```json
{
    "code": 422,
    "msg": "Validation failed",
    "data": {
        "errors": {
            "key": ["Key is required"],
            "value": ["Value is required"]
        }
    }
}
```

## Batch Update Settings

```http
PUT /api/admin/settings
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| settings | object | Yes | Setting key-value pairs |

### Request Example

```json
{
    "settings": {
        "site.title": "New Title",
        "site.logo": "/new-logo.png"
    }
}
```

### Response Example

```json
{
    "code": 0,
    "msg": "Updated successfully",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```

**422 Validation Failed**
```json
{
    "code": 422,
    "msg": "Validation failed",
    "data": {
        "errors": {
            "settings": ["Settings is required"]
        }
    }
}
```

## Delete Setting

```http
DELETE /api/admin/settings/{id}
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 0,
    "msg": "Deleted successfully",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```

**404 Not Found**
```json
{
    "code": 404,
    "msg": "Resource not found",
    "data": null
}
```

## Refresh Settings Cache

```http
POST /api/admin/settings/refresh
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 0,
    "msg": "Cache refreshed",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "msg": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "msg": "Forbidden",
    "data": null
}
```
