# 通知系统

Lartrix 提供完整的通知消息系统，支持分类管理和多后台发送。

## 功能特性

- 通知分类管理
- 个人消息中心
- 已读/未读状态
- 主后台向二级后台发送通知
- 轮询或 WebSocket 实时刷新
- 按消息类型触发声音、弹窗和自定义前端行为
- 菜单和自定义导航项未读角标

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

## 实时刷新

实时能力由 `config/lartrix.php` 中的 `realtime` 控制：

```php
'realtime' => [
    'enabled' => true,
    'enable_notification' => true,
    'driver' => 'polling',
    'polling' => [
        'interval' => 15000,
        'api' => '/notifications/poll',
    ],
    'websocket' => [
        'url' => env('LARTRIX_REALTIME_WS_URL', ''),
    ],
],
```

轮询接口返回未读总数、按类型未读数和新增消息：

```json
{
  "unread_count": 12,
  "unread_count_by_type": {
    "audit.pending": 3
  },
  "has_new": true,
  "messages": []
}
```

角标数量来自 `unread_count_by_type`，不依赖通知列表分页，因此分页只加载当前页不会影响菜单或导航栏角标。

## 消息行为

按消息 `type` 配置行为：

```php
'realtime' => [
    'behaviors' => [
        'audit.pending' => [
            'notify' => false,
            'actions' => [
                ['type' => 'sound', 'src' => '/sounds/audit.mp3', 'times' => 3],
                ['type' => 'notification', 'title' => '新的审核任务'],
            ],
        ],
    ],
],
```

内置动作：

- `sound`：播放指定声音，`times` 控制播放次数。
- `notification`：弹出前端通知。

浏览器有自动播放限制，Trix 会在用户首次点击、触摸或按键后解锁音频；解锁前到达的声音会排队，解锁后顺序播放。

## 自定义导航项角标

```php
'header' => [
    'custom_items' => [
        [
            'icon' => 'ph:check-square',
            'tooltip' => '待审核',
            'badge' => [
                'source' => 'notification',
                'types' => ['audit.pending'],
                'mode' => 'count',
                'max' => 99,
                'color' => '#f5222d',
            ],
            'click' => 'route',
            'click_target' => '/audit',
        ],
    ],
],
```

处理完业务后，后端应更新对应消息的已读状态或业务状态，并在下一次轮询中返回新的未读计数。需要即时刷新时，前端可调用 `useNotificationRealtime().refresh()`。
