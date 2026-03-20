# 菜单管理 API

## 获取菜单列表

```http
GET /api/admin/menus
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| guard_name | string | Guard 名称，默认 admin |
| flat | boolean | 是否平铺返回 |

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": 1,
            "parent_id": 0,
            "title": "系统管理",
            "icon": "settings",
            "path": "/system",
            "sort": 1,
            "children": [
                {
                    "id": 2,
                    "parent_id": 1,
                    "title": "用户管理",
                    "path": "/users",
                    "permission": "users.list"
                }
            ]
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

## 创建菜单

```http
POST /api/admin/menus
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| parent_id | int | 否 | 父菜单ID，0为顶级 |
| title | string | 是 | 菜单标题 |
| icon | string | 否 | 图标 |
| path | string | 否 | 路由路径 |
| component | string | 否 | 组件路径 |
| permission | string | 否 | 所需权限 |
| sort | int | 否 | 排序，默认 0 |
| guard_name | string | 否 | Guard，默认 admin |
| hidden | boolean | 否 | 是否隐藏 |
| keep_alive | boolean | 否 | 是否缓存 |

### 响应示例

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 10,
        "title": "新菜单",
        "path": "/new"
    }
}
```

## 更新菜单

```http
PUT /api/admin/menus/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

同创建菜单

### 响应示例

```json
{
    "code": 200,
    "message": "更新成功",
    "data": null
}
```

## 删除菜单

```http
DELETE /api/admin/menus/{id}
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

## 获取当前用户菜单

```http
GET /api/admin/menus/my
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": 1,
            "title": "系统管理",
            "children": [...]
        }
    ]
}
```

## 菜单排序

```http
POST /api/admin/menus/sort
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | 是 | 菜单ID排序列表 |

### 响应示例

```json
{
    "code": 200,
    "message": "排序成功",
    "data": null
}
```
