# 认证 API

统一响应格式：

```json
{
    "code": 0,
    "msg": "操作成功",
    "data": {}
}
```

`code = 0` 表示成功；业务错误使用错误码（如 `40001` 用户名或密码错误）；HTTP 状态码由错误类型决定。

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
    "code": 0,
    "msg": "登录成功",
    "data": {
        "token": "1|xxxxxxxxxxxxxxxx"
    }
}
```

### 错误响应

**用户名或密码错误**

```json
{
    "code": 40001,
    "msg": "用户名或密码错误",
    "data": null
}
```

**账号已被禁用**

```json
{
    "code": 40003,
    "msg": "账号已被禁用",
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
    "code": 0,
    "msg": "登出成功",
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
    "code": 0,
    "msg": "刷新成功",
    "data": {
        "token": "2|yyyyyyyyyyyy"
    }
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
    "code": 0,
    "msg": "success",
    "data": {
        "id": 1,
        "username": "admin",
        "nickname": "超级管理员",
        "avatar": null,
        "email": "admin@example.com",
        "phone": null,
        "status": 1,
        "roles": ["super-admin"],
        "permissions": ["users.list", "users.create"],
        "locale": "zh-CN"
    }
}
```

- `permissions` 来自 `getActivePermissions()`：超级管理员返回全部权限；普通用户排除**禁用角色**的权限
- `roles` 为 spatie 角色名数组

## Token 管理

```http
GET /api/admin/auth/tokens                 # 当前用户所有 Token
DELETE /api/admin/auth/tokens/{id}         # 撤销指定 Token
Authorization: Bearer {token}
```

## 获取后台配置

```http
GET /api/admin/auth/config
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "apiPrefix": "/api/admin",
        "appTitle": "Lartrix Admin",
        "logo": "",
        "locale": "zh-CN",
        "fallbackLocale": "en-US",
        "languages": [
            { "label": "中文", "value": "zh-CN" },
            { "label": "English", "value": "en-US" }
        ],
        "translationsUrl": "/translations",
        "realtime": {
            "enabled": true,
            "driver": "polling",
            "polling": { "api": "/notifications/poll", "interval": 15000 }
        }
    }
}
```

## 用户自助接口

以下接口供前端"个人中心 / 账号设置 / 修改密码"弹窗使用，需登录。

### 个人资料

```http
GET /api/admin/user/profile/ui          # 弹窗 UI Schema（OptForm）
POST /api/admin/user/profile            # 保存资料：nickname/email/phone/avatar
```

### 账号设置

```http
GET /api/admin/user/settings/ui         # 弹窗 UI Schema
POST /api/admin/user/settings           # 保存设置：locale（语言偏好）
```

### 修改密码

```http
GET /api/admin/user/password/ui         # 弹窗 UI Schema
POST /api/admin/user/password
```

请求参数：

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| current_password | string | 是 | 当前密码 |
| new_password | string | 是 | 新密码（至少 6 位） |
| new_password_confirmation | string | 是 | 确认新密码 |

错误：当前密码不正确返回 `40022`。

## 其他认证相关接口

| 接口 | 说明 |
|------|------|
| `GET /api/admin/translations` | 语言包（`?locale=zh-CN`） |
| `POST /api/admin/locale` | 设置用户语言偏好 |
| `GET /api/admin/login/page` | 登录页 UI Schema |
| `GET /api/admin/system/theme-config` | 获取主题配置 |
| `POST /api/admin/system/theme-config` | 保存主题配置 |

二级后台的认证接口前缀为 `api/{模块小写名}`（如 `api/merchant/auth/login`），结构相同。
