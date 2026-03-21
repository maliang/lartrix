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
    "code": 200,
    "message": "success",
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
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
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
    "code": 200,
    "message": "success",
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
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
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
    "code": 200,
    "message": "Created successfully",
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
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**422 Validation Failed**
```json
{
    "code": 422,
    "message": "Validation failed",
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
    "code": 200,
    "message": "Updated successfully",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**422 Validation Failed**
```json
{
    "code": 422,
    "message": "Validation failed",
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
    "code": 200,
    "message": "Deleted successfully",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**404 Not Found**
```json
{
    "code": 404,
    "message": "Resource not found",
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
    "code": 200,
    "message": "Cache refreshed",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```
