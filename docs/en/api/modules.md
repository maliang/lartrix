# Module Management API

## Get Module List

```http
GET /api/admin/modules
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "name": "Blog",
            "alias": "blog",
            "description": "Blog module",
            "version": "1.0.0",
            "enabled": true,
            "order": 0
        }
    ]
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

## Enable Module

```http
POST /api/admin/modules/{name}/enable
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "Module enabled",
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
    "message": "Module not found",
    "data": null
}
```

## Disable Module

```http
POST /api/admin/modules/{name}/disable
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "Module disabled",
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
    "message": "Module not found",
    "data": null
}
```

## Get Module Details

```http
GET /api/admin/modules/{name}
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "name": "Blog",
        "alias": "blog",
        "description": "Blog module",
        "version": "1.0.0",
        "enabled": true,
        "providers": [...],
        "requires": []
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

**404 Not Found**
```json
{
    "code": 404,
    "message": "Module not found",
    "data": null
}
```

## Get Module Logo

```http
GET /api/admin/modules/{name}/logo
```

No authentication required, returns image directly.

### Response

Image file (png, jpg, svg, etc.)
