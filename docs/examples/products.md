# 商品管理示例

商品管理，包含图片上传、SKU 管理、分类关联等功能。

## 迁移

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->integer('stock')->default(0);
    $table->json('images')->nullable();
    $table->json('skus')->nullable();
    $table->foreignId('category_id')->nullable()->constrained();
    $table->boolean('status')->default(true);
    $table->timestamps();
});
```

## 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'images',
        'skus',
        'category_id',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'skus' => 'array',
        'status' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

## 控制器

```php
<?php

namespace App\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, InputNumber, Select, SwitchC, Button, Space, Tag, Image, Upload};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm, DataTable};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends CrudController
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    protected function getResourceName(): string
    {
        return '商品';
    }

    protected function getListWith(): array
    {
        return ['category'];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('商品管理')
            ->apiPrefix('/shop/products')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                [
                    'key' => 'images',
                    'title' => '商品图片',
                    'width' => 120,
                    'slot' => [
                        Image::make()
                            ->props([
                                'src' => '{{ slotData.row.images && slotData.row.images[0] }}',
                                'width' => 80,
                                'height' => 80,
                                'objectFit' => 'cover',
                                'fallbackSrc' => '/placeholder.png',
                            ]),
                    ],
                ],
                ['key' => 'name', 'title' => '商品名称', 'ellipsis' => true],
                ['key' => 'category.name', 'title' => '分类', 'width' => 120],
                [
                    'key' => 'price',
                    'title' => '价格',
                    'width' => 120,
                    'slot' => [
                        Tag::make()
                            ->type('success')
                            ->children(['¥{{ slotData.row.price }}']),
                    ],
                ],
                ['key' => 'stock', 'title' => '库存', 'width' => 100],
                [
                    'key' => 'status',
                    'title' => '状态',
                    'width' => 100,
                    'slot' => [
                        SwitchC::make()
                            ->props(['value' => '{{ slotData.row.status }}'])
                            ->on('update:value', [
                                FetchAction::make('/shop/products/{{ slotData.row.id }}')
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
                                    FetchAction::make('/shop/products/{{ slotData.row.id }}')
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
                                        '确定要删除这个商品吗？',
                                        [
                                            'positiveText' => '确认删除',
                                            'negativeText' => '取消',
                                            'onPositiveClick' => [
                                                FetchAction::make('/shop/products/{{ slotData.row.id }}')
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
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索商品名称', 'clearable' => true])],
                ['分类', 'category_id', Select::make()->props(['options' => '{{ categories }}', 'clearable' => true])],
                ['状态', 'status', Select::make()->props([
                    'options' => [
                        ['label' => '上架', 'value' => true],
                        ['label' => '下架', 'value' => false],
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
                            'name' => '',
                            'description' => '',
                            'price' => 0,
                            'stock' => 0,
                            'images' => [],
                            'skus' => [],
                            'category_id' => null,
                            'status' => true,
                        ]),
                        SetAction::make('formVisible', true),
                    ])
                    ->text('新增商品'),
            ])
            ->data([
                'categories' => [],
            ])
            ->methods([
                'loadCategories' => [
                    FetchAction::make('/shop/categories?action_type=all')
                        ->then([
                            SetAction::make('categories', '{{ $response.data }}'),
                        ]),
                ],
            ])
            ->onMounted([
                CallAction::make('loadCategories'),
            ])
            ->modal('form', '{{ editId ? "编辑商品" : "新增商品" }}', $this->getFormSchema());

        return success($schema->build());
    }

    protected function getFormSchema(): array
    {
        return OptForm::make('formData')
            ->fields([
                ['商品名称', 'name', Input::make()->props(['placeholder' => '请输入商品名称'])],
                ['分类', 'category_id', Select::make()->props(['options' => '{{ categories }}', 'placeholder' => '请选择分类'])],
                ['商品图片', 'images', Upload::make()->props([
                    'action' => '/api/upload',
                    'listType' => 'image-card',
                    'multiple' => true,
                    'max' => 5,
                ])],
                ['商品描述', 'description', Input::make()->props(['type' => 'textarea', 'rows' => 4, 'placeholder' => '请输入商品描述'])],
                ['价格', 'price', InputNumber::make()->props(['min' => 0, 'step' => 0.01, 'precision' => 2])],
                ['库存', 'stock', InputNumber::make()->props(['min' => 0])],
                ['SKU 规格', 'skus', DataTable::make()->props([
                    'columns' => [
                        ['title' => '规格名称', 'key' => 'name'],
                        ['title' => '价格', 'key' => 'price'],
                        ['title' => '库存', 'key' => 'stock'],
                    ],
                    'data' => '{{ formData.skus }}',
                    'addable' => true,
                    'editable' => true,
                    'deletable' => true,
                ])],
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
                    FetchAction::make('{{ editId ? "/shop/products/" + editId : "/shop/products" }}')
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
            $query->where('name', 'like', '%' . $request->keyword . '%');
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'skus' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|boolean',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return $this->getStoreRules();
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
    Route::resource('products', ProductController::class)
        ->parameters(['products' => 'id'])
        ->except(['create', 'edit']);
});
```
