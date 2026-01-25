# Lartrix

A Laravel admin panel package that provides API endpoints for the [Trix](https://github.com/maliang/trix) frontend.

## Features

- 🔐 Authentication & Authorization (Laravel Sanctum + Spatie Permission)
- 👥 User, Role, Permission Management
- 📋 Menu Management (Tree Structure)
- ⚙️ System Settings
- 📦 Modular Development (nwidart/laravel-modules)
- 🎨 PHP Schema Builder - Build frontend UI with PHP

## Requirements

- PHP >= 8.1
- Laravel >= 10.0

## Installation

```bash
composer require lartrix/lartrix
```

Run the install command:

```bash
php artisan lartrix:install
```

The installation will:
1. Publish frontend assets to `public/admin`
2. Publish config and migration files
3. Run database migrations
4. Create super admin role and permissions
5. Create default menus
6. Interactively create admin account
7. Create AI development guide files (AGENTS.md, CLAUDE.md)

## Access Admin Panel

After installation, visit `/admin/` to access the admin panel.

## Configuration

Config file is located at `config/lartrix.php`:

```php
return [
    'route_prefix' => 'api/admin',      // API route prefix
    'guard' => 'sanctum',               // Auth guard
    'super_admin_role' => 'super-admin', // Super admin role name
    'models' => [...],                  // Model class mapping
    'controllers' => [...],             // Controller class mapping
];
```

## Development Guide

### Modular Development

Use `nwidart/laravel-modules` for modular development:

```bash
php artisan module:make Blog
```

### Controller Development

Extend `CrudController` for quick CRUD implementation:

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Button, Space};
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\Blog\Models\Post::class;
    }

    protected function getResourceName(): string
    {
        return 'Post';
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('Post Management')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => 'Title'],
            ])
            ->search([
                ['Keyword', 'keyword', Input::make()->props(['placeholder' => 'Search', 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()->type('primary')->on('click', [SetAction::make('formVisible', true)])->text('Add'),
            ]);

        return success($schema->build());
    }
}
```

### API Response

```php
// Success response
return success('Operation successful', $data);
return success($data);

// Error response
error('Error message', null, 40004);
```

## Schema Components

### NaiveUI Components

Class names without N prefix, output keeps N prefix:

```php
use Lartrix\Schema\Components\NaiveUI\{Button, Input, Select, SwitchC, Card, Modal};

Button::make()->type('primary')->text('Submit');
Input::make()->props(['placeholder' => 'Enter...']);
SwitchC::make();  // Switch is a PHP reserved word
```

### Business Components

```php
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};

CrudPage::make('Title')
    ->apiPrefix('/api/path')
    ->columns([...])
    ->search([...])
    ->build();
```

### Action Types

```php
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction, ScriptAction};

SetAction::make('visible', true);
CallAction::make('$message.success', ['Success']);
FetchAction::make('/api/path')->post()->body($data);
```

## Commands

```bash
php artisan lartrix:install          # Install
php artisan lartrix:publish-assets   # Publish frontend assets
php artisan lartrix:uninstall        # Uninstall
```

## Testing

```bash
./vendor/bin/phpunit
```

## License

MIT License
