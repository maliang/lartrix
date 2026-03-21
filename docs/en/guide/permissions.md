# Permission System

Lartrix provides complete RBAC permission management based on Spatie Laravel Permission.

## Permission Model

```
User ←→ Role ←→ Permission
```

- A user can have multiple roles
- A role can have multiple permissions
- Permissions are isolated by Guard (main/sub-admin)

## Permission Definition

### Standard CRUD Permissions

Each resource typically needs:

| Permission | Description |
|------------|-------------|
| `{resource}.list` | View list |
| `{resource}.create` | Create |
| `{resource}.update` | Update |
| `{resource}.delete` | Delete |
| `{resource}.export` | Export |

Example for post management:
- `posts.list`
- `posts.create`
- `posts.update`
- `posts.delete`

### Permission Usage

In CrudController, permissions are auto-generated based on resource name:

```php
class PostController extends CrudController
{
    protected function getResourceName(): string
    {
        return 'posts'; // Auto-generates posts.* permissions
    }
}
```

## Role Management

### Create Role

```php
use Lartrix\Models\Role;

$role = Role::create([
    'name' => 'editor',
    'guard_name' => 'admin',
]);

$role->givePermissionTo(['posts.list', 'posts.create', 'posts.update']);
```

### Assign Role

```php
$user->assignRole('editor');
```

## Permission Checks

### In Controllers

```php
public function store(Request $request)
{
    $this->authorize('posts.create');
    // ...
}
```

### In Middleware

```php
Route::middleware(['permission:posts.create'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```

### In Blade Templates

```blade
@can('posts.create')
    <a href="{{ route('posts.create') }}">Create Post</a>
@endcan
```

## Menu Permissions

Bind permissions to menu items:

```php
[
    'title' => 'Post Management',
    'path' => '/posts',
    'permission' => 'posts.list',
]
```

Users without this permission won't see the menu.

## Sub-Admin Permissions

Sub-admins use independent Guards:

```php
$role = Role::create([
    'name' => 'merchant_admin',
    'guard_name' => 'merchant', // Independent Guard
]);
```

Data is automatically isolated by `guard_name` field.
