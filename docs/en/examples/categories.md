# Category Management Example

Tree-structured category management with support for unlimited nesting and drag-and-drop sorting.

## Migration

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('categories');
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->integer('sort')->default(0);
    $table->boolean('status')->default(true);
    $table->timestamps();
});
```

## Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'sort', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
}
```

## Controller

```php
<?php

namespace App\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, InputNumber, TreeSelect, SwitchC, Button, Space, Tag};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CategoryController extends CrudController
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    protected function getResourceName(): string
    {
        return 'Category';
    }

    protected function getDefaultOrder(): array
    {
        return ['sort' => 'asc', 'id' => 'asc'];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('Category Management')
            ->apiPrefix('/shop/categories')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => 'Name'],
                ['key' => 'slug', 'title' => 'Slug', 'width' => 150],
                ['key' => 'sort', 'title' => 'Sort', 'width' => 100],
                [
                    'key' => 'status',
                    'title' => 'Status',
                    'width' => 100,
                    'slot' => [
                        SwitchC::make()
                            ->props(['value' => '{{ slotData.row.status }}'])
                            ->on('update:value', [
                                FetchAction::make('/shop/categories/{{ slotData.row.id }}')
                                    ->put()
                                    ->body([
                                        'action_type' => 'status',
                                        'status' => '{{ $event }}',
                                    ])
                                    ->then([
                                        CallAction::make('$message.success', ['Status updated']),
                                        CallAction::make('loadData'),
                                    ]),
                            ]),
                    ],
                ],
                [
                    'key' => 'actions',
                    'title' => 'Actions',
                    'width' => 240,
                    'fixed' => 'right',
                    'slot' => [
                        Space::make()->children([
                            Button::make()
                                ->text('Add Child')
                                ->type('info')
                                ->size('small')
                                ->on('click', [
                                    SetAction::make('editId', null),
                                    SetAction::make('formData', [
                                        'parent_id' => '{{ slotData.row.id }}',
                                        'name' => '',
                                        'slug' => '',
                                        'description' => '',
                                        'sort' => 0,
                                        'status' => true,
                                    ]),
                                    SetAction::make('formVisible', true),
                                ]),
                            Button::make()
                                ->text('Edit')
                                ->type('primary')
                                ->size('small')
                                ->on('click', [
                                    SetAction::make('editId', '{{ slotData.row.id }}'),
                                    FetchAction::make('/shop/categories/{{ slotData.row.id }}')
                                        ->then([
                                            SetAction::make('formData', '{{ $response.data }}'),
                                            SetAction::make('formVisible', true),
                                        ]),
                                ]),
                            Button::make()
                                ->text('Delete')
                                ->type('error')
                                ->size('small')
                                ->on('click', [
                                    CallAction::make('$dialog.warning', [
                                        'Confirm Delete',
                                        'Are you sure you want to delete this category? All child categories will also be deleted.',
                                        [
                                            'positiveText' => 'Delete',
                                            'negativeText' => 'Cancel',
                                            'onPositiveClick' => [
                                                FetchAction::make('/shop/categories/{{ slotData.row.id }}')
                                                    ->delete()
                                                    ->then([
                                                        CallAction::make('$message.success', ['Deleted successfully']),
                                                        CallAction::make('loadData'),
                                                    ]),
                                            ],
                                        ],
                                    ]),
                                ]),
                        ]),
                    ],
                ],
            ])
            ->tree('children', false)
            ->pagination(false)
            ->search([
                ['Keyword', 'keyword', Input::make()->props(['placeholder' => 'Search category name', 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::make('editId', null),
                        SetAction::make('formData', [
                            'parent_id' => null,
                            'name' => '',
                            'slug' => '',
                            'description' => '',
                            'sort' => 0,
                            'status' => true,
                        ]),
                        SetAction::make('formVisible', true),
                    ])
                    ->text('Add Category'),
            ])
            ->data([
                'categoryTree' => [],
            ])
            ->methods([
                'loadCategoryTree' => [
                    FetchAction::make('/shop/categories?action_type=tree')
                        ->then([
                            SetAction::make('categoryTree', '{{ $response.data }}'),
                        ]),
                ],
            ])
            ->onMounted([
                CallAction::make('loadCategoryTree'),
            ])
            ->modal('form', '{{ editId ? "Edit Category" : "Add Category" }}', $this->getFormSchema());

        return success($schema->build());
    }

    protected function getFormSchema(): array
    {
        return OptForm::make('formData')
            ->fields([
                ['Parent Category', 'parent_id', TreeSelect::make()->props([
                    'options' => '{{ categoryTree }}',
                    'placeholder' => 'Select parent category (leave empty for top-level)',
                    'clearable' => true,
                    'keyField' => 'id',
                    'labelField' => 'name',
                    'childrenField' => 'children',
                ])],
                ['Name', 'name', Input::make()->props(['placeholder' => 'Enter category name'])],
                ['Slug', 'slug', Input::make()->props(['placeholder' => 'Enter URL slug'])],
                ['Description', 'description', Input::make()->props(['type' => 'textarea', 'rows' => 3, 'placeholder' => 'Enter description'])],
                ['Sort', 'sort', InputNumber::make()->props(['min' => 0])],
                ['Status', 'status', SwitchC::make()->props(['checkedValue' => true, 'uncheckedValue' => false])],
            ])
            ->buttons([
                Button::make()
                    ->on('click', [
                        SetAction::make('formVisible', false),
                    ])
                    ->text('Cancel'),
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        CallAction::make('handleSubmit'),
                    ])
                    ->text('Confirm'),
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    FetchAction::make('{{ editId ? "/shop/categories/" + editId : "/shop/categories" }}')
                        ->method('{{ editId ? "PUT" : "POST" }}')
                        ->body('{{ formData }}')
                        ->then([
                            CallAction::make('$message.success', ['Saved successfully']),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
                            CallAction::make('loadCategoryTree'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['Save failed']),
                        ])
                        ->finally([
                            SetAction::make('submitting', false),
                        ]),
                ],
            ])
            ->build();
    }

    protected function applySearch(Builder $query, Request $request): void
    {
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'sort' => 'required|integer|min:0',
            'status' => 'required|boolean',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'sort' => 'required|integer|min:0',
            'status' => 'required|boolean',
        ];
    }

    public function index(Request $request)
    {
        // Return tree structure (for TreeSelect)
        if ($request->get('action_type') === 'tree') {
            $categories = Category::with('allChildren')
                ->whereNull('parent_id')
                ->orderBy('sort')
                ->get();
            return success($categories);
        }

        // Return flat list (for Select)
        if ($request->get('action_type') === 'all') {
            $categories = Category::select('id as value', 'name as label')
                ->orderBy('sort')
                ->get();
            return success($categories);
        }

        // Return list (tree display)
        if ($request->get('action_type') === 'list' || !$request->has('action_type')) {
            $query = Category::with('children')->whereNull('parent_id');

            $this->applySearch($query, $request);

            $categories = $query->orderBy('sort')->get();

            return success([
                'data' => $categories,
                'total' => $categories->count(),
            ]);
        }

        return parent::index($request);
    }

    protected function beforeDelete(mixed $model): void
    {
        // Delete all child categories
        $model->children()->each(function ($child) {
            $child->delete();
        });
    }
}
```

## Routes

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('categories', CategoryController::class)
        ->parameters(['categories' => 'id'])
        ->except(['create', 'edit']);
});
```
