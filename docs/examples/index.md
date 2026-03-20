# 示例

这里提供完整的 Lartrix 使用示例，帮助你快速上手。

## 示例列表

- [用户管理](/examples/users) - 完整的用户 CRUD
- [文章管理](/examples/posts) - 带富文本编辑器的文章管理
- [商品管理](/examples/products) - 带图片上传的商品管理
- [分类管理](/examples/categories) - 树形分类管理
- [完整模块示例](/examples/full-module) - 从零开始创建一个完整模块

## 快速预览

### 最简单的 CRUD

```php
class UserController extends CrudController
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function listUi(): array
    {
        return success(
            CrudPage::make('用户管理')
                ->apiPrefix('/users')
                ->columns([
                    ['key' => 'id', 'title' => 'ID'],
                    ['key' => 'name', 'title' => '姓名'],
                    ['key' => 'email', 'title' => '邮箱'],
                ])
                ->build()
        );
    }
}
```

### 带搜索和筛选

```php
protected function listUi(): array
{
    \$schema = CrudPage::make('文章管理')
        ->apiPrefix('/posts')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'title' => '标题'],
            ['key' => 'status', 'title' => '状态'],
        ])
        ->search([
            ['标题', 'title', Input::make()],
            ['状态', 'status', Select::make()->options([
                ['label' => '启用', 'value' => 1],
                ['label' => '禁用', 'value' => 0],
            ])],
        ]);

    return success(\$schema->build());
}
```

### 带操作按钮

```php
protected function listUi(): array
{
    \$schema = CrudPage::make('用户管理')
        ->apiPrefix('/users')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'title' => '姓名'],
            [
                'key' => 'actions',
                'title' => '操作',
                'slot' => [
                    Space::make()
                        ->children([
                            Button::make('编辑')
                                ->type('primary')
                                ->size('small')
                                ->on('click', SetAction::make('editId', '{{ slotData.row.id }}')),
                            Button::make('删除')
                                ->type('error')
                                ->size('small')
                                ->on('click', [
                                    CallAction::make('\$dialog.warning', [
                                        '确认删除',
                                        '此操作不可恢复',
                                        [
                                            'positiveText' => '确认',
                                            'onPositiveClick' => [
                                                FetchAction::make('/users/{{ slotData.row.id }}')->delete(),
                                            ],
                                        ],
                                    ]),
                                ]),
                        ]),
                ],
            ],
        ]);

    return success(\$schema->build());
}
```
