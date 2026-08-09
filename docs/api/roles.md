# 角色管理 API

## 获取角色列表

```http
GET /api/admin/roles
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| page | int | 页码 |
| per_page | int | 每页数量 |
| keyword | string | 关键词搜索 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "name": "super-admin",
                "guard_name": "admin",
                "created_at": "2024-01-01 00:00:00"
            }
        ],
        "total": 5
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

## 创建角色

```http
POST /api/admin/roles
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 角色标识 |
| guard_name | string | 否 | Guard，默认 admin |
| permission_ids | array | 否 | 权限ID列表 |

### 响应示例

```json
{
    "code": 0,
    "msg": "创建成功",
    "data": {
        "id": 2,
        "name": "editor",
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
            "name": ["角色标识不能为空"],
            "permission_ids": ["权限ID必须是数组"]
        }
    }
}
```

## 获取角色详情

```http
GET /api/admin/roles/{id}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "id": 1,
        "name": "super-admin",
        "permissions": [
            {"id": 1, "name": "users.list"},
            {"id": 2, "name": "users.create"}
        ]
    }
}
```

## 更新角色

```http
PUT /api/admin/roles/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 角色标识 |
| permission_ids | array | 否 | 权限ID列表 |

### 响应示例

```json
{
    "code": 0,
    "msg": "更新成功",
    "data": null
}
```

## 删除角色

```http
DELETE /api/admin/roles/{id}
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
    "msg": "角色不存在",
    "data": null
}
```

## 获取所有角色（不分页）

```http
GET /api/admin/roles/all
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {"id": 1, "name": "super-admin"},
        {"id": 2, "name": "editor"}
    ]
}
```
