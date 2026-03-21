# Sub-Admin Systems

Lartrix supports creating independent sub-admin systems like merchant backend, agent backend, etc.

## Create Sub-Admin

```bash
php artisan lartrix:make-backend Merchant \
    --path=/merchant \
    --api-prefix=api/merchant \
    --title="Merchant Admin"
```

Parameters:

| Parameter | Description | Default |
|-----------|-------------|---------|
| name | Module name | Required |
| --path | Frontend path | /{name} |
| --api-prefix | API prefix | api/{name} |
| --table | User table | {name}s |
| --title | Admin title | {name} Admin |

## Generated Structure

```
Modules/Merchant/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── MenuController.php
│   │   └── PermissionController.php
│   ├── Models/
│   │   └── Merchant.php
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/api.php
```

## Configure Authentication

Add to `config/auth.php`:

```php
'guards' => [
    'merchant' => [
        'driver' => 'sanctum',
        'provider' => 'merchants',
    ],
],
'providers' => [
    'merchants' => [
        'driver' => 'eloquent',
        'model' => \Modules\Merchant\Models\Merchant::class,
    ],
],
```

## Initialize Data

```bash
php artisan migrate
php artisan module:seed Merchant
```

This creates:
- Super admin role
- Initial permissions
- Default menus

## Data Isolation

Sub-admins isolate data by `guard_name` field:

```php
// Menu isolation
Menu::where('guard_name', 'merchant')->get();

// Role isolation
Role::where('guard_name', 'merchant')->get();

// Permission isolation
Permission::where('guard_name', 'merchant')->get();
```

## Add Business Features

Add business controllers in sub-admin:

```php
<?php

namespace Modules\Merchant\Http\Controllers;

use Lartrix\Controllers\CrudController;

class ProductController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\Merchant\Models\Product::class;
    }

    protected function getResourceName(): string
    {
        return 'Product';
    }

    // Restrict to current merchant's data only
    protected function applyFilters($query, $request): void
    {
        $query->where('merchant_id', $request->user()->id);
    }
}
```

## Access Sub-Admin

After starting the service, visit:

```
http://localhost:8000/merchant
```

## Remove Sub-Admin

```bash
php artisan lartrix:remove-backend Merchant
```
