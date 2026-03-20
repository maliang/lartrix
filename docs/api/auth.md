# 认证 API

## 登录

```http
POST /api/admin/auth/login
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| username | string | 是 | 用户名 |
| password | string | 是 | 密码 |

### 响应示例

```json
{
    "code": 200,
    "message": "登录成功",
    "data": {
        "token": "1|xxxxxxxxxxxx",
        "user": {
            "id": 1,
            "name": "管理员",
            "email": "admin@example.com"
        }
    }
}
```

### 错误响应

**422 验证失败**
```json
{
    "code": 422,
    "message": "验证失败",
    "data": {
        "errors": {
            "username": ["用户名不能为空"],
            "password": ["密码不能为空"]
        }
    }
}
```

**401 认证失败**
```json
{
    "code": 401,
    "message": "用户名或密码错误",
    "data": null
}
```

## 登出

```http
POST /api/admin/auth/logout
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "登出成功",
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

## 刷新 Token

```http
POST /api/admin/auth/refresh
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "刷新成功",
    "data": {
        "token": "2|yyyyyyyyyyyy"
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

## 获取当前用户

```http
GET /api/admin/auth/user
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
        "roles": ["super_admin"],
        "permissions": ["*"]
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

## 获取配置

```http
GET /api/admin/auth/config
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "app_title": "管理系统",
        "logo": "/admin/logo.svg",
        "copyright": "© 2024"
    }
}
```
