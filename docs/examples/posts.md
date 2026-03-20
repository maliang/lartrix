# 文章管理示例

完整的文章管理 CRUD，包含富文本编辑器、搜索、状态切换等功能。

## 迁移

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->string('cover')->nullable();
    $table->foreignId('category_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

## 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'cover',
        'category_id',
        'user_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## 控制器

```php
<?php

namespace App\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Select, SwitchC, Button, Space, Tag, Image};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm, RichEditor};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }

    protected function getListWith(): array
    {
        return ['category', 'user'];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('文章管理')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                [
                    'key' => 'cover',
                    'title' => '封面',
                    'width' => 100,
                    'slot' => [
                        Image::make()
                            ->props([
                                'src' => '{{ slotData.row.cover }}',
                                'width' => 60,
                                'height' => 60,
                                'objectFit' => 'cover',
                            ]),
                    ],
                ],
                ['key' => 'title', 'title' => '标题', 'ellipsis' => true],
                ['key' => 'category.name', 'title' => '分类', 'width' => 120],
                ['key' => 'user.name', 'title' => '作者', 'width' => 120],
                [
                    'key' => 'status',
                    'title' => '状态',
                    'width' => 100,
                    'slot' => [
                        SwitchC::make()
                            ->props([
                                'value' => '{{ slotData.row.status === "published" }}',
                                'checkedValue' => 'published',
                                'uncheckedValue' => 'draft',
                            ])
                            ->on('update:value', [
                                FetchAction::make('/blog/posts/{{ slotData.row.id }}')
                                    ->put()
                                    ->body([
                                        'action_type' => 'status',
                                        'status' => '{{ $event }}',
                                    ])
                                    ->then([
                                        CallAction::make('$message.success', ['状态更新成功']),
                                        CallAction::make('loadData'),
                                    ])
                                    ->catch([
                                        CallAction::make('$message.error', ['状态更新失败']),
                                    ]),
                            ]),
                    ],
                ],
                ['key' => 'published_at', 'title' => '发布时间', 'width' => 180],
                [
                    'key' => 'actions',
                    'title' => '操作',
                    'width' => 180,
                    'fixed' => 'right',
                    'slot' => [
                        Space::make()->children([
                            Button::make()
                                ->text('编辑')
                                ->type('primary')
                                ->size('small')
                                ->on('click', [
                                    SetAction::make('editId', '{{ slotData.row.id }}'),
                                    FetchAction::make('/blog/posts/{{ slotData.row.id }}')
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
                                        '确定要删除这篇文章吗？',
                                        [
                                            'positiveText' => '确认删除',
                                            'negativeText' => '取消',
                                            'onPositiveClick' => [
                                                FetchAction::make('/blog/posts/{{ slotData.row.id }}')
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
            ->scrollX(1200)
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索标题或内容', 'clearable' => true])],
                ['分类', 'category_id', Select::make()->props(['options' => '{{ categories }}', 'clearable' => true])],
                ['状态', 'status', Select::make()->props([
                    'options' => [
                        ['label' => '草稿', 'value' => 'draft'],
                        ['label' => '已发布', 'value' => 'published'],
                    ],
                    'clearable' => true,
                ])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::make('editId', null),
                        SetAction::make('formData', [
                            'title' => '',
                            'slug' => '',
                            'content' => '',
                            'cover' => '',
                            'category_id' => null,
                            'status' => 'draft',
                        ]),
                        SetAction::make('formVisible', true),
                    ])
                    ->text('新增文章'),
            ])
            ->data([
                'categories' => [],
            ])
            ->methods([
                'loadCategories' => [
                    FetchAction::make('/blog/categories?action_type=all')
                        ->then([
                            SetAction::make('categories', '{{ $response.data }}'),
                        ]),
                ],
            ])
            ->onMounted([
                CallAction::make('loadCategories'),
            ])
            ->modal('form', '{{ editId ? "编辑文章" : "新增文章" }}', $this->getFormSchema());

        return success($schema->build());
    }

    protected function getFormSchema(): array
    {
        return OptForm::make('formData')
            ->fields([
                ['标题', 'title', Input::make()->props(['placeholder' => '请输入标题'])],
                ['Slug', 'slug', Input::make()->props(['placeholder' => '请输入 URL 标识'])],
                ['分类', 'category_id', Select::make()->props(['options' => '{{ categories }}', 'placeholder' => '请选择分类'])],
                ['封面', 'cover', Input::make()->props(['placeholder' => '请输入封面图片 URL'])],
                ['内容', 'content', RichEditor::make()->props(['height' => 400])],
                ['状态', 'status', Select::make()->props([
                    'options' => [
                        ['label' => '草稿', 'value' => 'draft'],
                        ['label' => '已发布', 'value' => 'published'],
                    ],
                ])],
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
                    FetchAction::make('{{ editId ? "/blog/posts/" + editId : "/blog/posts" }}')
                        ->method('{{ editId ? "PUT" : "POST" }}')
                        ->body('{{ formData }}')
                        ->then([
                            CallAction::make('$message.success', ['保存成功']),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
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
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->keyword . '%')
                    ->orWhere('content', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts',
            'content' => 'required|string',
            'cover' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug,' . $id,
            'content' => 'required|string',
            'cover' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published',
        ];
    }

    protected function prepareStoreData(array $validated): array
    {
        $validated['user_id'] = auth()->id();

        if ($validated['status'] === 'published' && !isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        return $validated;
    }

    protected function afterStatusUpdate(mixed $model, bool $status): void
    {
        if ($status === 'published' && !$model->published_at) {
            $model->update(['published_at' => now()]);
        }
    }

    public function index(Request $request)
    {
        // 获取所有分类（用于下拉选择）
        if ($request->get('action_type') === 'all') {
            $categories = \App\Models\Category::select('id as value', 'name as label')
                ->orderBy('sort')
                ->get();
            return success($categories);
        }

        return parent::index($request);
    }
}
```

## 路由

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('posts', PostController::class)
        ->parameters(['posts' => 'id'])
        ->except(['create', 'edit']);
});
```
