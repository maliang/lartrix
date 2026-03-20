# 用户管理 API

## 获取用户列表

```http
GET /api/admin/users
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| page | int | 页码，默认 1 |
| per_page | int | 每页数量，默认 15 |
| keyword | string | 关键词搜索（姓名/邮箱） |
| status | int | 状态筛选：0=禁用，1=启用 |

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "name": "管理员",
                "email": "admin@example.com",
                "status": true,
                "created_at": "2024-01-01 00:00:00"
            }
        ],
        "total": 100,
        "per_page": 15,
        "current_page": 1
    }
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "message": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "message": "无权限访问",
    "data": null
}
```

## 创建用户

```http
POST /api/admin/users
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 姓名 |
| email | string | 是 | 邮箱 |
| phone | string | 否 | 电话 |
| password | string | 是 | 密码 |
| status | boolean | 否 | 状态，默认 true |
| role_ids | array | 否 | 角色ID列表 |

### 响应示例

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 2,
        "name": "新用户",
        "email": "user@example.com",
        "status": true
    }
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "message": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "message": "无权限访问",
    "data": null
}
```

**422 验证失败**
```json
{
    "code": 422,
    "message": "验证失败",
    "data": {
        "errors": {
            "email": ["邮箱格式不正确"],
            "password": ["密码长度至少 6 位"]
        }
    }
}
```

## 获取用户详情

```http
GET /api/admin/users/{id}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "id": 1,
        "name": "管理员",
        "email": "admin@example.com",
        "roles": [{"id": 1, "name": "super_admin"}],
        "permissions": ["*"]
    }
}
```

## 更新用户

```http
PUT /api/admin/users/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 姓名 |
| email | string | 否 | 邮箱 |
| phone | string | 否 | 电话 |
| password | string | 否 | 密码（不传则不修改）|
| status | boolean | 否 | 状态 |
| role_ids | array | 否 | 角色ID列表 |

### 响应示例

```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 1,
        "name": "新名称"
    }
}
```

## 更新用户状态

```http
PUT /api/admin/users/{id}?action_type=status
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | boolean | 是 | 状态 |

### 响应示例

```json
{
    "code": 200,
    "message": "状态更新成功",
    "data": null
}
```

## 删除用户

```http
DELETE /api/admin/users/{id}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

### 错误响应

**401 未认证**
```json
{
    "code": 401,
    "message": "未认证",
    "data": null
}
```

**403 无权限**
```json
{
    "code": 403,
    "message": "无权限访问",
    "data": null
}
```

**404 资源不存在**
```json
{
    "code": 404,
    "message": "用户不存在",
    "data": null
}
```

## 批量删除

```http
DELETE /api/admin/users?action_type=batch
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | 是 | 用户ID列表 |

### 响应示例

```json
{
    "code": 200,
    "message": "批量删除成功",
    "data": null
}
```

## 获取列表 UI

```http
GET /api/admin/users?action_type=list_ui
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        // vschema-ui Schema 对象
    }
}
```

## 获取表单 UI

```http
GET /api/admin/users?action_type=form_ui
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        // 表单 Schema 对象
    }
}
```
