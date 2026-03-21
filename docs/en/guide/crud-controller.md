# CrudController

CrudController is a base class provided by Lartrix for quickly implementing standard CRUD operations.

## Basic Usage

Extend CrudController and implement required methods:

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
}
```

This automatically provides:

| Method | Route | Description |
|--------|-------|-------------|
| GET | /posts | List + List UI |
| POST | /posts | Create |
| PUT | /posts/{id} | Update |
| DELETE | /posts/{id} | Delete |

## Configurable Methods

### Basic Configuration

```php
// Model class (required)
protected function getModelClass(): string;

// Resource name (for messages)
protected function getResourceName(): string;

// Default sorting
protected function getDefaultOrder(): array
{
    return ['created_at' => 'desc'];
}

// Default page size
protected function getDefaultPageSize(): int
{
    return 15;
}

// Eager loading
protected function getListWith(): array
{
    return ['author', 'category'];
}
```

### Query Customization

```php
// Search logic
protected function applySearch($query, $request): void
{
    if ($request->filled('keyword')) {
        $query->where('title', 'like', "%{$request->keyword}%");
    }
}

// Filter logic
protected function applyFilters($query, $request): void
{
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
}
```

### Validation Rules

```php
// Create validation
protected function getStoreRules(): array
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'nullable|string',
        'status' => 'boolean',
    ];
}

// Update validation (with ID parameter)
protected function getUpdateRules(int $id): array
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'nullable|string',
        'status' => 'boolean',
    ];
}
```

### Data Processing

```php
// Before create
protected function prepareStoreData(array $validated): array
{
    $validated['slug'] = Str::slug($validated['title']);
    return $validated;
}

// Before update
protected function prepareUpdateData(array $validated): array
{
    return $validated;
}
```

### Lifecycle Hooks

```php
// After create
protected function afterStore($model, $validated): void
{
    // Send notifications, update cache, etc.
}

// After update
protected function afterUpdate($model, $validated): void
{
    // Log changes, etc.
}

// After status update
protected function afterStatusUpdate($model, $status): void
{
    // Handle status changes
}

// Before delete
protected function beforeDelete($model): void
{
    // Check related data, etc.
}

// After delete
protected function afterDelete($model): void
{
    // Clean up related data, etc.
}
```

## action_type Details

### index Method action_type

```php
// Default, returns list data
GET /posts

// Returns list UI Schema
GET /posts?action_type=list_ui

// Returns form UI Schema
GET /posts?action_type=form_ui

// Export data
GET /posts?action_type=export

// Batch delete
POST /posts?action_type=batch_destroy
    Body: {ids: [1, 2, 3]}
```

### update Method action_type

```php
// Default, update record
PUT /posts/{id}

// Update status (calls afterStatusUpdate)
PUT /posts/{id}?action_type=status
    Body: {status: false}

// Custom action
PUT /posts/{id}?action_type=publish
    // Automatically calls updatePublish method
```

### destroy Method action_type

```php
// Default, delete record
DELETE /posts/{id}

// Batch delete
DELETE /posts?action_type=batch
    Body: {ids: [1, 2, 3]}
```

## UI Definition

### List UI

```php
protected function listUi(): array
{
    $schema = CrudPage::make('Post Management')
        ->apiPrefix('/blog/posts')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'title' => 'Title'],
            ['key' => 'status', 'title' => 'Status'],
        ]);

    return success($schema->build());
}
```

### Form UI

```php
protected function formUi(): array
{
    $form = OptForm::make('formData')
        ->fields([
            ['Title', 'title', Input::make()],
            ['Content', 'content', Input::make()->type('textarea')],
            ['Status', 'status', SwitchC::make(), true],
        ]);

    return success(['form' => $form->build()]);
}
```

## Full Example

```php
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

    protected function getDefaultOrder(): array
    {
        return ['created_at' => 'desc'];
    }

    protected function getListWith(): array
    {
        return ['author'];
    }

    protected function applySearch($query, $request): void
    {
        if ($request->filled('keyword')) {
            $query->where('title', 'like', "%{$request->keyword}%");
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('Post Management')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'title', 'title' => 'Title'],
                ['key' => 'author.name', 'title' => 'Author'],
                ['key' => 'created_at', 'title' => 'Created At'],
            ])
            ->search([
                ['Keyword', 'keyword', Input::make()]
            ]);

        return success($schema->build());
    }
}
```
