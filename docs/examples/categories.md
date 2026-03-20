# 分类管理示例

树形分类管理，支持无限级分类、拖拽排序。

## 迁移

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

## 模型

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

## 控制器

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
        return '分类';
    }

    protected function getDefaultOrder(): array
    {
        return ['sort' => 'asc', 'id' => 'asc'];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('分类管理')
            ->apiPrefix('/shop/categories')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => '分类名称'],
                ['key' => 'slug', 'title' => 'Slug', 'width' => 150],
                ['key' => 'sort', 'title' => '排序', 'width' => 100],
                [
                    'key' => 'status',
                    'title' => '状态',
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
                                        CallAction::make('$message.success', ['状态更新成功']),
                                        CallAction::make('loadData'),
                                    ]),
                            ]),
                    ],
                ],
                [
                    'key' => 'actions',
                    'title' => '操作',
                    'width' => 240,
                    'fixed' => 'right',
                    'slot' => [
                        Space::make()->children([
                            Button::make()
                                ->text('添加子分类')
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
                                ->text('编辑')
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
                                ->text('删除')
                                ->type('error')
                                ->size('small')
                                ->on('click', [
                                    CallAction::make('$dialog.warning', [
                                        '确认删除',
                                        '确定要删除这个分类吗？如果有子分类将一并删除。',
                                        [
                                            'positiveText' => '确认删除',
                                            'negativeText' => '取消',
                                            'onPositiveClick' => [
                                                FetchAction::make('/shop/categories/{{ slotData.row.id }}')
                                                    ->delete()
                                                    ->then([
                                                        CallAction::make('$message.success', ['删除成功']),
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
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索分类名称', 'clearable' => true])],
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
                    ->text('新增分类'),
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
            ->modal('form', '{{ editId ? "编辑分类" : "新增分类" }}', $this->getFormSchema());

        return success($schema->build());
    }

    protected function getFormSchema(): array
    {
        return OptForm::make('formData')
            ->fields([
                ['父级分类', 'parent_id', TreeSelect::make()->props([
                    'options' => '{{ categoryTree }}',
                    'placeholder' => '请选择父级分类（不选则为顶级分类）',
                    'clearable' => true,
                    'keyField' => 'id',
                    'labelField' => 'name',
                    'childrenField' => 'children',
                ])],
                ['分类名称', 'name', Input::make()->props(['placeholder' => '请输入分类名称'])],
                ['Slug', 'slug', Input::make()->props(['placeholder' => '请输入 URL 标识'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'rows' => 3, 'placeholder' => '请输入分类描述'])],
                ['排序', 'sort', InputNumber::make()->props(['min' => 0])],
                ['状态', 'status', SwitchC::make()->props(['checkedValue' => true, 'uncheckedValue' => false])],
            ])
            ->buttons([
                Button::make()
                    ->on('click', [
                        SetAction::make('formVisible', false),
                    ])
                    ->text('取消'),
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        CallAction::make('handleSubmit'),
                    ])
                    ->text('确定'),
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    FetchAction::make('{{ editId ? "/shop/categories/" + editId : "/shop/categories" }}')
                        ->method('{{ editId ? "PUT" : "POST" }}')
                        ->body('{{ formData }}')
                        ->then([
                            CallAction::make('$message.success', ['保存成功']),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
                            CallAction::make('loadCategoryTree'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['保存失败']),
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
        // 返回树形结构（用于 TreeSelect）
        if ($request->get('action_type') === 'tree') {
            $categories = Category::with('allChildren')
                ->whereNull('parent_id')
                ->orderBy('sort')
                ->get();
            return success($categories);
        }

        // 返回所有分类（用于 Select）
        if ($request->get('action_type') === 'all') {
            $categories = Category::select('id as value', 'name as label')
                ->orderBy('sort')
                ->get();
            return success($categories);
        }

        // 返回列表（树形展示）
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
        // 删除所有子分类
        $model->children()->each(function ($child) {
            $child->delete();
        });
    }
}
```

## 路由

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('categories', CategoryController::class)
        ->parameters(['categories' => 'id'])
        ->except(['create', 'edit']);
});
```
