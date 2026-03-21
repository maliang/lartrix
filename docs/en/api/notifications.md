# Notification Management API

## Get Notification List

```http
GET /api/admin/notifications
Authorization: Bearer {token}
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number |
| per_page | int | Items per page |
| read | boolean | Read status filter |
| category_id | int | Category ID filter |

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "System Notification",
                "content": "System maintenance tonight",
                "is_read": false,
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "total": 10,
        "unread_count": 5
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

## Get Notification Details

```http
GET /api/admin/notifications/{id}
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "id": 1,
        "title": "System Notification",
        "content": "System maintenance tonight",
        "is_read": true,
        "read_at": "2024-01-01 12:05:00",
        "created_at": "2024-01-01 12:00:00"
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**404 Not Found**
```json
{
    "code": 404,
    "message": "Notification not found",
    "data": null
}
```

## Mark as Read

```http
POST /api/admin/notifications/{id}/mark-read
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "Marked as read",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**404 Not Found**
```json
{
    "code": 404,
    "message": "Notification not found",
    "data": null
}
```

## Mark All as Read

```http
POST /api/admin/notifications/mark-all-read
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "All marked as read",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

## Delete Notification

```http
DELETE /api/admin/notifications/{id}
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "Deleted successfully",
    "data": null
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**404 Not Found**
```json
{
    "code": 404,
    "message": "Notification not found",
    "data": null
}
```

## Get Notification Categories

```http
GET /api/admin/notification-categories
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": 1,
            "name": "System Notification",
            "icon": "notifications",
            "color": "#1890ff"
        },
        {
            "id": 2,
            "name": "Task Reminder",
            "icon": "task",
            "color": "#52c41a"
        }
    ]
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

## Send Notification

```http
POST /api/admin/notifications/send
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| title | string | Yes | Notification title |
| content | string | Yes | Notification content |
| category_id | int | No | Category ID |
| user_ids | array | No | Target user IDs |
| guard_name | string | No | Guard name |

### Request Example

```json
{
    "title": "System Announcement",
    "content": "New feature released",
    "category_id": 1,
    "user_ids": [1, 2, 3]
}
```

### Response Example

```json
{
    "code": 200,
    "message": "Sent successfully",
    "data": {
        "sent_count": 3
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

**422 Validation Failed**
```json
{
    "code": 422,
    "message": "Validation failed",
    "data": {
        "errors": {
            "title": ["Title is required"],
            "content": ["Content is required"]
        }
    }
}
```

## Get Available Guards

```http
GET /api/admin/notifications/available-guards
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {"value": "admin", "label": "Main Admin"},
        {"value": "merchant", "label": "Merchant Admin"}
    ]
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```

## Get Sent Notifications

```http
GET /api/admin/notifications/sent
Authorization: Bearer {token}
```

### Response Example

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "System Announcement",
                "sent_count": 100,
                "read_count": 50,
                "created_at": "2024-01-01 12:00:00"
            }
        ]
    }
}
```

### Error Responses

**401 Unauthenticated**
```json
{
    "code": 401,
    "message": "Unauthenticated",
    "data": null
}
```

**403 Forbidden**
```json
{
    "code": 403,
    "message": "Forbidden",
    "data": null
}
```
