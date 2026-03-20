# 模块管理 API

## 获取模块列表

```http
GET /api/admin/modules
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "name": "Blog",
            "alias": "blog",
            "description": "博客模块",
            "version": "1.0.0",
            "enabled": true,
            "order": 0
        }
    ]
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

## 启用模块

```http
POST /api/admin/modules/{name}/enable
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "模块已启用",
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
    "message": "模块不存在",
    "data": null
}
```

## 禁用模块

```http
POST /api/admin/modules/{name}/disable
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "模块已禁用",
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
    "message": "模块不存在",
    "data": null
}
```

## 获取模块详情

```http
GET /api/admin/modules/{name}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "name": "Blog",
        "alias": "blog",
        "description": "博客模块",
        "version": "1.0.0",
        "enabled": true,
        "providers": [...],
        "requires": []
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

**404 资源不存在**
```json
{
    "code": 404,
    "message": "模块不存在",
    "data": null
}
```

## 获取模块 Logo

```http
GET /api/admin/modules/{name}/logo
```

无需认证，直接返回图片。

### 响应

图片文件（png, jpg, svg 等）
