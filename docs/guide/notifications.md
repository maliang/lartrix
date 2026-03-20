# 通知系统

Lartrix 提供完整的通知消息系统，支持分类管理和多后台发送。

## 功能特性

- 通知分类管理
- 个人消息中心
- 已读/未读状态
- 主后台向二级后台发送通知

## 管理界面

### 通知分类

登录后台，进入"系统管理" -> "通知分类"：

- 系统通知
- 任务提醒
- 公告消息

### 发送通知

主后台可以向其他后台发送通知：

1. 进入"系统管理" -> "发送通知"
2. 选择目标后台（Guard）
3. 选择通知分类
4. 填写标题和内容
5. 选择接收用户（可选，不选则全员发送）

## API

### 获取通知列表

```http
GET /api/admin/notifications
```

### 标记已读

```http
POST /api/admin/notifications/{id}/mark-read
```

### 全部标记已读

```http
POST /api/admin/notifications/mark-all-read
```

### 发送通知给后台

```http
POST /api/admin/notifications/send-to-backend
{
    "guard": "merchant",
    "title": "系统公告",
    "content": "系统将于今晚维护",
    "category_id": 1
}
```

## 在前端显示

系统会自动在页面顶部显示通知铃铛，点击可查看消息列表。
