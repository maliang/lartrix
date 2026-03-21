# CallAction

CallAction is used to call built-in or custom methods.

## Basic Usage

```php
use Lartrix\Schema\Actions\CallAction;

CallAction::make('$message.success', ['Operation successful']);
```

## Built-in Methods

### Message

```php
CallAction::make('$message.success', ['Success']);
CallAction::make('$message.error', ['Error']);
CallAction::make('$message.warning', ['Warning']);
CallAction::make('$message.info', ['Info']);
```

### Dialog

```php
CallAction::make('$dialog.success', ['Title', 'Content']);
CallAction::make('$dialog.error', ['Title', 'Content']);
CallAction::make('$dialog.warning', [
    'Confirm Delete',
    'This action cannot be undone',
    [
        'positiveText' => 'Confirm',
        'onPositiveClick' => [
            FetchAction::make('/api/delete')->delete(),
        ],
    ],
]);
```

### Notification

```php
CallAction::make('$notification.success', [
    'title' => 'Success',
    'content' => 'Operation completed',
    'duration' => 3000,
]);
```

### Navigation

```php
// Navigate to route
CallAction::make('$nav.push', ['/users']);

// Replace current route
CallAction::make('$nav.replace', ['/login']);

// Go back
CallAction::make('$nav.back');
```

### Tab Operations

```php
// Close current tab
CallAction::make('$tab.close');

// Open new tab
CallAction::make('$tab.open', ['/users', 'User Management']);

// Pin tab
CallAction::make('$tab.fix', ['/dashboard']);
```

### Window Operations

```php
// Open new window
CallAction::make('$window.open', ['https://example.com']);
```

### Download

```php
CallAction::make('$download', ['/api/export', 'data.xlsx']);
```

## Custom Methods

Define custom methods in CrudPage:

```php
CrudPage::make('Title')
    ->methods([
        'handleSubmit' => [
            FetchAction::make('/api/submit')
                ->post()
                ->body(['data' => '{{ formData }}'])
                ->then([
                    CallAction::make('$message.success', ['Saved']),
                    CallAction::make('loadData'),
                ]),
        ],
    ]);
```

Call custom method:

```php
Button::make('Submit')
    ->on('click', CallAction::make('handleSubmit'));
```

## Method Chaining

```php
Button::make('Delete')
    ->on('click', [
        CallAction::make('$dialog.warning', [
            'Confirm',
            'Delete this item?',
            [
                'positiveText' => 'Confirm',
                'onPositiveClick' => [
                    FetchAction::make('/api/delete/{{ id }}')->delete(),
                    CallAction::make('$message.success', ['Deleted']),
                    CallAction::make('loadData'),
                ],
            ],
        ]),
    ]);
```
