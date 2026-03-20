# 布局组件

## Space 间距

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Space;

Space::make()
    ->children([
        Button::make('按钮1'),
        Button::make('按钮2'),
        Button::make('按钮3'),
    ]);

// 垂直排列
Space::make()
    ->props(['vertical' => true])
    ->children([...]);

// 设置间距大小
Space::make()
    ->props(['size' => 'large']) // small, medium, large
    ->children([...]);
```

## Flex 弹性布局

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Flex;

Flex::make()
    ->props([
        'justify' => 'space-between',
        'align' => 'center',
    ])
    ->children([
        Input::make(),
        Button::make('搜索'),
    ]);
```

## Grid 网格

```php
use Lartrix\\Schema\\Components\\NaiveUI\\{Grid, GridItem};

Grid::make()
    ->props([
        'cols' => 3,
        'xGap' => 12,
        'yGap' => 12,
    ])
    ->children([
        GridItem::make()->children([Card::make()]),
        GridItem::make()->children([Card::make()]),
        GridItem::make()->children([Card::make()]),
    ]);
```

## Row/Col 栅格

```php
use Lartrix\\Schema\\Components\\NaiveUI\\{Row, Col};

Row::make()
    ->children([
        Col::make()
            ->props(['span' => 12])
            ->children([Input::make()->props(['placeholder' => '左侧'])]),
        Col::make()
            ->props(['span' => 12])
            ->children([Input::make()->props(['placeholder' => '右侧'])]),
    ]);
```

## Layout 布局

```php
use Lartrix\\Schema\\Components\\NaiveUI\\{
    Layout, LayoutHeader, LayoutSider, LayoutContent, LayoutFooter
};

Layout::make()
    ->children([
        LayoutHeader::make()->children(['Header']),
        Layout::make()
            ->children([
                LayoutSider::make()->children(['Sidebar']),
                LayoutContent::make()->children(['Content']),
            ]),
        LayoutFooter::make()->children(['Footer']),
    ]);
```

## Divider 分割线

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Divider;

// 水平分割线
Divider::make();

// 带文字
Divider::make()->children(['或']);

// 垂直
Divider::make()->props(['vertical' => true]);
```
