# 系统设置 API

## 获取设置列表

```http
GET /api/admin/settings
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| group | string | 分组名称 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "site": {
            "title": "管理系统",
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

## 获取分组设置

```http
GET /api/admin/settings/{group}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "title": "管理系统",
        "logo": "/logo.png"
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

## 创建设置

```http
POST /api/admin/settings
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| key | string | 是 | 设置键 |
| value | mixed | 是 | 设置值 |
| group | string | 否 | 分组，默认 default |
| type | string | 否 | 类型：string, number, boolean, json |

### 响应示例

```json
{
    "code": 0,
    "msg": "创建成功",
    "data": {
        "id": 1,
        "key": "site.title",
        "value": "新标题"
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
            "key": ["键不能为空"],
            "value": ["值不能为空"]
        }
    }
}
```

## 批量更新设置

```http
PUT /api/admin/settings
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| settings | object | 是 | 设置键值对 |

### 请求示例

```json
{
    "settings": {
        "site.title": "新标题",
        "site.logo": "/new-logo.png"
    }
}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "更新成功",
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

**422 验证失败**
```json
{
    "code": 422,
    "msg": "验证失败",
    "data": {
        "errors": {
            "settings": ["设置不能为空"]
        }
    }
}
```

## 删除设置

```http
DELETE /api/admin/settings/{id}
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
    "msg": "资源不存在",
    "data": null
}
```

## 刷新设置缓存

```http
POST /api/admin/settings/refresh
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
