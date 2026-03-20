# 权限系统

Lartrix 基于 Spatie Laravel Permission 提供完整的 RBAC 权限管理。

## 权限模型

```
用户(User) ←→ 角色(Role) ←→ 权限(Permission)
```

- 一个用户可以拥有多个角色
- 一个角色可以拥有多个权限
- 权限通过 Guard 隔离（主后台/二级后台）

## 权限定义

### 标准 CRUD 权限

每个资源通常需要以下权限：

| 权限 | 说明 |
|------|------|
| `{resource}.list` | 查看列表 |
| `{resource}.create` | 创建 |
| `{resource}.update` | 更新 |
| `{resource}.delete` | 删除 |
| `{resource}.export` | 导出 |

例如，文章管理的权限：
- `posts.list`
- `posts.create`
- `posts.update`
- `posts.delete`

### 权限使用

在 CrudController 中，权限会自动根据资源名生成：

```php
class PostController extends CrudController
{
    protected function getResourceName(): string
    {
        return 'posts'; // 自动生成 posts.* 权限
    }
}
```

## 角色管理

### 创建角色

```php
use Lartrix\\Models\\Role;

\$role = Role::create([
    'name' => 'editor',
    'guard_name' => 'admin',
]);

\$role->givePermissionTo(['posts.list', 'posts.create', 'posts.update']);
```

### 分配角色

```php
\$user->assignRole('editor');
```

## 权限检查

### 在控制器中

```php
public function store(Request \$request)
{
    \$this->authorize('posts.create');
    // ...
}
```

### 在中间件中

```php
Route::middleware(['permission:posts.create'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```

### 在 Blade 模板中

```blade
@can('posts.create')
    <a href="{{ route('posts.create') }}">创建文章</a>
@endcan
```

## 菜单权限

在菜单管理中，可以为菜单项绑定权限：

```php
[
    'title' => '文章管理',
    'path' => '/posts',
    'permission' => 'posts.list', // 绑定权限
]
```

没有该权限的用户将看不到此菜单。

## 二级后台权限

二级后台使用独立的 Guard：

```php
// 二级后台角色
\$role = Role::create([
    'name' => 'merchant_admin',
    'guard_name' => 'merchant', // 独立的 Guard
]);
```

数据通过 `guard_name` 字段自动隔离。
