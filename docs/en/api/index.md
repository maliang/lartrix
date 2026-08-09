# API Reference

Complete API documentation for Lartrix.

## Authentication API

- Login
- Logout
- Refresh Token
- Get User Info

## User Management

- List Users
- Create User
- Update User
- Delete User
- Batch Operations

## Role Management

- List Roles
- Create Role
- Update Role
- Delete Role
- Assign Permissions

## Permission Management

- List Permissions
- Create Permission
- Update Permission
- Delete Permission

## Menu Management

- List Menus
- Create Menu
- Update Menu
- Delete Menu
- Sort Menus

## Module Management

- List Modules
- Enable/Disable Module
- Module Configuration

## System Settings

- Get Settings
- Update Settings
- Reset Settings

## Data Dictionary

- List Dictionary Items
- Create Dictionary Item
- Update Dictionary Item
- Delete Dictionary Item

## Notification Management

- List Notifications
- Mark as Read
- Delete Notification

## Standard Response Format

All API responses follow this format:

```json
{
  "code": 0,
  "msg": "Success",
  "data": {}
}
```

### Success Response

```json
{
  "code": 0,
  "msg": "Success",
  "data": {
    "id": 1,
    "name": "John Doe"
  }
}
```

### Error Response

```json
{
  "code": 40004,
  "msg": "Resource not found",
  "data": null
}
```

### Paginated Response

```json
{
  "code": 0,
  "msg": "Success",
  "data": {
    "data": [...],
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```
