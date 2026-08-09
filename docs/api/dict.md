# 数据字典 API

## 获取字典选项

```http
GET /api/admin/dicts/options
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | 字典编码 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {"label": "启用", "value": 1},
        {"label": "禁用", "value": 0}
    ]
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

## 获取字典分组列表

```http
GET /api/admin/dict/groups
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "code": "status",
                "name": "状态",
                "description": "通用状态"
            }
        ],
        "total": 10
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

## 创建字典分组

```http
POST /api/admin/dict/groups
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | 分组编码 |
| name | string | 是 | 分组名称 |
| description | string | 否 | 描述 |

### 响应示例

```json
{
    "code": 0,
    "msg": "创建成功",
    "data": {
        "id": 1,
        "code": "status",
        "name": "状态"
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
            "code": ["编码已存在"],
            "name": ["名称不能为空"]
        }
    }
}
```

## 更新字典分组

```http
PUT /api/admin/dict/groups/{id}
Authorization: Bearer {token}
Content-Type: application/json
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

**404 资源不存在**
```json
{
    "code": 404,
    "msg": "资源不存在",
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
            "code": ["编码已存在"]
        }
    }
}
```

## 删除字典分组

```http
DELETE /api/admin/dict/groups/{id}
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

## 获取字典项列表

```http
GET /api/admin/dict/items
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| group_id | int | 分组ID |
| group_code | string | 分组编码 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {
            "id": 1,
            "group_id": 1,
            "label": "启用",
            "value": 1,
            "sort": 1
        },
        {
            "id": 2,
            "group_id": 1,
            "label": "禁用",
            "value": 0,
            "sort": 2
        }
    ]
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

## 创建字典项

```http
POST /api/admin/dict/items
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| group_id | int | 是 | 分组ID |
| label | string | 是 | 显示标签 |
| value | mixed | 是 | 选项值 |
| sort | int | 否 | 排序 |

### 响应示例

```json
{
    "code": 0,
    "msg": "创建成功",
    "data": {
        "id": 1,
        "label": "新选项",
        "value": "new"
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
            "group_id": ["分组不存在"],
            "label": ["标签不能为空"]
        }
    }
}
```

## 更新字典项

```http
PUT /api/admin/dict/items/{id}
Authorization: Bearer {token}
Content-Type: application/json
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

**422 验证失败**
```json
{
    "code": 422,
    "msg": "验证失败",
    "data": {
        "errors": {
            "label": ["标签不能为空"]
        }
    }
}
```

## 删除字典项

```http
DELETE /api/admin/dict/items/{id}
Authorization: Bearer {token}
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
