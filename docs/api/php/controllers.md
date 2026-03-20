# Controllers

## Controller

基础控制器，所有控制器继承此类。

### 命名空间

```php
Lartrix\\Controllers\\Controller
```

### 基础用法

```php
use Lartrix\\Controllers\\Controller;

class MyController extends Controller
{
    public function index()
    {
        return success('Hello World');
    }
}
```

## CrudController

CRUD 基类，提供完整的增删改查实现。

### 命名空间

```php
Lartrix\\Controllers\\CrudController
```

### 必须实现的方法

| 方法 | 返回类型 | 说明 |
|------|----------|------|
| `getModelClass()` | string | 返回模型类名 |

### 可选配置方法

| 方法 | 返回类型 | 说明 |
|------|----------|------|
| `getResourceName()` | string | 资源名称 |
| `getDefaultOrder()` | array | 默认排序 |
| `getDefaultPageSize()` | int | 默认分页大小 |
| `getListWith()` | array | 关联加载 |
| `getExportColumns()` | array | 导出列 |

### 查询方法

| 方法 | 说明 |
|------|------|
| `applySearch($query, $request)` | 搜索逻辑 |
| `applyFilters($query, $request)` | 过滤器逻辑 |

### 验证方法

| 方法 | 返回类型 | 说明 |
|------|----------|------|
| `getStoreRules()` | array | 创建验证规则 |
| `getUpdateRules($id)` | array | 更新验证规则 |

### 数据处理方法

| 方法 | 说明 |
|------|------|
| `prepareStoreData($validated)` | 创建前处理 |
| `prepareUpdateData($validated)` | 更新前处理 |

### 生命周期钩子

| 方法 | 说明 |
|------|------|
| `afterStore($model, $validated)` | 创建后 |
| `afterUpdate($model, $validated)` | 更新后 |
| `afterStatusUpdate($model, $status)` | 状态更新后 |
| `beforeDelete($model)` | 删除前 |
| `afterDelete($model)` | 删除后 |

### UI 方法

| 方法 | 说明 |
|------|------|
| `listUi()` | 列表界面 |
| `formUi()` | 表单界面 |

## AuthController

认证控制器，处理登录登出。

### 命名空间

```php
Lartrix\\Controllers\\AuthController
```

### 方法

| 方法 | 路由 | 说明 |
|------|------|------|
| `login()` | POST /auth/login | 登录 |
| `logout()` | POST /auth/logout | 登出 |
| `refresh()` | POST /auth/refresh | 刷新 Token |
| `user()` | GET /auth/user | 当前用户 |
| `config()` | GET /auth/config | 系统配置 |

## 其他控制器

### UserController

用户管理控制器。

### RoleController

角色管理控制器。

### PermissionController

权限管理控制器。

### MenuController

菜单管理控制器。

### ModuleController

模块管理控制器。

### SettingController

系统设置控制器。

### DictController

数据字典控制器。

### NotificationController

通知消息控制器。
