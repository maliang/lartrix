# Modular Development

Lartrix supports modular development based on nwidart/laravel-modules.

## Create Module

```bash
php artisan module:make Blog
```

Generated module structure:

```
Modules/Blog/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
├── routes/
│   └── api.php
├── tests/
├── module.json
└── composer.json
```

## Module Configuration

### module.json

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "Blog module",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\Blog\Providers\BlogServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

### Routes

Define in `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

## Controller Development

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Modules\Blog\Models\Post;

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
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
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'title', 'title' => 'Title'],
            ]);

        return success($schema->build());
    }
}
```

## Database Migrations

```bash
php artisan module:make-migration create_posts_table Blog
```

Migration files are located in `Modules/Blog/database/migrations/`.

## Module Management

### Enable/Disable Module

```bash
php artisan module:enable Blog
php artisan module:disable Blog
```

### List Modules

```bash
php artisan module:list
```

## Module Dependencies

Declare dependencies in `module.json`:

```json
{
    "requires": ["User", "Media"]
}
```

## Best Practices

1. **Independent Namespace**: Use module name as namespace prefix
2. **Database Prefix**: Use module name prefix for tables, e.g., `blog_posts`
3. **Config Isolation**: Use module name prefix for config, e.g., `blog.per_page`
4. **Route Prefix**: Use module name prefix for API routes, e.g., `/blog/posts`
