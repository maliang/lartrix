# 权限管理 API

## 获取权限列表

```http
GET /api/admin/permissions
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| guard_name | string | Guard 名称筛选 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "name": "users.list",
                "guard_name": "admin"
            },
            {
                "id": 2,
                "name": "users.create",
                "guard_name": "admin"
            }
        ]
    }
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "msg": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "msg": "无权限访问",
    "data": null
}
```

## 获取权限分组

```http
GET /api/admin/permissions/groups
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "users": [
            {"id": 1, "name": "users.list"},
            {"id": 2, "name": "users.create"},
            {"id": 3, "name": "users.update"},
            {"id": 4, "name": "users.delete"}
        ],
        "posts": [
            {"id": 5, "name": "posts.list"},
            {"id": 6, "name": "posts.create"}
        ]
    }
}
```

## 创建权限

```http
POST /api/admin/permissions
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 权限标识 |
| guard_name | string | 否 | Guard，默认 admin |

### 响应示例

```json
{
    "code": 0,
    "msg": "创建成功",
    "data": {
        "id": 10,
        "name": "custom.permission",
        "guard_name": "admin"
    }
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "msg": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "msg": "无权限访问",
    "data": null
}
```

**422 验证失败**
```json
{
    "code": 422,
    "msg": "验证失败",
    "data": {
        "errors": {
            "name": ["权限标识不能为空"]
        }
    }
}
```

## 删除权限

```http
DELETE /api/admin/permissions/{id}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "删除成功",
    "data": null
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "msg": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "msg": "无权限访问",
    "data": null
}
```

**404 资源不存在**
```json
{
    "code": 404,
    "msg": "权限不存在",
    "data": null
}
```

## 刷新权限缓存

```http
POST /api/admin/permissions/refresh
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "缓存已刷新",
    "data": null
}
```
