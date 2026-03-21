# Architecture Overview

Lartrix adopts a layered architecture separating frontend and backend.

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend (Trix)                        │
│                   Vue 3 + NaiveUI + vschema-ui              │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTP / JSON Schema
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Lartrix Package                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Schema     │  │   Models     │      │
│  │              │  │  Components  │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Actions    │  │   Services   │  │ Middleware   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ Eloquent
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Framework                        │
│        Routing │ Auth │ Validation │ Cache │ Queue          │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database / Storage                       │
└─────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. Schema Builder

The Schema system is the core of Lartrix, converting PHP code into JSON Schema for frontend rendering:

```php
// PHP code
Button::make('Click Me')
    ->type('primary')
    ->on('click', SetAction::make('visible', true));

// Converts to JSON
{
    "com": "NButton",
    "props": {
        "type": "primary"
    },
    "on": {
        "click": [{"action": "set", "name": "visible", "value": true}]
    }
}
```

### 2. CrudController

Base controller providing standard CRUD operations:
- Automatic list/create/update/delete
- Built-in pagination and search
- Permission checks
- UI Schema generation

### 3. Action System

Actions are abstractions for frontend interactions, supporting method chaining:

- **SetAction**: Set state
- **CallAction**: Call a method
- **FetchAction**: HTTP request
- **IfAction**: Conditional logic

### 4. Permission System

RBAC based on Spatie Laravel Permission:
- User ←→ Role ←→ Permission
- Guard isolation
- Menu permission binding

### 5. Module System

Modular development based on nwidart/laravel-modules:
- Independent namespaces
- Independent routes
- Independent migrations
- Hot-swappable modules

## Request Flow

### List Request

```
1. Frontend: GET /api/posts?action_type=list_ui
2. Backend: PostController@index
3. Check: Permission check
4. Execute: listUi() method
5. Return: JSON Schema
6. Frontend: Render UI based on Schema
```

### Data Request

```
1. Frontend: GET /api/posts?page=1
2. Backend: PostController@index
3. Check: Permission check
4. Query: Apply search/filters
5. Return: Paginated data
6. Frontend: Render data in table
```

## Directory Structure

```
lartrix/
├── src/
│   ├── Controllers/      # Base controllers
│   ├── Models/          # Base models
│   ├── Schema/          # Schema components
│   │   ├── Components/  # UI components
│   │   └── Actions/     # Action types
│   ├── Services/        # Business services
│   └── Traits/          # Reusable traits
├── config/              # Configuration
├── database/            # Migrations & seeders
└── routes/              # Routes
```

## Extension Points

1. **Custom Controllers**: Extend CrudController
2. **Custom Components**: Extend Component base class
3. **Custom Actions**: Implement Action interface
4. **Custom Modules**: Create independent modules
5. **Custom Guards**: Add sub-admin systems
