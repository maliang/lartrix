# Data Dictionary API

## Get Dictionary Options

```http
GET /api/admin/dicts/options
Authorization: Bearer {token}
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| code | string | Yes | Dictionary code |

### Response Example

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {"label": "Enabled", "value": 1},
        {"label": "Disabled", "value": 0}
    ]
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

## Get Dictionary Group List

```http
GET /api/admin/dict/groups
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "code": "status",
                "name": "Status",
                "description": "Common status"
            }
        ],
        "total": 10
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

## Create Dictionary Group

```http
POST /api/admin/dict/groups
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| code | string | Yes | Group code |
| name | string | Yes | Group name |
| description | string | No | Description |

### Response Example

```json
{
    "code": 0,
    "msg": "Created successfully",
    "data": {
        "id": 1,
        "code": "status",
        "name": "Status"
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
            "code": ["Code already exists"],
            "name": ["Name is required"]
        }
    }
}
```

## Update Dictionary Group

```http
PUT /api/admin/dict/groups/{id}
Authorization: Bearer {token}
Content-Type: application/json
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

**404 Not Found**
```json
{
    "code": 404,
    "msg": "Resource not found",
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
            "code": ["Code already exists"]
        }
    }
}
```

## Delete Dictionary Group

```http
DELETE /api/admin/dict/groups/{id}
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

## Get Dictionary Item List

```http
GET /api/admin/dict/items
Authorization: Bearer {token}
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| group_id | int | Group ID filter |

### Response Example

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "group_id": 1,
                "label": "Enabled",
                "value": "1",
                "sort": 0
            }
        ],
        "total": 10
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

## Create Dictionary Item

```http
POST /api/admin/dict/items
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| label | string | Yes | Label |
| value | string | Yes | Value |
| sort | int | No | Sort order |

### Response Example

```json
{
    "code": 0,
    "msg": "Created successfully",
    "data": {
        "id": 1,
        "label": "Enabled",
        "value": "1"
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
            "group_id": ["Group not found"],
            "label": ["Label is required"]
        }
    }
}
```

## Update Dictionary Item

```http
PUT /api/admin/dict/items/{id}
Authorization: Bearer {token}
Content-Type: application/json
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

**422 Validation Failed**
```json
{
    "code": 422,
    "msg": "Validation failed",
    "data": {
        "errors": {
            "label": ["Label is required"]
        }
    }
}
```

## Delete Dictionary Item

```http
DELETE /api/admin/dict/items/{id}
Authorization: Bearer {token}
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
