# Controllers

## Controller

Base controller that all controllers extend.

### Namespace

```php
Lartrix\Controllers\Controller
```

### Basic Usage

```php
use Lartrix\Controllers\Controller;

class MyController extends Controller
{
    public function index()
    {
        return success('Hello World');
    }
}
```

## CrudController

CRUD base class providing a complete create/read/update/delete implementation.

### Namespace

```php
Lartrix\Controllers\CrudController
```

### Required Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getModelClass()` | string | Return the model class name |

### Optional Configuration Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getResourceName()` | string | Resource name |
| `getDefaultOrder()` | array | Default sort order |
| `getDefaultPageSize()` | int | Default page size |
| `getListWith()` | array | Eager load relations |
| `getExportColumns()` | array | Export columns |

### Query Methods

| Method | Description |
|--------|-------------|
| `applySearch($query, $request)` | Search logic |
| `applyFilters($query, $request)` | Filter logic |

### Validation Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getStoreRules()` | array | Create validation rules |
| `getUpdateRules($id)` | array | Update validation rules |

### Data Processing Methods

| Method | Description |
|--------|-------------|
| `prepareStoreData($validated)` | Process data before create |
| `prepareUpdateData($validated)` | Process data before update |

### Lifecycle Hooks

| Method | Description |
|--------|-------------|
| `afterStore($model, $validated)` | After create |
| `afterUpdate($model, $validated)` | After update |
| `afterStatusUpdate($model, $status)` | After status update |
| `beforeDelete($model)` | Before delete |
| `afterDelete($model)` | After delete |

### UI Methods

| Method | Description |
|--------|-------------|
| `listUi()` | List page schema |
| `formUi()` | Form schema |

## AuthController

Authentication controller handling login and logout.

### Namespace

```php
Lartrix\Controllers\AuthController
```

### Methods

| Method | Route | Description |
|--------|-------|-------------|
| `login()` | POST /auth/login | Login |
| `logout()` | POST /auth/logout | Logout |
| `refresh()` | POST /auth/refresh | Refresh token |
| `user()` | GET /auth/user | Current user |
| `config()` | GET /auth/config | System config |

## Other Controllers

### UserController

User management controller.

### RoleController

Role management controller.

### PermissionController

Permission management controller.

### MenuController

Menu management controller.

### ModuleController

Module management controller.

### SettingController

System settings controller.

### DictController

Data dictionary controller.

### NotificationController

Notification message controller.
