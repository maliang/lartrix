# 通知管理 API

## 获取通知列表

```http
GET /api/admin/notifications
Authorization: Bearer {token}
```

### 查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| page | int | 页码 |
| per_page | int | 每页数量 |
| read | boolean | 是否已读筛选 |
| category_id | int | 分类ID筛选 |

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "系统通知",
                "content": "系统将于今晚维护",
                "is_read": false,
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "total": 10,
        "unread_count": 5
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

## 获取通知详情

```http
GET /api/admin/notifications/{id}
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": {
        "id": 1,
        "title": "系统通知",
        "content": "系统将于今晚维护",
        "is_read": true,
        "read_at": "2024-01-01 12:05:00",
        "created_at": "2024-01-01 12:00:00"
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

**404 资源不存在**
```json
{
    "code": 404,
    "msg": "通知不存在",
    "data": null
}
```

## 标记已读

```http
POST /api/admin/notifications/{id}/mark-read
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "已标记为已读",
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
    "msg": "通知不存在",
    "data": null
}
```

## 全部标记已读

```http
POST /api/admin/notifications/mark-all-read
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "全部已标记为已读",
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

## 删除通知

```http
DELETE /api/admin/notifications/{id}
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
    "msg": "通知不存在",
    "data": null
}
```

## 获取通知分类

```http
GET /api/admin/notification-categories
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {
            "id": 1,
            "name": "系统通知",
            "icon": "notifications",
            "color": "#1890ff"
        },
        {
            "id": 2,
            "name": "任务提醒",
            "icon": "task",
            "color": "#52c41a"
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

## 发送通知给后台

```http
POST /api/admin/notifications/send-to-backend
Authorization: Bearer {token}
Content-Type: application/json
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| guard | string | 是 | Guard 名称 |
| title | string | 是 | 标题 |
| content | string | 是 | 内容 |
| category_id | int | 否 | 分类ID |
| user_ids | array | 否 | 指定用户ID，不传则发送给所有用户 |

### 响应示例

```json
{
    "code": 0,
    "msg": "发送成功",
    "data": {
        "sent_count": 10
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
            "guard": ["Guard 不存在"],
            "title": ["标题不能为空"]
        }
    }
}
```

## 获取可用 Guards

```http
GET /api/admin/notifications/available-guards
Authorization: Bearer {token}
```

### 响应示例

```json
{
    "code": 0,
    "msg": "success",
    "data": [
        {"value": "admin", "label": "主后台"},
        {"value": "merchant", "label": "商户后台"}
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

## 获取已发送通知

```http
GET /api/admin/notifications/sent
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
                "title": "系统公告",
                "sent_count": 100,
                "read_count": 50,
                "created_at": "2024-01-01 12:00:00"
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
