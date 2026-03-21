# Data Display Components

## Table

```php
use Lartrix\Schema\Components\NaiveUI\Table;

Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [
            ['title' => 'ID', 'key' => 'id'],
            ['title' => 'Name', 'key' => 'name'],
            ['title' => 'Status', 'key' => 'status'],
        ],
    ]);
```

### With Action Column

```php
Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [
            ['title' => 'ID', 'key' => 'id'],
            ['title' => 'Name', 'key' => 'name'],
            [
                'title' => 'Actions',
                'key' => 'actions',
                'render' => [
                    Button::make('Edit')->type('primary'),
                    Button::make('Delete')->type('error'),
                ],
            ],
        ],
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| data | array | Table data |
| columns | array | Column configuration |
| bordered | boolean | Show border |
| striped | boolean | Striped rows |
| singleLine | boolean | Single line display |
| size | string | Size: small, medium, large |
| loading | boolean | Loading state |
| pagination | object/boolean | Pagination config |
| scrollX | number | Horizontal scroll width |
| maxHeight | number | Max height |

## List

```php
use Lartrix\Schema\Components\NaiveUI\ListC;

ListC::make()
    ->props([
        'data' => '{{ list }}',
    ]);
```

## Tree

```php
use Lartrix\Schema\Components\NaiveUI\Tree;

Tree::make()
    ->props([
        'data' => [
            [
                'label' => 'Root',
                'key' => 'root',
                'children' => [
                    ['label' => 'Child 1', 'key' => 'child1'],
                    ['label' => 'Child 2', 'key' => 'child2'],
                ],
            ],
        ],
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| data | array | Tree data |
| checkable | boolean | Show checkbox |
| selectable | boolean | Selectable |
| expandedKeys | array | Expanded nodes |
| checkedKeys | array | Checked nodes |
| defaultExpandAll | boolean | Expand all by default |
| keyField | string | Node key field, default 'key' |
| labelField | string | Node label field, default 'label' |
| childrenField | string | Children field, default 'children' |

## TreeSelect

```php
use Lartrix\Schema\Components\NaiveUI\TreeSelect;

TreeSelect::make()
    ->props([
        'options' => [...],
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| options | array | Options data |
| multiple | boolean | Multiple selection |
| checkable | boolean | Show checkbox |
| cascade | boolean | Cascade selection |
| checkStrategy | string | Check strategy: all, parent, child |
| placeholder | string | Placeholder |
| clearable | boolean | Clearable |
| filterable | boolean | Searchable |

## Badge

```php
use Lartrix\Schema\Components\NaiveUI\Badge;

Badge::make()
    ->props(['value' => 5])
    ->children([
        Button::make('Messages'),
    ]);
```

## Tag

```php
use Lartrix\Schema\Components\NaiveUI\Tag;

Tag::make()->type('success')->children(['Success']);
Tag::make()->type('warning')->children(['Warning']);
Tag::make()->type('error')->children(['Error']);
```

## Avatar

```php
use Lartrix\Schema\Components\NaiveUI\Avatar;

Avatar::make()
    ->props([
        'src' => '{{ user.avatar }}',
        'fallbackSrc' => '/default-avatar.png',
    ]);
```

## Progress

```php
use Lartrix\Schema\Components\NaiveUI\Progress;

Progress::make()
    ->props([
        'percentage' => 50,
        'status' => 'success',
    ]);
```
