# API 参考

Lartrix 提供完整的 RESTful API，用于构建后台管理系统。

## API 规范

### 基础 URL

```
/api/admin
```

### 认证方式

使用 Laravel Sanctum 的 Token 认证：

```http
Authorization: Bearer {token}
```

### 响应格式

所有响应都使用统一的 JSON 格式：

```json
{
    "code": 0,
    "msg": "操作成功",
    "data": { }
}
```

`code = 0` 表示成功；业务错误使用业务错误码（如 `40001` 用户名或密码错误）：

```json
{
    "code": 40001,
    "msg": "用户名或密码错误",
    "data": null
}
```

### HTTP 状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 成功 |
| 401 | 未认证 |
| 403 | 无权限 |
| 422 | 验证失败 |
| 401 | 未认证 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器错误 |

## API 分类

- [认证 API](/api/auth) - 登录、登出、刷新 Token
- [用户管理](/api/users) - 用户的增删改查
- [角色管理](/api/roles) - 角色的增删改查
- [权限管理](/api/permissions) - 权限列表
- [菜单管理](/api/menus) - 菜单的增删改查
- [模块管理](/api/modules) - 模块管理
- [系统设置](/api/settings) - 系统配置
- [数据字典](/api/dict) - 字典数据
- [通知管理](/api/notifications) - 通知消息

## 通用参数

### 分页参数

```
GET /users?page=1&per_page=15
```

| 参数 | 说明 | 默认值 |
|------|------|--------|
| page | 当前页码 | 1 |
| per_page | 每页数量 | 15 |

### 排序参数

```
GET /users?order_by=created_at&order=desc
```

### 搜索参数

```
GET /users?keyword=john
```
