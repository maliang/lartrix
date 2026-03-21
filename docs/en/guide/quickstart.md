# Quick Start

This guide will help you create your first CRUD page in 5 minutes.

## Create a Module

```bash
php artisan module:make Blog
```

## Create Migration

```bash
php artisan module:make-migration create_posts_table Blog
```

Edit the migration file:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
});
```

Run migration:

```bash
php artisan migrate
```

## Create Model

```bash
php artisan module:make-model Post Blog
```

Edit the model:

```php
<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'status'];
}
```

## Create Controller

```bash
php artisan module:make-controller PostController Blog
```

Edit the controller:

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, SwitchC};
use Lartrix\Schema\Components\Business\CrudPage;
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
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => 'Title'],
                ['key' => 'status', 'title' => 'Status'],
            ])
            ->search([
                ['Keyword', 'keyword', Input::make()]
            ]);

        return success($schema->build());
    }
}
```

## Add Routes

Add to `Modules/Blog/Routes/api.php`:

```php
use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

## Add Menu

Log in to the admin panel, go to "System" -> "Menu Management", and add a new menu:

- Title: Post Management
- Path: /blog/posts
- Icon: document-text
- Permission: posts.list

## Done

Refresh the admin panel — you will now see "Post Management" in the sidebar!

## Next Steps

- [Schema System](/en/guide/schema) - Deep dive into Schema building
- [CrudController](/en/guide/crud-controller) - Learn more CRUD features
