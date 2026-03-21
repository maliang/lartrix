# Installation

::: info Version
This documentation corresponds to Lartrix v1.x.
:::

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.1 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 |
| Composer | 2.x |

## Installation Steps

### 1. Install Lartrix

```bash
composer require lartrix/lartrix
```

### 2. Run Install Command

```bash
php artisan lartrix:install
```

This command will:

- Publish configuration file
- Publish database migration files
- Run database migrations
- Create initial permission data
- Publish frontend assets

### 3. Create Super Admin

```bash
php artisan db:seed --class=Lartrix\Database\Seeders\AdminUserSeeder
```

Default credentials:
- Username: admin
- Password: admin123

### 4. Access Admin Panel

```bash
php artisan serve
```

Visit: `http://localhost:8000/admin`

## Next Steps

- [Quick Start](/en/guide/quickstart) - Create your first CRUD page
