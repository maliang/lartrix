# Feedback Components

## Alert

```php
use Lartrix\Schema\Components\NaiveUI\Alert;

// Info alert
Alert::make()
    ->type('info')
    ->props(['title' => 'Info', 'content' => 'This is an info message']);

// Success alert
Alert::make()
    ->type('success')
    ->props(['title' => 'Success', 'content' => 'Operation completed successfully']);

// Warning alert
Alert::make()
    ->type('warning')
    ->props(['title' => 'Warning', 'content' => 'Please proceed with caution']);

// Error alert
Alert::make()
    ->type('error')
    ->props(['title' => 'Error', 'content' => 'Operation failed']);

// Closable
Alert::make()
    ->type('info')
    ->props(['closable' => true]);
```

## Message

Via CallAction:

```php
CallAction::make('$message.success', ['Operation successful']);
CallAction::make('$message.error', ['Operation failed']);
CallAction::make('$message.warning', ['Warning message']);
CallAction::make('$message.info', ['Info message']);

// With options
CallAction::make('$message.success', [
    'Saved successfully',
    ['duration' => 5000, 'closable' => true],
]);
```

## Dialog

Via CallAction:

```php
// Success dialog
CallAction::make('$dialog.success', [
    'Success',
    'Data has been saved',
]);

// Confirmation dialog
CallAction::make('$dialog.warning', [
    'Confirm Delete',
    'This action cannot be undone. Continue?',
    [
        'positiveText' => 'Confirm Delete',
        'negativeText' => 'Cancel',
        'onPositiveClick' => [
            // Execute on confirm
            FetchAction::make('/api/delete')->delete(),
        ],
        'onNegativeClick' => [
            // Execute on cancel
            CallAction::make('$message.info', ['Cancelled']),
        ],
    ],
]);
```

## Modal

```php
use Lartrix\Schema\Components\NaiveUI\Modal;

Modal::make()
    ->show('{{ visible }}')
    ->props([
        'title' => 'Title',
        'preset' => 'card',
    ])
    ->on('update:show', SetAction::make('visible', '{{ $event }}'))
    ->children([
        // Modal content
        Input::make(),
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| show | boolean | Show state |
| title | string | Title |
| preset | string | Preset: dialog, card |
| closable | boolean | Show close button |
| maskClosable | boolean | Close on mask click |
| closeOnEsc | boolean | Close on ESC |
| autoFocus | boolean | Auto focus |
| transformOrigin | string | Transform origin |

## Drawer

```php
use Lartrix\Schema\Components\NaiveUI\Drawer;

Drawer::make()
    ->show('{{ drawerVisible }}')
    ->props([
        'title' => 'Details',
        'width' => 400,
        'placement' => 'right', // left, right, top, bottom
    ])
    ->on('update:show', SetAction::make('drawerVisible', '{{ $event }}'))
    ->children([
        // Drawer content
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| show | boolean | Show state |
| title | string | Title |
| placement | string | Placement: left, right, top, bottom |
| width | number/string | Width |
| height | number/string | Height |
| closable | boolean | Show close button |
| maskClosable | boolean | Close on mask click |

## Popconfirm

```php
use Lartrix\Schema\Components\NaiveUI\Popconfirm;

Popconfirm::make()
    ->props([
        'positiveText' => 'Confirm',
        'negativeText' => 'Cancel',
    ])
    ->on('positiveClick', FetchAction::make('/api/delete')->delete())
    ->children([
        Button::make('Delete')->type('error'),
    ]);
```

### Props

| Property | Type | Description |
|----------|------|-------------|
| positiveText | string | Confirm button text |
| negativeText | string | Cancel button text |
| showIcon | boolean | Show icon |
| onPositiveClick | array | Confirm callback |
| onNegativeClick | array | Cancel callback |

## Tooltip

```php
use Lartrix\Schema\Components\NaiveUI\Tooltip;

Tooltip::make()
    ->props(['trigger' => 'hover'])
    ->children([
        Button::make('Hover to view'),
    ]);
```

## LoadingBar

Via CallAction:

```php
// Start loading
CallAction::make('$loadingBar.start');

// Finish loading
CallAction::make('$loadingBar.finish');

// Loading error
CallAction::make('$loadingBar.error');
```
