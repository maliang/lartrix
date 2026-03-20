# 数据展示组件

## Table 表格

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Table;

Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [
            ['title' => 'ID', 'key' => 'id'],
            ['title' => '名称', 'key' => 'name'],
            ['title' => '状态', 'key' => 'status'],
        ],
    ]);
```

### 带操作列

```php
Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [
            ['title' => 'ID', 'key' => 'id'],
            ['title' => '名称', 'key' => 'name'],
            [
                'title' => '操作',
                'key' => 'actions',
                'render' => [
                    Button::make('编辑')->type('primary'),
                    Button::make('删除')->type('error'),
                ],
            ],
        ],
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| data | array | 表格数据 |
| columns | array | 列配置 |
| bordered | boolean | 显示边框 |
| striped | boolean | 斑马纹 |
| singleLine | boolean | 单行显示 |
| size | string | 尺寸：small, medium, large |
| loading | boolean | 加载状态 |
| pagination | object/boolean | 分页配置 |
| scrollX | number | 横向滚动宽度 |
| maxHeight | number | 最大高度 |

## List 列表

```php
use Lartrix\\Schema\\Components\\NaiveUI\\ListC;

ListC::make()
    ->props([
        'data' => '{{ list }}',
    ]);
```

## Tree 树形控件

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Tree;

Tree::make()
    ->props([
        'data' => [
            [
                'label' => '根节点',
                'key' => 'root',
                'children' => [
                    ['label' => '子节点1', 'key' => 'child1'],
                    ['label' => '子节点2', 'key' => 'child2'],
                ],
            ],
        ],
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| data | array | 树形数据 |
| checkable | boolean | 显示复选框 |
| selectable | boolean | 可选择 |
| expandedKeys | array | 展开的节点 |
| checkedKeys | array | 选中的节点 |
| defaultExpandAll | boolean | 默认展开所有 |
| keyField | string | 节点 key 字段，默认 'key' |
| labelField | string | 节点标签字段，默认 'label' |
| childrenField | string | 子节点字段，默认 'children' |

## TreeSelect 树形选择

```php
use Lartrix\\Schema\\Components\\NaiveUI\\TreeSelect;

TreeSelect::make()
    ->props([
        'options' => [...],
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| options | array | 选项数据 |
| multiple | boolean | 多选 |
| checkable | boolean | 显示复选框 |
| cascade | boolean | 级联选择 |
| checkStrategy | string | 选择策略：all, parent, child |
| placeholder | string | 占位符 |
| clearable | boolean | 可清空 |
| filterable | boolean | 可搜索 |

## Badge 徽标

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Badge;

Badge::make()
    ->props(['value' => 5])
    ->children([
        Button::make('消息'),
    ]);
```

## Tag 标签

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Tag;

Tag::make()->type('success')->children(['成功']);
Tag::make()->type('warning')->children(['警告']);
Tag::make()->type('error')->children(['错误']);
```

## Avatar 头像

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Avatar;

Avatar::make()
    ->props([
        'src' => '{{ user.avatar }}',
        'fallbackSrc' => '/default-avatar.png',
    ]);
```

## Progress 进度条

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Progress;

Progress::make()
    ->props([
        'percentage' => 50,
        'status' => 'success',
    ]);
```
