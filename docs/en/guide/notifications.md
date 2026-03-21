# Notification System

Lartrix provides a complete notification system with support for category management and cross-admin sending.

## Features

- Notification category management
- Personal message center
- Read / unread status
- Main admin can send notifications to sub-admins

## Admin Interface

### Notification Categories

Log in to the admin panel and go to "System" -> "Notification Categories":

- System notifications
- Task reminders
- Announcement messages

### Send Notification

The main admin can send notifications to other admins:

1. Go to "System" -> "Send Notification"
2. Select target admin (Guard)
3. Select notification category
4. Fill in title and content
5. Select recipients (optional, leave empty to send to all)

## API

### Get Notification List

```http
GET /api/admin/notifications
```

### Mark as Read

```http
POST /api/admin/notifications/{id}/mark-read
```

### Mark All as Read

```http
POST /api/admin/notifications/mark-all-read
```

### Send Notification to Admin

```http
POST /api/admin/notifications/send-to-backend
{
    "guard": "merchant",
    "title": "System Announcement",
    "content": "System maintenance tonight",
    "category_id": 1
}
```

## Frontend Display

The system automatically shows a notification bell icon at the top of the page. Click it to view the message list.
