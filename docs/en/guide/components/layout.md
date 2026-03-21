# Layout Components

## Space

```php
use Lartrix\Schema\Components\NaiveUI\Space;

Space::make()
    ->children([
        Button::make('Button 1'),
        Button::make('Button 2'),
        Button::make('Button 3'),
    ]);

// Vertical layout
Space::make()
    ->props(['vertical' => true])
    ->children([...]);

// Set spacing size
Space::make()
    ->props(['size' => 'large']) // small, medium, large
    ->children([...]);
```

## Flex

```php
use Lartrix\Schema\Components\NaiveUI\Flex;

Flex::make()
    ->props([
        'justify' => 'space-between',
        'align' => 'center',
    ])
    ->children([
        Input::make(),
        Button::make('Search'),
    ]);
```

## Grid

```php
use Lartrix\Schema\Components\NaiveUI\{Grid, GridItem};

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

## Row/Col

```php
use Lartrix\Schema\Components\NaiveUI\{Row, Col};

Row::make()
    ->children([
        Col::make()
            ->props(['span' => 12])
            ->children([Input::make()->props(['placeholder' => 'Left'])]),
        Col::make()
            ->props(['span' => 12])
            ->children([Input::make()->props(['placeholder' => 'Right'])]),
    ]);
```

## Layout

```php
use Lartrix\Schema\Components\NaiveUI\{
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

## Divider

```php
use Lartrix\Schema\Components\NaiveUI\Divider;

// Horizontal divider
Divider::make();

// With text
Divider::make()->children(['OR']);

// Vertical
Divider::make()->props(['vertical' => true]);
```
