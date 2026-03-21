# Introduction

Lartrix is a Laravel admin package that provides API interfaces for the Trix frontend. It supports user management, role permissions, menu management, system settings, and provides a PHP Schema Builder to generate vschema-ui compatible JSON Schema.

## Features

- **PHP Schema Builder**: Describe UI with PHP code, no need to write frontend code manually
- **120+ Components**: Rich NaiveUI component wrappers
- **RBAC Permission System**: Complete permission management based on Spatie Laravel Permission
- **Modular Development**: Support modular development based on nwidart/laravel-modules
- **Sub-Admin Systems**: Support creating independent sub-admin systems with complete data isolation
- **Type Safety**: Full PHP 8.1+ type support

## Tech Stack

- PHP 8.1+, Laravel 10/11/12
- Laravel Sanctum (Authentication)
- Spatie Laravel Permission (Permission Management)
- nwidart/laravel-modules (Modular Development)
- Maatwebsite Excel (Export)

## Quick Example

```php
use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Button};
use Lartrix\Schema\Components\Business\CrudPage;

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('Posts')
            ->apiPrefix('/api/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => 'Title'],
            ])
            ->search([
                ['Keyword', 'keyword', Input::make()]
            ]);

        return success($schema->build());
    }
}
```

## Next Steps

- [Installation](/en/guide/installation)
- [Quick Start](/en/guide/quickstart)
